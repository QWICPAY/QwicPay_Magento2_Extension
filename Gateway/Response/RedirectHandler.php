<?php
// File: app/code/Qwicpay/Checkout/Gateway/Response/RedirectHandler.php
namespace Qwicpay\Checkout\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Psr\Log\LoggerInterface;

class RedirectHandler implements HandlerInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * RedirectHandler constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Handles the redirect URL from the gateway response.
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {

        $this->logger->info('Qwicpay RedirectHandler: Response received -> ' . json_encode($response));

        if (!isset($response['url'])) {
            $this->logger->critical('Qwicpay RedirectHandler: Redirect URL is missing from the response.');
            throw new \InvalidArgumentException('No redirect URL from QwicPay');
        }

        $paymentDataObject = SubjectReader::readPayment($handlingSubject);
        $payment = $paymentDataObject->getPayment();
        
        $redirectUrl = $response['url'];

        $payment->setAdditionalInformation('redirect_url', $redirectUrl);
        $payment->setAdditionalInformation('order_place_redirect_url', $redirectUrl);
        $this->logger->info('Qwicpay RedirectHandler: Saved redirect URL ' . $redirectUrl . ' to order.');

        $this->logger->info('Qwicpay RedirectHandler: Handler finished.');
    }
}
