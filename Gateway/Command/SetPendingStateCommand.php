<?php
namespace Qwicpay\Checkout\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order;

class SetPendingStateCommand implements CommandInterface
{
    public function execute(array $commandSubject)
    {
        $paymentDO = SubjectReader::readPayment($commandSubject);
        $payment = $paymentDO->getPayment();
        $order = $payment->getOrder();

        if ($order && $order->getId()) {
            $order->setState(Order::STATE_PENDING_PAYMENT);
            $order->setStatus(Order::STATE_PENDING_PAYMENT);
            $order->addStatusHistoryComment('Order set to pending_payment by Qwicpay command before redirect.');
            $order->save();
        }

        return null;
    }
}
