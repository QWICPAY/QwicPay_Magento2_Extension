<?php
namespace Qwicpay\Checkout\Gateway\Command;

use Magento\Payment\Gateway\Command\GatewayCommand;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Magento\Payment\Gateway\CommandInterface;
use Psr\Log\LoggerInterface;

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


    public function __construct(
    BuilderInterface $requestBuilder,
    TransferFactoryInterface $transferFactory,
    ClientInterface $client,
    HandlerInterface $handler = null,
    ValidatorInterface $validator = null,
    LoggerInterface $logger = null
) {
    parent::__construct($requestBuilder, $transferFactory, $client, $logger, $handler, $validator);

    $this->requestBuilder = $requestBuilder;
    $this->transferFactory = $transferFactory;
    $this->client = $client;
    $this->handler = $handler;
    $this->validator = $validator;
    $this->logger = $logger;
}

    public function execute(array $commandSubject)
    {
        $this->logger?->info('Qwicpay CustomRedirectCommand: execute() called');

        // Build request payload
        $request = $this->requestBuilder->build($commandSubject);
        $this->logger?->info('Qwicpay CustomRedirectCommand: request built', ['request' => $request]);

        // Use transfer factory
        $transfer = $this->transferFactory->create($request);
        $this->logger?->info('Qwicpay CustomRedirectCommand: transfer object created', ['transfer' => (array) $transfer]);

        // Send request
        $response = $this->client->placeRequest($transfer);
        $this->logger?->info('Qwicpay CustomRedirectCommand: response received', ['response' => (array) $response]);
        $validationSubject = ['response' => $response];
        // Validate
        if ($this->validator) {
            $result = $this->validator->validate($validationSubject);
            if (!$result->isValid()) {
                throw new \Magento\Payment\Gateway\Command\CommandException(
                    __('Transaction has been declined. Please try again later.')
                );
            }
        }

        // Handle
        if ($this->handler) {
            $this->handler->handle($commandSubject, $response);
        }

        return $this;
    }

}
