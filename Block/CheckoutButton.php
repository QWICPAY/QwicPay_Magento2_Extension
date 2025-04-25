<?php
namespace Qwicpay\Checkout\Block;

use Magento\Framework\View\Element\Template;
use Magento\Checkout\Model\Session as CheckoutSession;
use Qwicpay\Checkout\Helper\Data as QwicpayHelper;

class CheckoutButton extends Template
{
    protected $checkoutSession;
    protected $helper;

    public function __construct(
        Template\Context $context,
        CheckoutSession $checkoutSession,
        QwicpayHelper $helper,
        array $data = []
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

  

    public function getButtonUrl()
    {
        $url = $this->helper->getButton();
        return $url;
    }

    /**
     * Check if QwicPay service is up for this merchant.
     *
     * @return bool
     */
    public function isQwicpayAvailable(): bool
    {
        $merchantId = $this->helper->getMerchantId();
        $url = "https://ice.qwicpay.com/isup/{$merchantId}";

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    public function getBaseCheckoutUrl()
    {
    return "http://localhost:3000/app/magento/checkout";
    }

}
