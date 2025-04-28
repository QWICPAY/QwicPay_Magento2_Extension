<?php
namespace Qwicpay\Checkout\Controller\Guest;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Track extends Action
{
    protected $resultFactory;

    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $email = $this->getRequest()->getParam('email');
        $lastname = $this->getRequest()->getParam('lastname');

        if (!$orderId || !$email || !$lastname) {
            // Redirect to homepage if missing info
            return $this->_redirect('/');
        }

        $html = '<html><body>';
        $html .= '<form id="trackform" action="' . $this->_url->getUrl('sales/guest/view') . '" method="POST">';
        $html .= '<input type="hidden" name="oar_order_id" value="' . htmlspecialchars($orderId) . '" />';
        $html .= '<input type="hidden" name="oar_billing_lastname" value="' . htmlspecialchars($lastname) . '" />';
        $html .= '<input type="hidden" name="oar_type" value="email" />';
        $html .= '<input type="hidden" name="oar_email" value="' . htmlspecialchars($email) . '" />';
        $html .= '</form>';
        $html .= '<script type="text/javascript">document.getElementById("trackform").submit();</script>';
        $html .= '</body></html>';

        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        return $resultRaw->setHeader('Content-Type', 'text/html')->setContents($html);
    }
}
