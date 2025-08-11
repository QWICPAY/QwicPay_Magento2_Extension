<?php
namespace Qwicpay\Checkout\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;

class Redirect extends Action
{
    protected $orderRepository;
    protected $resultRedirectFactory;
    protected $commandPool;

    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        RedirectFactory $resultRedirectFactory,
        CommandPoolInterface $commandPool
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->commandPool = $commandPool;
    }

    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $order = $this->orderRepository->get($orderId);

        // Build payment data array to pass to gateway command
        $paymentData = [
            'order' => $order,
            'billing' => $order->getBillingAddress(),
            'customer' => $order->getCustomer(),
            // Add other necessary data your command expects
        ];

        try {
            // Execute the redirect command
            $commandResult = $this->commandPool->get('redirect')->execute($paymentData);

            if (isset($commandResult['redirect'])) {
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setUrl($commandResult['redirect']);
                return $resultRedirect;
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__('Payment redirect failed: %1', $e->getMessage()));
        }

        throw new LocalizedException(__('Unable to create payment session.'));
    }
}
