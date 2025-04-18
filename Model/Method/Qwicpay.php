<?php
namespace Qwicpay\Checkout\Model\Method;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Adapter;
use Magento\Framework\Phrase;
use Magento\Quote\Api\Data\CartInterface; // Add this use statement

class Qwicpay extends AbstractMethod
{
    const PAYMENT_METHOD_QWICPAY_CODE = 'qwicpay'; // Payment method code

    protected $_code = self::PAYMENT_METHOD_QWICPAY_CODE;
    protected $_isOffline = true; // Mark it as offline since the payment is done externally
    protected $_canUseForMultishipping = false; // Disable multi-shipping orders for QwicPay

    /**
     * Check if the payment method can be used in checkout.
     *
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(CartInterface $quote = null)
    {
        // Disable for frontend checkout but allow API usage
        return false; // The payment method should not be available for frontend use
    }

    /**
     * Get the title of the payment method.
     *
     * @return Phrase
     */
    public function getTitle()
    {
        return __('QwicPay');
    }

    /**
     * This is a placeholder method to signify a payment has been chosen externally (on QwicPay).
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return \Magento\Payment\Model\Method\Adapter
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        // This method doesn't do anything in Magento since the payment is completed externally
        return $this;
    }

    /**
     * Capture method for the payment (again, not necessary for actual payment since it is done externally).
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return \Magento\Payment\Model\Method\Adapter
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        // This method doesn't do anything in Magento since the payment is completed externally
        return $this;
    }
}