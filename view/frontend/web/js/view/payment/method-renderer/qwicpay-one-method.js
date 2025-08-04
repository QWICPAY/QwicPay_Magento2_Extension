define([
    'Magento_Checkout/js/view/payment/default'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Qwicpay_Checkout/payment/qwicpay-one'
        },

        getTitle: function () {
            return window.checkoutConfig.payment.qwicpay_one.title || 'QwicPay ONE';
        },

        getIcons: function () {
            const base = 'https://cdn.qwicpay.com/icons/';
            return [
                { src: base + 'visa.svg', alt: 'Visa' },
                { src: base + 'mastercard.svg', alt: 'Mastercard' },
                { src: base + 'apple-pay.svg', alt: 'Apple Pay' },
                { src: base + 'samsung-pay.svg', alt: 'Samsung Pay' }
            ];
        }
    });
});
