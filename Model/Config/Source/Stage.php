<?php

namespace Qwicpay\Checkout\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Stage implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'TEST', 'label' => __('Test')],
            ['value' => 'PROD', 'label' => __('Production')],
        ];
    }
}
