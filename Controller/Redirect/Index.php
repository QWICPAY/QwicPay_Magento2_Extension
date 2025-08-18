<?php
// File: app/code/Qwicpay/Checkout/Controller/Redirect/Index.php
namespace Qwicpay\Checkout\Controller\Redirect;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

class Index extends Action
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            // Get the order ID directly from the URL parameter
            $orderId = $this->getRequest()->getParam('order_id');
            
            if (!$orderId) {
                throw new \Exception('No order ID found in the URL.');
            }

            // Use the OrderRepository to load the order by its ID.
            $order = $this->orderRepository->get($orderId);

            if (!$order || !$order->getId()) {
                throw new \Exception('Failed to load order with ID: ' . $orderId);
            }

            $redirectUrl = $order->getPayment()->getAdditionalInformation('redirect_url');

            if (!$redirectUrl) {
                throw new \Exception(
                    'No redirect URL found in payment additional information for order #' . $order->getIncrementId()
                );
            }

            $this->checkoutSession->clearQuote();

            $this->logger->info('Qwicpay Redirecting to: ' . $redirectUrl);
            $resultRedirect->setUrl($redirectUrl);

        } catch (\Exception $e) {
            $this->logger->critical('Qwicpay Redirect Error: ' . $e->getMessage());
            $this->messageManager->addErrorMessage('An error occurred during payment redirection. Please try again.');
            $resultRedirect->setPath('checkout/cart');
        }

        return $resultRedirect;
    }
}
