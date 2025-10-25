<?php
namespace Qwicpay\Checkout\Controller\Redirect;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Psr\Log\LoggerInterface;

class Index extends Action
{
    private $checkoutSession;
    private $quoteRepository;
    private $quoteIdMaskFactory;
    private $logger;
    private $paymentMethod;

    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $quoteRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->logger = $logger;

        $this->paymentMethod = $this->_objectManager->get('QwicpayOneGatewayFacade');
    }

    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $maskedId = $this->getRequest()->getParam('quote_id');

        if (!$maskedId) {
            throw new \Exception('Missing quote_id parameter.');
        }

        try {
            // Convert masked ID → real ID
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($maskedId, 'masked_id');
            if (!$quoteIdMask->getQuoteId()) {
                throw new \Exception('Invalid quote ID.');
            }

            $realQuoteId = $quoteIdMask->getQuoteId();
            $quote = $this->quoteRepository->get($realQuoteId);

            if (!$quote->getIsActive()) {
                throw new \Exception('Quote is not active.');
            }

            // Set session quote
            $this->checkoutSession->replaceQuote($quote);

            // Set payment method
            $payment = $quote->getPayment();
            $payment->setMethod(\Qwicpay\Checkout\Model\Ui\ConfigProvider::CODE);

            // Set info instance
            $this->paymentMethod->setInfoInstance($payment);

            // Run initialize command
            $this->paymentMethod->initialize('authorize', new \Magento\Framework\DataObject());

            // Get redirect URL
            $redirectUrl = $payment->getAdditionalInformation('order_place_redirect_url');

            if (!$redirectUrl) {
                throw new \Exception('No redirect URL from QwicPay.');
            }

            $resultRedirect->setUrl($redirectUrl);
            $this->logger->info('Qwicpay redirecting to: ' . $redirectUrl);

        } catch (\Exception $e) {
            $this->logger->critical('Qwicpay redirect error: ' . $e->getMessage());
            $this->messageManager->addErrorMessage('Payment failed. Please try again.');
            $resultRedirect->setPath('checkout/cart');
        }

        return $resultRedirect;
    }
}