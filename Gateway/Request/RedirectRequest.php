<?php
namespace Qwicpay\Checkout\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\UrlInterface;

class RedirectRequest implements BuilderInterface
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param CheckoutSession $checkoutSession
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        UrlInterface $urlBuilder
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Builds API request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment']) || !$buildSubject['payment'] instanceof PaymentDataObjectInterface) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $buildSubject['payment'];
        $order = $paymentDO->getOrder();
        $billingAddress = $order->getBillingAddress();
        
        // Build the return and webhook URLs
        $returnUrl = $this->urlBuilder->getUrl('checkout/onepage/success');
        $cancelUrl = $this->urlBuilder->getUrl('checkout/onepage/failure');
        $webhookUrl = $this->urlBuilder->getUrl('qwicpay/webhook/index');

        // Create the payload for the Qwicpay API
        $payload = [
            "platform" => "API",
            "orderNumber" => $order->getOrderIncrementId(),
            "cancelUrl" => $cancelUrl,
            "user" => [
                "firstName" => $billingAddress->getFirstname(),
                "lastName" => $billingAddress->getLastname(),
                "email" => $order->getCustomerEmail()
            ],
            "billing" => [
                "address1" => $billingAddress->getStreetLine(1),
                "address2" => $billingAddress->getStreetLine(2),
                "city" => $billingAddress->getCity(),
                "postcode" => $billingAddress->getPostcode(),
                "country" => $billingAddress->getCountryId()
            ],
            "payment" => [
                "amount" => (float) $order->getGrandTotalAmount() * 100, // Amount in cents
                "currency" => $order->getCurrencyCode()
            ],
            "items" => array_map(function($item) {
                return [
                    "name" => $item->getName(),
                    "quantity" => (int) $item->getQtyOrdered(),
                    "price" => (float) $item->getPrice()
                ];
            }, $order->getItems()),
            "response" => ["url" => $returnUrl],
            "notify" => ["url" => $webhookUrl],
            "metadata" => ["magento_order_id" => $order->getEntityId()]
        ];

        // This is the payload that will be passed to the TransferFactory
        return ['payload' => $payload];
    }
}