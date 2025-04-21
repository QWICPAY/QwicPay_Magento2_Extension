<?php
namespace QwicPay\Payment\Model;

use Magento\Payment\Model\Method\AbstractMethod;

class QwicPay extends AbstractMethod
{
    protected $_code = 'qwicpay';

    // Disable capture, refund, etc.
    protected $_isOffline = true;
    protected $_canAuthorize = false;
    protected $_canCapture = false;
    protected $_canRefund = false;
    protected $_canUseCheckout = false;
    protected $_canUseInternal = true;
    protected $_canUseForMultishipping = false;
}
