<?php
namespace Qwicpay\Checkout\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Qwicpay\Checkout\Helper\Data as QwicpayHelper;
use Magento\Framework\Controller\Result\JsonFactory;

class CartInfo extends Action
{
    protected $checkoutSession;
    protected $helper;
    protected $resultJsonFactory;

    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        QwicpayHelper $helper,
        JsonFactory $resultJsonFactory
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        $quote = $this->checkoutSession->getQuote();
        $cartId = $quote ? $quote->getId() : null;

        return $result->setData([
            'cartId' => $cartId,
            'merchantId' => $this->helper->getMerchantId(),
            
        ]);
    }
}
