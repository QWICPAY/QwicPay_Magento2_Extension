<?php
namespace Qwicpay\Checkout\Controller\Callback;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Exception as WebapiException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface as TransactionBuilder;
use Psr\Log\LoggerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;

class Index extends Action implements CsrfAwareActionInterface
{
    protected $orderRepository;
    protected $logger;
    protected $searchCriteriaBuilder;
    protected $urlBuilder;
    protected $scopeConfig;
    protected $quoteRepository;
    protected $cartManagement;
    protected $transactionBuilder;

    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger,
        UrlInterface $urlBuilder,
        ScopeConfigInterface $scopeConfig,
        CartRepositoryInterface $quoteRepository,
        CartManagementInterface $cartManagement,
        TransactionBuilder $transactionBuilder
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->quoteRepository = $quoteRepository;
        $this->cartManagement = $cartManagement;
        $this->transactionBuilder = $transactionBuilder;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function execute()
    {
        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $this->logger->info('Qwicpay Callback: Execution started.');

        try {
            // -------------------------------------------------------------
            // 1. Decode payload
            // -------------------------------------------------------------
            $requestBody = $this->getRequest()->getContent();
            $payload = json_decode($requestBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new WebapiException(__('Invalid JSON received.'), 0, WebapiException::HTTP_BAD_REQUEST);
            }

            // -------------------------------------------------------------
            // 2. Verify merchant key
            // -------------------------------------------------------------
            $merchantKey = $this->scopeConfig->getValue(
                'qwicpay/general/merchant_key',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $requestKey = $this->getRequest()->getHeader('KEY');

            if (!$requestKey || $requestKey !== $merchantKey) {
                $this->logger->critical('Qwicpay Callback: Invalid merchant key.');
                throw new WebapiException(__('Unauthorized.'), 0, WebapiException::HTTP_UNAUTHORIZED);
            }

            // -------------------------------------------------------------
            // 3. Validate required fields
            // -------------------------------------------------------------
            if (empty($payload['orderNumber']) || !isset($payload['payment']['transactionStatus'])) {
                throw new WebapiException(__('Missing required fields.'), 0, WebapiException::HTTP_BAD_REQUEST);
            }

            $orderNumber = $payload['orderNumber'];
            $quoteId = (int) preg_replace('/^QUOTE_/', '', $orderNumber);
            $stage = $payload['stage'] ?? '';
            $paidAmount = (int) ($payload['payment']['totalPaid'] ?? 0);
            $transactionStatus = (int) $payload['payment']['transactionStatus'];
            $transactionId = $payload['payment']['paymentRef']
                ?? $payload['transactionid']
                ?? uniqid('qwicpay_');

            // -------------------------------------------------------------
            // 4. Load quote
            // -------------------------------------------------------------
            try {
                $quote = $this->quoteRepository->get($quoteId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->logger->critical("Qwicpay Callback: Quote ID {$quoteId} not found.");
                throw new LocalizedException(__('Quote not found.'));
            }

            // -------------------------------------------------------------
            // 5. IDEMPOTENCY: Check for existing order FIRST
            // -------------------------------------------------------------
            $existingOrder = $this->findExistingOrderByQuoteId($quoteId);
            if ($existingOrder) {
                $this->logger->info("Qwicpay Callback: Order {$existingOrder->getIncrementId()} already exists.");
                return $this->handleExistingOrder($existingOrder, $transactionStatus, $transactionId, $stage, $paidAmount, $resultJson);
            }

            // -------------------------------------------------------------
            // 6. Ensure quote has email
            // -------------------------------------------------------------
            if (!$quote->getCustomerEmail() && $quote->getBillingAddress()) {
                $quote->setCustomerEmail($quote->getBillingAddress()->getEmail());
            }
            if ($quote->getBillingAddress() && !$quote->getBillingAddress()->getEmail()) {
                $quote->getBillingAddress()->setEmail($quote->getCustomerEmail());
            }
            $quote->setCustomerIsGuest(true);

            // -------------------------------------------------------------
            // 7. Handle payment result
            // -------------------------------------------------------------
            if ($transactionStatus === 1) {
                $result = $this->handleSuccess($quote, $transactionId, $stage, $paidAmount, $resultJson);
            } else {
                $result = $this->handleFailure($quote, $transactionStatus, $resultJson);
            }

            // -------------------------------------------------------------
            // 8. Lock quote (prevent reuse)
            // -------------------------------------------------------------
            $quote->setIsActive(false);
            $this->quoteRepository->save($quote);
            $this->logger->info("Qwicpay Callback: Quote {$quoteId} locked (is_active = 0).");

            return $result;

        } catch (\Exception $e) {
            $this->logger->critical('Qwicpay Callback Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $resultJson->setHttpResponseCode(WebapiException::HTTP_INTERNAL_ERROR);
            $resultJson->setData(['status' => 'error', 'message' => $e->getMessage()]);
            return $resultJson;
        }
    }

    // -----------------------------------------------------------------
    // Helper: Find existing order by quote ID
    // -----------------------------------------------------------------
    private function findExistingOrderByQuoteId(int $quoteId): ?Order
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('quote_id', $quoteId)
            ->create();
        $orderList = $this->orderRepository->getList($searchCriteria)->getItems();
        return $orderList ? reset($orderList) : null;
    }

    // -----------------------------------------------------------------
    // Idempotency: Handle duplicate callbacks
    // -----------------------------------------------------------------
    private function handleExistingOrder(
        Order $order,
        int $status,
        string $transactionId,
        string $stage,
        int $paidAmount,
        Json $resultJson
    ): Json {
        $this->logger->info("Qwicpay Callback: Order {$order->getIncrementId()} already exists (idempotent).");

        if ($status === 1) {
            // Add transaction if not exists
            $payment = $order->getPayment();
            if (!$payment->getTransaction($transactionId)) {
                $payment->setTransactionId($transactionId);
                $payment->setLastTransId($transactionId);
                $payment->setIsTransactionClosed(false);
                $payment->addTransaction(Transaction::TYPE_AUTH, null, false, 'QwicPay payment confirmed (idempotent).');
                $this->orderRepository->save($order);
            }
            $resultJson->setData(['status' => 'success', 'orderNumber' => $order->getIncrementId()]);
        } else {
            $resultJson->setData(['status' => 'error', 'message' => 'Payment failed.']);
        }
        return $resultJson;
    }

    // -----------------------------------------------------------------
    // SUCCESS: Create order WITHOUT re-running payment gateway
    // -----------------------------------------------------------------
    // In Qwicpay\Checkout\Controller\Callback\Index::handleSuccess()

    private function handleSuccess(Quote $quote, string $transactionId, string $stage, int $paidAmount, Json $resultJson): Json
    {
        try {
            $this->logger->info('Qwicpay Callback: Success started.');
            $quoteTotal = $quote->getGrandTotal();
            $paidAmountInMajorUnits = $paidAmount;
            $paidAmountFormatted = number_format($paidAmountInMajorUnits, 4, '.', '');
            $quoteTotalFormatted = number_format($quoteTotal, 4, '.', '');

            $this->logger->info('Qwicpay Callback: Quote Total {$quoteTotalFormatted}');

            if ($paidAmountFormatted !== $quoteTotalFormatted && $stage === 'PROD') {

                $this->logger->critical("Qwicpay Callback: SECURITY ERROR. Paid amount ({$paidAmountFormatted}) does not match quote total ({$quoteTotalFormatted}).", [
                    'quote_id' => $quote->getId(),
                    'qwicpay_id' => $transactionId,
                ]);

                // Stop processing and throw an exception
                $resultJson->setData([
                    'status' => 'error',
                    'error' => "Qwicpay Callback: SECURITY ERROR. Paid amount ({$paidAmountFormatted}) does not match quote total ({$quoteTotalFormatted}).",
                ]);
                return $resultJson;
            }
            $this->logger->info('Qwicpay Callback: Quote Total Match');
            $payment = $quote->getPayment();
            $payment->setMethod('qwicpay_one');

            // CRITICAL STEP: Set the external Qwicpay ID on the payment object.
            $payment->setTransactionId($transactionId);
            $payment->setLastTransId($transactionId);
            $payment->setIsTransactionClosed(false);

            // FLAG: This signals your payment command (CustomRedirectCommand) to skip
            // the gateway call entirely.
            $payment->setAdditionalInformation('is_callback_call', true);





            $this->quoteRepository->save($quote);
            $this->logger->info("Qwicpay Callback: Quote {$quote->getId()} prepared with Qwicpay ID: {$transactionId}.");

            // PLACE ORDER: This triggers your payment command, which will skip the gateway
            // call because of the 'is_callback_call' flag.
            $orderId = $this->cartManagement->placeOrder($quote->getId());

            $order = $this->orderRepository->get($orderId);
            $this->logger->info("Qwicpay Callback: Order successfully placed. ID: {$order->getIncrementId()}.");
            $this->logger->info("Qwicpay Callback: TotalPaid. {$paidAmountInMajorUnits}.");
            // NOTE: A transaction linked to the Qwicpay ID should be auto-created here
            // if your payment method is configured to do so. We just finalize state.

            // Finalize order state
            $order->setState(Order::STATE_PROCESSING);
            $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING));

            $order->setTotalPaid($paidAmountInMajorUnits);
            $order->setBaseTotalPaid($paidAmountInMajorUnits);

            if ($stage === 'PROD') {
                // Add a comment recording the external payment ID
                $order->addCommentToStatusHistory(
                    __("QwicPay payment completed. External Ref: %1", $transactionId),
                    false,
                    true
                );
            } else {
                $order->addCommentToStatusHistory(
                    __("TEST ORDER – NO PAYMENT. DO NOT SHIP."),
                    false,
                    false
                );
            }

            $this->orderRepository->save($order);
            $redirectUrl = $this->buildRedirectUrl($order);

            $resultJson->setData([
                'status' => 'success',
                'orderNumber' => $order->getIncrementId(),
                'redirect' => $redirectUrl
            ]);

        } catch (\Exception $e) {
            $resultJson->setData([
                'status' => 'error',
                'error' => $e,
            ]);

        }

        return $resultJson;
    }

    // -----------------------------------------------------------------
    // FAILURE: Lock quote, return error
    // -----------------------------------------------------------------
    private function handleFailure(Quote $quote, int $status, Json $resultJson): Json
    {
        $this->logger->info("Qwicpay Callback: Payment failed (status {$status}). Quote locked.");
        $resultJson->setData([
            'status' => 'error',
            'message' => 'Payment failed. Please try again.'
        ]);
        return $resultJson;
    }

    private function buildRedirectUrl(Order $order): string
    {
        return $this->urlBuilder->getUrl('qwicpay/guest/track', [
            '_secure' => true,
            'order_id' => $order->getIncrementId(),
            'email' => $order->getCustomerEmail(),
            'lastname' => $order->getCustomerLastname(),
            'zip' => $order->getBillingAddress() ? $order->getBillingAddress()->getPostcode() : 'null'
        ]);
    }
}