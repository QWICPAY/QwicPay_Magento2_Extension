<?php
namespace Qwicpay\Checkout\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Psr\Log\LoggerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class RedirectRequest implements BuilderInterface
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param CheckoutSession $checkoutSession
     * @param UrlInterface $urlBuilder
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        UrlInterface $urlBuilder,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder = $urlBuilder;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Builds the request payload from the quote data.
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $this->logger->info('Qwicpay RedirectRequest: Starting request build for callback flow.');

        try {
            // Get the quote from the checkout session
            $quote = $this->checkoutSession->getQuote();
            if (!$quote->getId()) {
                $this->logger->critical('Qwicpay RedirectRequest: No active quote found.');
                throw new \Exception('No active quote found.');
            }

            $billingAddress = $quote->getBillingAddress();
            $quoteId = $quote->getId(); // Use quote ID as a unique identifier

            $this->logger->info('Qwicpay RedirectRequest: Quote data received for ID ' . $quoteId);

            $userPayload = [
                "name" => $billingAddress->getFirstname() ?: 'Guest',
                "surname" => $billingAddress->getLastname() ?: 'Guest',
                "email" => $billingAddress->getEmail() ?: $quote->getCustomerEmail()
            ];

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->critical('Qwicpay RedirectRequest: JSON error in user data: ' . json_last_error_msg());
            }

            $billingPayload = [
                "street" => implode(', ', $billingAddress->getStreet() ?: ['N/A']),
                "city" => $billingAddress->getCity() ?: 'N/A',
                "postalCode" => $billingAddress->getPostcode() ?: 'N/A',
                "country" => $billingAddress->getCountryId() ?: 'ZA',
                "cell" => $billingAddress->getTelephone() ?: 'N/A'
            ];

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->critical('Qwicpay RedirectRequest: JSON error in billing data: ' . json_last_error_msg());
            }

            $items = [];
            foreach ($quote->getAllVisibleItems() as $item) {
                // Skip child items of configurable/bundle products
                if ($item->getParentItem()) {
                    continue;
                }

                if (!$item->getSku() || $item->isDeleted()) {
                    continue;
                }

                $itemArray = [
                    'title' => (string)$item->getName(),
                    'id' => (string)$item->getSku(),
                    'price' => (int)($item->getPriceInclTax() * 100), // Convert to cents
                    'quantity' => (int)$item->getQty(),
                ];
                $items[] = $itemArray;
            }

            $this->logger->info('Qwicpay RedirectRequest: Finished item processing. Total items added: ' . count($items));

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->critical('Qwicpay RedirectRequest: JSON error in items data: ' . json_last_error_msg());
            }

            // Get the stage from Magento's configuration
            $stage = $this->scopeConfig->getValue('qwicpay/general/stage');
            $this->logger->info('Qwicpay RedirectRequest: Retrieved stage from config -> ' . $stage);

            $totalAmount = $quote->getGrandTotal() * 100; // Convert to cents
            $paymentPayload = [
                "amount" => $totalAmount,
                "currency" => $quote->getQuoteCurrencyCode() ?: 'ZAR'
            ];

            $this->logger->info('Qwicpay RedirectRequest: Payment payload -> ' . json_encode($paymentPayload));
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->critical('Qwicpay RedirectRequest: JSON error in payment data: ' . json_last_error_msg());
            }

            // Generate the URL for the backend callback
            $callbackUrl = $this->urlBuilder->getUrl(
                'qwicpay/callback/index',
                ['_secure' => true, 'quote_id' => $quoteId]
            );

            // Build the final request payload
            $payload = [
                "platform" => "MAGENTO",
                "stage" => $stage,
                "orderNumber" =>  $quoteId, // Use quote ID as a temporary identifier
                "user" => $userPayload,
                "billing" => $billingPayload,
                "items" => $items,
                "payment" => $paymentPayload,
                "response" => [
                    "url" => $callbackUrl,
                ],
            ];

            $this->logger->info('Qwicpay RedirectRequest: Final payload built -> ' . json_encode($payload));

            return [
                'payload' => $payload
            ];
        } catch (\Exception $e) {
            $this->logger->critical('Qwicpay RedirectRequest: A fatal error occurred during build: ' . $e->getMessage());
            return ['payload' => []];
        }
    }
}