<?php
// File: app/code/Qwicpay/Checkout/Gateway/Validator/ResponseCodeValidator.php
namespace Qwicpay\Checkout\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Psr\Log\LoggerInterface;

class ResponseCodeValidator extends AbstractValidator
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * ResponseCodeValidator constructor.
     * @param ResultInterfaceFactory $resultFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        // The parent constructor only expects the ResultInterfaceFactory parameter.
        parent::__construct($resultFactory);
    }

    /**
     * Performs validation of the HTTP client's result.
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        
        $this->logger->info('Qwicpay Validator: Validation subject -> ' . json_encode($validationSubject));

        if (!isset($validationSubject['response']) || !is_array($validationSubject['response'])) {
            $this->logger->critical('Qwicpay Validator: Invalid response from gateway. Response subject is missing or not an array.');
            return $this->createResult(false, [__('Qwicpay API did not return a valid response.')]);
        }

        $response = $validationSubject['response'];
        

        $isValid = false;
        

        // Check for 'url' key, which indicates a successful transaction creation
        if (isset($response['url'])) {
            $isValid = true;
            $this->logger->info('Qwicpay Validator: "url" key found. Validation is now ' . ($isValid ? 'true' : 'false'));
        }

        // QwicPay returns "CREATED" for a successful request that results in a redirect URL.
        if (isset($response['status']) && $response['status'] === 'CREATED') {
            $isValid = true;
            $this->logger->info('Qwicpay Validator: "status" is "CREATED". Validation is now ' . ($isValid ? 'true' : 'false'));
        }

        // Return a declined message if neither a redirect URL nor a "CREATED" status is found.
        if (!$isValid) {
            $this->logger->error('Qwicpay Validator: Final validation check failed.');
            $errorMessages = [];
            if (isset($response['errors']) && is_array($response['errors'])) {
                $errorMessages = $response['errors'];
            }
            $this->logger->error('Qwicpay Validator: Error messages from response -> ' . json_encode($errorMessages));

            return $this->createResult(
                false,
                [__('QwicPay API returned an error: %1', implode(', ', $errorMessages))]
            );
        }

        $this->logger->info('Qwicpay Validator: Final validation passed successfully.');
        return $this->createResult($isValid);
    }
}