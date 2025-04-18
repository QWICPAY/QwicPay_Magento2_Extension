<?php

namespace Qwicpay\Checkout\Controller\Adminhtml\Dashboard;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    protected $resultPageFactory;

    public function __construct(Action\Context $context, PageFactory $resultPageFactory)
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Qwicpay_Checkout::qwicpay_dashboard');
        $resultPage->getConfig()->getTitle()->prepend(__('QwicPay Dashboard'));
        return $resultPage;
    }
}
