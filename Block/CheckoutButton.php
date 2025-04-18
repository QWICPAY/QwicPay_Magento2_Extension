<?php
namespace Qwicpay\Checkout\Block;

use Magento\Framework\View\Element\Template;
use Magento\Checkout\Model\Session as CheckoutSession;
use Qwicpay\Checkout\Helper\Data as QwicpayHelper;

class CheckoutButton extends Template
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var QwicpayHelper
     */
    protected $helper;

    /**
     * CheckoutButton constructor.
     *
     * @param Template\Context $context
     * @param CheckoutSession $checkoutSession
     * @param QwicpayHelper $helper
     * @param array $data
     */
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

    /**
     * Get the QwicPay checkout URL.
     * 
     * @return string
     */
    public function getCheckoutUrl()
    {
        $cartId = $this->checkoutSession->getQuote()->getId(); // Get current quote ID
        $merchantId = $this->helper->getMerchantId(); // Get Merchant ID from system config
        $stage = $this->helper->getStage(); // Get Stage from system config (test/live)

        // Construct and return the URL for redirecting to the QwicPay checkout
        return "http://localhost:3000/magento/checkout?cartId={$cartId}&merchantId={$merchantId}&stage={$stage}";
    }
}
