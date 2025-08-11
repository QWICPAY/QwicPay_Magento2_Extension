<?php
namespace Qwicpay\Checkout\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class RedirectHandler implements HandlerInterface
{
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($response['url'])) {
            throw new \InvalidArgumentException('No redirect URL from QwicPay');
        }

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $handlingSubject['payment']->getPayment();
        $payment->setAdditionalInformation('redirect_url', $response['url']);
    }
}
