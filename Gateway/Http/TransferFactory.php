<?php
namespace Qwicpay\Checkout\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

class TransferFactory implements TransferFactoryInterface
{
    protected $transferBuilder;
    protected $scopeConfig;
    protected $logger;

    public function __construct(
        TransferBuilder $transferBuilder,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->transferBuilder = $transferBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    public function create(array $request)
    {
        $merchantId = $this->scopeConfig->getValue('qwicpay/general/merchant_id');
        $merchantKey = $this->scopeConfig->getValue('qwicpay/general/merchant_key');

        $request['merchant_id'] = $merchantId;
        $request['merchant_key'] = $merchantKey;

        // Log the request payload before sending
        $this->logger->info('Qwicpay TransferFactory: Creating request', ['request' => $request]);

        return $this->transferBuilder
            ->setBody($request)
            ->setMethod('POST')
            ->build();
    }
}
