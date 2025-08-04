<?php
namespace Qwicpay\Checkout\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Controller\Result\JsonFactory;

class Callback extends Action
{
    protected $orderRepository;
    protected $scopeConfig;
    protected $resultJsonFactory;

    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        ScopeConfigInterface $scopeConfig,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->scopeConfig = $scopeConfig;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function execute()
    {
        $merchantKey = $this->scopeConfig->getValue('qwicpay/general/merchant_key');
        $incomingKey = $this->getRequest()->getHeader('KEY');

        $data = json_decode($this->getRequest()->getContent(), true);
        $result = $this->resultJsonFactory->create();

        if ($incomingKey !== $merchantKey) {
            return $result->setData(['error' => 'Invalid API Key']);
        }

        $orderNumber = $data['orderNumber'] ?? null;
        if ($orderNumber) {
            $order = $this->orderRepository->get($orderNumber);
            if ($order && $data['payment']['transactionStatus'] == 1) {
                $order->setState(Order::STATE_PROCESSING)
                      ->setStatus(Order::STATE_PROCESSING);
                $this->orderRepository->save($order);
            }
        }

        $redirectUrl = $this->_url->getUrl('checkout/onepage/success');
        return $result->setData(['redirect' => $redirectUrl]);
    }
}
