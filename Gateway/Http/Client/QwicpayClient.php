<?php
namespace Qwicpay\Checkout\Gateway\Http\Client;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class QwicpayClient implements ClientInterface
{
    protected $curl;
    protected $logger;

    public function __construct(
        Curl $curl,
        LoggerInterface $logger
    ) {
        $this->curl = $curl;
        $this->logger = $logger;
    }

    /**
     * Places the request to the payment gateway.
     *
     * @param TransferInterface $transferObject
     * @return array
     * @throws LocalizedException
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $endpoint = "https://ice.qwicpay.com/one/merchant/payment"; // change to PROD in live mode
        $body = json_encode($transferObject->getBody()['payload']);

        $merchantId = $transferObject->getBody()['merchant_id'];
        $merchantKey = $transferObject->getBody()['merchant_key'];
        
        $this->logger->info("Qwicpay Request Endpoint: " . $endpoint);
        $this->logger->info("Qwicpay Request Body: " . $body);

        $this->curl->addHeader("Content-Type", "application/json");
        $this->curl->addHeader("MERCHANT_ID", $merchantId);
        $this->curl->addHeader("MERCHANT_KEY", $merchantKey);

        $this->curl->post($endpoint, $body);
        $rawResponse = $this->curl->getBody();
        $response = json_decode($rawResponse, true);
        
        $this->logger->info("Qwicpay Raw Response: " . $rawResponse);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new LocalizedException(__('QwicPay API returned an invalid response: %1', $rawResponse));
        }

        if (!isset($response['url'])) {
            throw new LocalizedException(__('QwicPay API did not return a redirect URL. Full response: %1', json_encode($response)));
        }

        return $response;
    }
}
