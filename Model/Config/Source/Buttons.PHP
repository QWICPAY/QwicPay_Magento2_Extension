<?php

namespace Qwicpay\Checkout\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Buttons implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'https://cdn.qwicpay.com/qwicpay/buttons/BlueBGWhiteText.svg', 'label' => __('Blue Background with White Text (Rounded - Recommended)')],
            ['value' => 'https://cdn.qwicpay.com/qwicpay/buttons/BlueBGWhiteText%20_Squared.svg', 'label' => __('Blue Background with White Text (Squared)')],
            ['value' => 'https://cdn.qwicpay.com/qwicpay/buttons/WhiteBGBlueText%20_Squared.svg', 'label' => __('White Background with Blue Text (Squared)')],
            ['value' => 'https://cdn.qwicpay.com/qwicpay/buttons/WhiteBGBlueText.svg', 'label' => __('White Background with Blue Text (Rounded)')],
            
        ];
    }
}
