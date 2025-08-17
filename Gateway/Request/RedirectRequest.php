<?php
// File: app/code/Qwicpay/Checkout/Gateway/Request/RedirectRequest.php
namespace Qwicpay\Checkout\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Psr\Log\LoggerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Action\Context;
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
     * Builds the request payload from the order data.
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $this->logger->info('Qwicpay RedirectRequest: Starting request build for callback flow.');

        try {
            /** @var PaymentDataObjectInterface $paymentDataObject */
            $paymentDataObject = $buildSubject['payment'];

            $order = $paymentDataObject->getOrder();
            $billingAddress = $order->getBillingAddress();
            
            // Get the order increment ID for logging purposes
            $orderIncrementId = $order->getOrderIncrementId();
            $this->logger->info('Qwicpay RedirectRequest: Order data received for ID ' . $orderIncrementId);

            // --- Log User Info ---
            $userPayload = [
                "name" => $billingAddress->getFirstname(),
                "surname" => $billingAddress->getLastname(),
                "email" => $billingAddress->getEmail()
            ];
            $this->logger->info('Qwicpay RedirectRequest: User payload -> ' . json_encode($userPayload));
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->critical('Qwicpay RedirectRequest: JSON error in user data: ' . json_last_error_msg());
            }

            // --- Log Billing Info ---
            $billingPayload = [
                "street" => implode(', ', $billingAddress->getStreet()),
                "city" => $billingAddress->getCity(),
                "postalCode" => $billingAddress->getPostcode(),
                "country" => $billingAddress->getCountryId(),
                "cell" => $billingAddress->getTelephone()
            ];
            $this->logger->info('Qwicpay RedirectRequest: Billing payload -> ' . json_encode($billingPayload));
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->critical('Qwicpay RedirectRequest: JSON error in billing data: ' . json_last_error_msg());
            }

            // --- Log Items Array ---
            $items = [];
            $this->logger->info('Qwicpay RedirectRequest: Starting item processing loop.');
            foreach ($order->getItems() as $item) {
                // Skip child items of configurable/bundle products to prevent duplicates
                if ($item->getParentItem()) {
                    continue;
                }
                
                // Ensure item data is valid before adding to payload
                if (!$item->getSku() || $item->isDeleted()) {
                    continue;
                }

                $itemArray = [
                    'title' => (string)$item->getName(),
                    'id' => (string)$item->getSku(),
                    'price' => (int)($item->getPriceInclTax()), // Use price including tax in cents
                    'quantity' => (int)$item->getQtyOrdered(),
                ];
                $items[] = $itemArray;
                $this->logger->info('Qwicpay RedirectRequest: Added item ' . $item->getSku() . ' to payload.');
            }
            $this->logger->info('Qwicpay RedirectRequest: Finished item processing. Total items added: ' . count($items));
            $this->logger->info('Qwicpay RedirectRequest: Items array payload -> ' . json_encode($items));
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->critical('Qwicpay RedirectRequest: JSON error in items data: ' . json_last_error_msg());
            }

            // Get the stage from Magento's configuration
            $stage = $this->scopeConfig->getValue('qwicpay/general/stage');
            $this->logger->info('Qwicpay RedirectRequest: Retrieved stage from config -> ' . $stage);


            // --- Log Payment Info ---
            $this->logger->info('Qwicpay RedirectRequest: Start Payment Calculations ');
            $totalAmount = $order->getGrandTotalAmount()*100;
            $this->logger->info('Qwicpay RedirectRequest: Payment Total -> ' . json_encode($totalAmount));
            $paymentPayload = [
                "amount" => $totalAmount,
                "currency" => $order->getCurrencyCode()
            ];
            $this->logger->info('Qwicpay RedirectRequest: Payment payload -> ' . json_encode($paymentPayload));
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->critical('Qwicpay RedirectRequest: JSON error in payment data: ' . json_last_error_msg());
            }

            // Generate the URL for the backend callback
            $callbackUrl = $this->urlBuilder->getUrl(
                'qwicpay/callback/index',
                ['_secure' => true]
            );
            $this->logger->info('Qwicpay RedirectRequest: Generated callback URL: ' . $callbackUrl);
            
            // Build the final request payload
            $payload = [
                "platform" => "API",
                "stage" => $stage, 
                "orderNumber" => $orderIncrementId,
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
            // Return an empty payload to prevent further execution in case of an error
            return ['payload' => []];
        }
    }
}
