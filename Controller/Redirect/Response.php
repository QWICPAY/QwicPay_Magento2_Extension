<?php
// File: app/code/Qwicpay/Checkout/Controller/Redirect/Response.php
namespace Qwicpay\Checkout\Controller\Redirect;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;

class Response extends Action
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param OrderFactory $orderFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        OrderFactory $orderFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->logger = $logger;
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $this->logger->info('Qwicpay Redirect Response: Execution started.');

        $orderNumber = $this->getRequest()->getParam('orderNumber');
        $this->logger->info('Qwicpay Redirect Response: Received orderNumber: ' . $orderNumber);

        if (!$orderNumber) {
            $this->logger->critical('Qwicpay Redirect Response: No orderNumber found in URL. Redirecting to cart.');
            return $resultRedirect->setPath('checkout/cart');
        }

        // Load order by increment ID
        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);

        if (!$order || !$order->getId()) {
            $this->logger->critical('Qwicpay Redirect Response: Could not find order for increment ID: ' . $orderNumber . '. Redirecting to cart.');
            return $resultRedirect->setPath('checkout/cart');
        }

        $this->logger->info('Qwicpay Redirect Response: Order ' . $orderNumber . ' loaded successfully. Current state: ' . $order->getState());

        // Check the order state to determine redirect
        if ($order->getState() === \Magento\Sales\Model\Order::STATE_PROCESSING) {
            $this->logger->info('Qwicpay Redirect Response: Order state is PROCESSING. Redirecting to success page.');
            
            // Payment succeeded, show thank you page
            $this->checkoutSession->setLastOrderId($order->getId());
            $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->checkoutSession->setLastOrderStatus($order->getStatus());

            $resultRedirect->setPath('checkout/onepage/success');
        } else {
            $this->logger->info('Qwicpay Redirect Response: Order state is ' . $order->getState() . '. Redirecting to cart.');
            // Payment failed or cancelled, go to cart
            $resultRedirect->setPath('checkout/cart');
        }

        return $resultRedirect;
    }
}
