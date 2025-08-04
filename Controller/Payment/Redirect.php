<?php
namespace Qwicpay\Checkout\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Exception\LocalizedException;

class Redirect extends Action
{
    protected $orderRepository;
    protected $resultRedirectFactory;
    protected $scopeConfig;
    protected $curl;

    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        RedirectFactory $resultRedirectFactory,
        ScopeConfigInterface $scopeConfig,
        Curl $curl
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->scopeConfig = $scopeConfig;
        $this->curl = $curl;
    }

    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $order = $this->orderRepository->get($orderId);

        $merchantId = $this->scopeConfig->getValue('qwicpay/general/merchant_id');
        $merchantKey = $this->scopeConfig->getValue('qwicpay/general/merchant_key');
        $stage = $this->scopeConfig->getValue('qwicpay/general/stage');

        $payload = [
            'platform' => 'MAGENTO',
            'stage' => $stage,
            'orderNumber' => $order->getIncrementId(),
            'user' => [
                'name' => $order->getCustomerFirstname(),
                'surname' => $order->getCustomerLastname(),
                'email' => $order->getCustomerEmail(),
                'cell' => $order->getBillingAddress()->getTelephone()
            ],
            'billing' => [
                'street' => implode(' ', $order->getBillingAddress()->getStreet()),
                'city' => $order->getBillingAddress()->getCity(),
                'postalCode' => $order->getBillingAddress()->getPostcode(),
                'country' => $order->getBillingAddress()->getCountryId()
            ],
            'payment' => [
                'amount' => $order->getGrandTotal(),
                'currency' => $order->getOrderCurrencyCode()
            ],
            'items' => [], // Add if you want line items
            'response' => [
                'url' => $this->_url->getUrl('qwicpay/payment/callback')
            ]
        ];

        try {
            $this->curl->addHeader("MERCHANT_ID", $merchantId);
            $this->curl->addHeader("MERCHANT_KEY", $merchantKey);
            $this->curl->post('https://ice.qwicpay.com/one/merchant/payment', json_encode($payload));

            $response = json_decode($this->curl->getBody(), true);
            if (isset($response['url'])) {
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setUrl($response['url']);
                return $resultRedirect;
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__('Payment redirect failed: %1', $e->getMessage()));
        }

        throw new LocalizedException(__('Unable to create payment session.'));
    }
}
