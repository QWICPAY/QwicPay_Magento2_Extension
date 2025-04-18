<?php

namespace Qwicpay\Checkout\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Stage implements ArrayInterface
{
    const STAGE_TEST = 'TEST';
    const STAGE_LIVE = 'PROD';

    public function toOptionArray()
    {
        return [
            ['value' => self::STAGE_TEST, 'label' => __('Test')],
            ['value' => self::STAGE_LIVE, 'label' => __('Live')]
        ];
    }
}
