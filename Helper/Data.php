<?php
namespace Qwicpay\Checkout\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Data extends AbstractHelper
{
    const XML_PATH_MERCHANT_ID = 'qwicpay/general/merchant_id';
    const XML_PATH_STAGE = 'qwicpay/general/stage';
    const XML_PATH_BUTTON = 'qwicpay/general/button';

    protected $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get the QwicPay Merchant ID from configuration.
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getMerchantId($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_MERCHANT_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get the QwicPay Stage setting (Test or Production) from configuration.
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getStage($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_STAGE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get the QwicPay Button from configuration.
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getButton($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_BUTTON,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }


}