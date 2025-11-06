<?php

namespace Qwicpay\Checkout\Gateway\Command;

use Magento\Payment\Gateway\Command\GatewayCommand;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Psr\Log\LoggerInterface;

/**
 * CustomRedirectCommand handles both the initial payment request (redirect)
 * and the subsequent order creation via callback.
 */
class CustomRedirectCommand extends GatewayCommand implements CommandInterface
{
    /** @var BuilderInterface */
    protected $requestBuilder;

    /** @var ClientInterface */
    protected $client;

    /** @var HandlerInterface|null */
    protected $handler;

    /** @var ValidatorInterface|null */
    protected $validator;

    /** @var LoggerInterface|null */
    protected $logger;

    /** @var TransferFactoryInterface */
    protected $transferFactory;

    /**
     * @param BuilderInterface $requestBuilder
     * @param TransferFactoryInterface $transferFactory
     * @param ClientInterface $client
     * @param HandlerInterface|null $handler
     * @param ValidatorInterface|null $validator
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        BuilderInterface $requestBuilder,
        TransferFactoryInterface $transferFactory,
        ClientInterface $client,
        HandlerInterface $handler = null,
        ValidatorInterface $validator = null,
        LoggerInterface $logger = null
    ) {
        // Pass dependencies to the parent constructor
        parent::__construct($requestBuilder, $transferFactory, $client, $logger, $handler, $validator);

        // Assign properties (though they are now mostly set by the parent)
        $this->requestBuilder = $requestBuilder;
        $this->transferFactory = $transferFactory;
        $this->client = $client;
        $this->handler = $handler;
        $this->validator = $validator;
        $this->logger = $logger;
    }

    /**
     * Executes the command. If the 'is_callback_call' flag is set on the payment,
     * the gateway interaction is skipped.
     *
     * @param array<string, mixed> $commandSubject
     * @return $this
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function execute(array $commandSubject)
    {
        // 1. Read payment object to check for the callback flag
        $paymentDataObject = SubjectReader::readPayment($commandSubject);
        $payment = $paymentDataObject->getPayment();

        // Check if this execution is triggered by the callback controller (post-payment)
        if ($payment->getAdditionalInformation('is_callback_call')) {
            if ($this->logger) {
                $this->logger->info('Qwicpay CustomRedirectCommand: SKIPPING gateway call (Callback detected).');
            }
            
            // Critical: If it's a callback, we have nothing more to do here. 
            // The order placement will proceed without initiating a new redirect.
            return $this; 
        }

        // -------------------------------------------------------------------
        // Initial Checkout Call: Proceed to request a redirect URL
        // -------------------------------------------------------------------

        if ($this->logger) {
            $this->logger->info('Qwicpay CustomRedirectCommand: execute() called (Initial checkout).');
        }

        // Build request payload (e.g., amount, quote ID, callback URLs)
        $request = $this->requestBuilder->build($commandSubject);
        if ($this->logger) {
            $this->logger->info('Qwicpay CustomRedirectCommand: request built', ['request' => $request]);
        }

        // Use transfer factory
        $transfer = $this->transferFactory->create($request);
        if ($this->logger) {
            $this->logger->info('Qwicpay CustomRedirectCommand: transfer object created');
        }

        // Send request to Qwicpay API
        $response = $this->client->placeRequest($transfer);
        if ($this->logger) {
            $this->logger->info('Qwicpay CustomRedirectCommand: response received', ['response' => (array) $response]);
        }
        
        $validationSubject = ['response' => $response];
        
        // Validate the response (e.g., check for success status, URL presence)
        if ($this->validator) {
            $result = $this->validator->validate($validationSubject);
            if (!$result->isValid()) {
                // Log failure details
                if ($this->logger) {
                    $this->logger->critical('Qwicpay CustomRedirectCommand: Validation failed.', ['errors' => $result->getFailsDescription()]);
                }
                
                throw new \Magento\Payment\Gateway\Command\CommandException(
                    __('Payment processing failed. Please try a different payment method or contact support.')
                );
            }
        }

        // Handle the response (e.g., set redirect URL in payment additional info)
        if ($this->handler) {
            $this->handler->handle($commandSubject, $response);
        }

        return $this;
    }
}
