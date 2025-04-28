<?php
namespace Qwicpay\Checkout\Model;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Sales\Model\Order;

class QwicPay extends AbstractMethod
{
    protected $_code = 'qwicpay';
    protected $_isOffline = true;
    protected $_canUseCheckout = true;
    protected $_canUseInternal = false;
    protected $_canAuthorize = true;

    protected $request;
    protected $curl;

    protected $validIps = null;

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
        $clientIp = $this->request->getClientIp();

        // Only allow if request is from REST API
        if (strpos($path, '/rest/') === false && strpos($path, '/V1/') === false) {
            return false;
        }

        // Check if IP is valid
        if (!$this->isValidIp($clientIp)) {
            return false;
        }

        return true;
    }

    public function isValidIp($ip)
    {
        if ($this->validIps === null) {
            try {
                $this->curl->get('https://ice.qwicpay.com/paymentips');
                $response = $this->curl->getBody();
                $data = json_decode($response, true);
                if (isset($data['iplist']) && is_array($data['iplist'])) {
                    $this->validIps = $data['iplist'];
                } else {
                    $this->validIps = [];
                }
            } catch (\Exception $e) {
                // If any error fetching IP list, default to empty list
                $this->validIps = [];
            }
        }

        return in_array($ip, $this->validIps);
    }

    /**
     * Force order to 'processing' instead of 'pending'
     */
    public function getOrderPlaceRedirectUrl()
    {
        return null; // No redirect needed
    }

    public function authorize(InfoInterface $payment, $amount)
    {
        $order = $payment->getOrder();
        if ($order) {
            $order->setState(Order::STATE_PROCESSING)
                  ->setStatus(Order::STATE_PROCESSING); // or use custom status if needed
        }
        return $this;
    }
}
