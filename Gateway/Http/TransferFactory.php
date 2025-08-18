<?php
namespace Qwicpay\Checkout\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class TransferFactory implements TransferFactoryInterface
{
    protected $transferBuilder;
    protected $scopeConfig;

    public function __construct(
        TransferBuilder $transferBuilder,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->transferBuilder = $transferBuilder;
        $this->scopeConfig = $scopeConfig;
    }

    public function create(array $request)
    {
        $merchantId = $this->scopeConfig->getValue('qwicpay/general/merchant_id');
        $merchantKey = $this->scopeConfig->getValue('qwicpay/general/merchant_key');

        $request['merchant_id'] = $merchantId;
        $request['merchant_key'] = $merchantKey;

        return $this->transferBuilder
            ->setBody($request)
            ->setMethod('POST')
            ->build();
    }
}
