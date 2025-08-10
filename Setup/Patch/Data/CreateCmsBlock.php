<?php
namespace Qwicpay\Checkout\Setup\Patch\Data;

use Magento\Cms\Model\BlockFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\Store;

class CreateCmsBlock implements DataPatchInterface
{
    protected $blockFactory;

    public function __construct(BlockFactory $blockFactory)
    {
        $this->blockFactory = $blockFactory;
    }

    public function apply()
{
    $block = $this->blockFactory->create()->load('qwicpay_checkout_button', 'identifier');
    if (!$block->getId()) {
        $this->blockFactory->create()->setData([
            'title' => 'QwicPay Checkout Button',
            'identifier' => 'qwicpay_checkout_button',
            'content' => '{{block class="Qwicpay\\Checkout\\Block\\CheckoutButton" template="Qwicpay_Checkout::button.phtml"}}',
            'is_active' => 1,
            'stores' => [\Magento\Store\Model\Store::DEFAULT_STORE_ID]
        ])->save();
    }
}

    public static function getDependencies() { return []; }
    public function getAliases() { return []; }
}