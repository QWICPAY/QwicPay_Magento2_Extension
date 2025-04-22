<?php
namespace Qwicpay\Checkout\Model;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Api\Data\CartInterface;

class QwicPay extends AbstractMethod
{
    protected $_code = 'qwicpay';
    protected $_isOffline = true;
    protected $_canUseCheckout = false; // Hides from standard frontend
    protected $_canUseInternal = false; // Hides from admin

    public function isAvailable(CartInterface $quote = null)
    {
        // Allow only when accessed via REST API (e.g., /rest/V1/guest-carts)
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/rest/') !== false) {
            return true;
        }

        // Otherwise, not available
        return false;
    }
}
