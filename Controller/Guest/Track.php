<?php
namespace Qwicpay\Checkout\Controller\Guest;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Controller\ResultFactory;

class Track extends Action
{
    protected $formKey;

    public function __construct(
        Context $context,
        FormKey $formKey
    ) {
        parent::__construct($context);
        $this->formKey = $formKey;
    }

    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $email = $this->getRequest()->getParam('email');
        $lastname = $this->getRequest()->getParam('lastname');
        $zip = $this->getRequest()->getParam('zip');

        if (!$orderId || !$email || !$lastname) {
            return $this->_redirect('/');
        }

        $formKey = $this->formKey->getFormKey(); // Generate a valid form_key

        $html = '<html><body>';
        $html .= '<form id="trackform" action="' . $this->_url->getUrl('sales/guest/view') . '" method="POST">';
        $html .= '<input type="hidden" name="form_key" value="' . htmlspecialchars($formKey) . '" />';
        $html .= '<input type="hidden" name="oar_order_id" value="' . htmlspecialchars($orderId) . '" />';
        $html .= '<input type="hidden" name="oar_billing_lastname" value="' . htmlspecialchars($lastname) . '" />';
        $html .= '<input type="hidden" name="oar_email" value="' . htmlspecialchars($email) . '" />';
        $html .= '<input type="hidden" name="oar_zip" value="' . htmlspecialchars($zip) . '" />'; // <-- new!
        $html .= '<input type="hidden" name="oar_type" value="email" />';
        $html .= '</form>';
        $html .= '<script type="text/javascript">document.getElementById("trackform").submit();</script>';
        $html .= '</body></html>';

        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        return $resultRaw->setHeader('Content-Type', 'text/html')->setContents($html);
    }
}
