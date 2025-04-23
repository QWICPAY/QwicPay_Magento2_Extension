<?php
namespace Qwicpay\Checkout\Model;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Framework\App\State;
use Magento\Framework\Webapi\Rest\Request as RestRequest;

class QwicPay extends AbstractMethod
{
    protected $_code = 'qwicpay';
    protected $_isOffline = true;
    protected $_canUseCheckout = false;
    protected $_canUseInternal = false;

    protected $appState;
    protected $restRequest;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        State $appState,
        RestRequest $restRequest,
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
        $this->appState = $appState;
        $this->restRequest = $restRequest;
    }

    public function isAvailable(CartInterface $quote = null)
    {
        // Check if it's a REST request
        if ($this->appState->getAreaCode() === \Magento\Framework\App\Area::AREA_WEBAPI_REST) {
            return true;
        }

        return false;
    }
}
