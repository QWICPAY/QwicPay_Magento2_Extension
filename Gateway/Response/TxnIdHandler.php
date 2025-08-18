<?php
// File: app/code/Qwicpay/Checkout/Gateway/Response/TxnIdHandler.php
namespace Qwicpay\Checkout\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Psr\Log\LoggerInterface;

class TxnIdHandler implements HandlerInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * TxnIdHandler constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Handles the transaction ID from the gateway response.
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        
        
        $paymentDataObject = SubjectReader::readPayment($handlingSubject);
        $payment = $paymentDataObject->getPayment();
        $payment->setTransactionId(null);

        if (isset($response['transactionid']) && !empty($response['transactionid'])) {
            $transactionId = $response['transactionid'];
            $payment->setTransactionId($transactionId);
            $payment->setIsTransactionClosed(false);
            $this->logger->info('Qwicpay TxnIdHandler: Transaction ID ' . $transactionId . ' saved to order.');
        } else {
            $this->logger->error('Qwicpay TxnIdHandler: Transaction ID is missing from the response.');
        }

        
    }
}
