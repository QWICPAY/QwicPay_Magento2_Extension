<?php
namespace Qwicpay\Checkout\Model;

use Magento\Payment\Model\Method\AbstractMethod;

class QwicPay extends AbstractMethod
{
    protected $_code = 'qwicpay';
    protected $_isOffline = true;
    protected $_canUseCheckout = false;
    protected $_canUseInternal = true;
}
