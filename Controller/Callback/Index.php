<?php
// File: app/code/Qwicpay/Checkout/Controller/Callback/Index.php
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
use Psr\Log\LoggerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;


class Index extends Action implements CsrfAwareActionInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;



    protected $urlBuilder;
    protected $scopeConfig;

    /**
     * @param Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LoggerInterface $logger
     * @param UrlInterface $urlBuilder
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger,
        UrlInterface $urlBuilder,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritDoc
     */


    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Adds a new transaction to the order's payment.
     *
     * @param Order $order
     * @param string $transactionId
     * @param string $transactionType
     * @param string $comment
     * @return void
     */
    protected function _addTransaction(Order $order, string $transactionId, string $transactionType, string $comment)
    {
        $payment = $order->getPayment();
        $payment->setLastTransId($transactionId);
        $payment->setTransactionId($transactionId);
        $payment->setIsTransactionClosed(0);

        $payment->addTransaction(
            $transactionType,
            null,
            false,
            $comment
        );

        $this->orderRepository->save($order);
    }

    /**
     * Main action to handle QwicPay's backend-to-backend callback.
     *
     * @return Json
     */
    public function execute()
    {
        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $this->logger->info('Qwicpay Callback: Execution started.');

        try {


            // Get the raw JSON body from the request
            $requestBody = $this->getRequest()->getContent();


            $payload = json_decode($requestBody, true);
            $this->logger->info('Qwicpay Callback: Attempting to decode JSON payload.');

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Qwicpay Callback: Invalid JSON format received.');
                throw new WebapiException(
                    __('Invalid JSON received.'),
                    0,
                    WebapiException::HTTP_BAD_REQUEST
                );
            }



            $this->logger->info('Qwicpay Callback: Successfully decoded payload. Starting Auth');


            // Fetch merchant key from Magento config
            $merchantKey = $this->scopeConfig->getValue(
                'qwicpay/general/merchant_key',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );


            // Read KEY header from request
            $requestKey = $this->getRequest()->getHeader('KEY');

            // Compare keys
            if (!$requestKey || $requestKey !== $merchantKey) {
                $this->logger->critical('Qwicpay Callback: Merchant key mismatch. Expected ' . $merchantKey . ' but got ' . $requestKey);
                throw new WebapiException(
                    __('Unauthorized: Invalid merchant key.'),
                    0,
                    WebapiException::HTTP_UNAUTHORIZED
                );
            }

            // Validate that required fields exist in the payload
            if (empty($payload['orderNumber']) || empty($payload['payment']['transactionStatus'])) {
                $this->logger->critical('Qwicpay Callback: Required payload data (orderNumber or transactionStatus) is missing.');
                throw new WebapiException(
                    __('Invalid payload.'),
                    0,
                    WebapiException::HTTP_BAD_REQUEST
                );
            }

            $orderNumber = $payload['orderNumber'];
            $stage = $payload['stage'];
            $paidAmmount = (int) $payload['payment']['totalPaid'];
            $transactionStatus = (int) $payload['payment']['transactionStatus'];
            $transactionId = $payload['payment']['paymentRef'] ?? $payload['transactionid'] ?? uniqid('qwicpay_');


            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('increment_id', $orderNumber)
                ->create();
            $orderList = $this->orderRepository->getList($searchCriteria)->getItems();

            $order = count($orderList) > 0 ? array_shift($orderList) : null;

            if (!$order || !$order->getId()) {
                $this->logger->critical('Qwicpay Callback: Could not find order ' . $orderNumber);
                throw new LocalizedException(__('Order not found.'));
            }
            $this->logger->info('Qwicpay Callback: Successfully loaded order ' . $orderNumber);

            // Check if the payment was successful (status code 1)
            if ($transactionStatus === 1) {
                $this->logger->info('Qwicpay Callback: Payment was successful. Processing order update.');

                // Add a transaction record to the order's payment
                $this->_addTransaction(
                    $order,
                    $transactionId,
                    Transaction::TYPE_AUTH,
                    'QwicPay payment completed successfully.'
                );

                // Update order status and add a comment
                $order->setState(Order::STATE_PROCESSING);
                $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING));


                if ($stage === "PROD") {
                    $order->setTotalPaid($paidAmmount);
                    $order->addStatusToHistory(
                        $order->getStatus(),
                        __('QwicPay payment completed successfully. Payment Ref: %1.', $transactionId),
                        true
                    );
                } else {
                    $order->addStatusToHistory(
                        $order->getStatus(),
                        __('THIS IS A TEST ORDER. NO PAYMENT OCCURRED. DO NOT SHIP.'),
                        false
                    );
                }             

                // Save the updated order
                $this->orderRepository->save($order);
                $this->logger->info('Qwicpay Callback: Successfully updated order ' . $orderNumber . ' to processing.');

                // Return a success response to QwicPay
                // Redirect to success page (checkout success)
                $redirectUrl = $this->urlBuilder->getUrl(
                    'qwicpay/guest/track',
                    [
                        '_secure' => true,
                        'order_id' => $order->getIncrementId(),
                        'email' => $order->getCustomerEmail(),
                        'lastname' => $order->getCustomerLastname(),
                        'zip' => $order->getBillingAddress() ? $order->getBillingAddress()->getPostcode() : 'null'
                    ]
                );


                // OR: redirect to customer’s order view page
// $redirectUrl = $this->urlBuilder->getUrl('sales/order/view', ['order_id' => $order->getId(), '_secure' => true]);

                $this->logger->info('Qwicpay Callback: Generated redirect URL: ' . $redirectUrl);

                $resultJson->setData([
                    'status' => 'success',
                    'redirect' => $redirectUrl
                ]);

            } else {
                // Payment failed, cancel the order and add a comment
                $this->logger->info('Qwicpay Callback: Payment failed with status ' . $transactionStatus . '. Cancelling order.');
                $order->cancel();
                $order->addStatusToHistory(
                    Order::STATE_CANCELED,
                    __('QwicPay payment failed. Transaction Status: %1.', $transactionStatus),
                    true
                );
                $this->orderRepository->save($order);

                $this->logger->info('Qwicpay Callback: Order ' . $orderNumber . ' was canceled due to failed payment.');
                $resultJson->setData(['status' => 'error', 'message' => 'Payment failed.']);
            }

        } catch (\Exception $e) {
            $this->logger->critical('Qwicpay Callback Error: An exception occurred: ' . $e->getMessage());
            $resultJson->setHttpResponseCode(WebapiException::HTTP_INTERNAL_ERROR);
            $resultJson->setData(['status' => 'error', 'message' => $e->getMessage()]);
        }

        return $resultJson;
    }
}
