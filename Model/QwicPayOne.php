<?php
namespace Qwicpay\Checkout\Model;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;

class QwicPayOne extends AbstractMethod
{
    protected $_code = 'qwicpay_one';
    protected $_isOffline = true;
    protected $_canUseCheckout = true;
    protected $_canUseInternal = false;
    protected $_canAuthorize = true;
    protected $_isActive = true;

    public function isAvailable(CartInterface $quote = null)
    {
        return true;
    }

    public function authorize(InfoInterface $payment, $amount)
    {
        $order = $payment->getOrder();
        if ($order) {
            $order->setState(Order::STATE_PENDING_PAYMENT)
                  ->setStatus(Order::STATE_PENDING_PAYMENT);
        }
        return $this;
    }
}
