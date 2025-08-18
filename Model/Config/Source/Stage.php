<?php
namespace Qwicpay\Checkout\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Stage implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'TEST', 'label' => __('Test')],
            ['value' => 'PROD', 'label' => __('Production')],
        ];
    }
}
