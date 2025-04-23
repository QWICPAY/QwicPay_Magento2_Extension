<?php
namespace Qwicpay\Checkout\Model;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Framework\App\RequestInterface;

use Magento\Payment\Model\InfoInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;

class QwicPay extends AbstractMethod
{
    protected $_code = 'qwicpay';
    protected $_isOffline = true;
    protected $_canUseCheckout = true;
    protected $_canUseInternal = false;
    protected $_canAuthorize = true;

    protected $request;
    protected $curl;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        RequestInterface $request,
        Curl $curl,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            null,
            null,
            $data
        );
        $this->request = $request;
        $this->curl = $curl;
    }

    public function isAvailable(CartInterface $quote = null)
    {
        $path = $this->request->getPathInfo();

        // Enable only for REST API routes
        if (strpos($path, '/rest/') !== false || strpos($path, '/V1/') !== false) {
            return true;
        }

        return false;
    }

    public function authorize(InfoInterface $payment, $amount)
    {
        $additionalData = $payment->getAdditionalInformation();
        if (!is_array($additionalData) || count($additionalData) < 2) {
            throw new LocalizedException(__('Invalid payment data.'));
        }

        $transactionId = $additionalData[0] ?? null;
        $paymentRef = $additionalData[1] ?? null;
        $merchantId = $additionalData[2] ?? null;

        if (!$paymentRef || !$merchantId) {
            throw new LocalizedException(__('Missing required data.'));
        }

        $this->curl->addHeader("Content-Type", "application/json");
        $this->curl->post("http://localhost:3000/app/magento/redeem", json_encode([
            'merchantId' => $merchantId,
            'paymentRef' => $paymentRef
        ]));

        $response = json_decode($this->curl->getBody(), true);

        if (!isset($response['status']) || $response['status'] != 1) {
            throw new LocalizedException(__('Payment not approved by QwicPay.'));
        }

        $payment->setTransactionId($paymentRef);
        $payment->setIsTransactionClosed(0);

        return $this;
    }
}
