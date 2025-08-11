<?php
namespace Qwicpay\Checkout\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'qwicpay_one';

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;

    /**
     * @param \Magento\Framework\UrlInterface $urlBuilder
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    // URL the frontend JS will call to start the QwicPay redirect flow
                    'redirectUrl' => $this->urlBuilder->getUrl('qwicpay/redirect/start'),
                    
                    // Optional: pass display labels or config for checkout UI
                    'label'       => __('QwicPay Secure Payment'),
                    'description' => __('Pay securely using QwicPay. You will be redirected to complete your payment.')
                ]
            ]
        ];
    }
}
