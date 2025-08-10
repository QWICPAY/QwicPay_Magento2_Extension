<?php
namespace Qwicpay\Checkout\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'qwicpay_one';

    public function getConfig()
    {
    return [
        'payment' => [
            'qwicpay_one' => [
                'redirectUrl' => 'https://example.com/redirect',
                'title'       => 'QwicPay ONE',
                'isActive'    => true,
            ]
        ]
    ];
}
}
