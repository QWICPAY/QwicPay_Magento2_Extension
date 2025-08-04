<?php
namespace Qwicpay\Checkout\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    public function getConfig()
    {
        return [
            'payment' => [
                'qwicpay_one' => [
                    'title' => __('QwicPay ONE'),
                    'icon' => 'https://cdn.qwicpay.com/icons/qwicpay-logo.svg'
                ]
            ]
        ];
    }
}
