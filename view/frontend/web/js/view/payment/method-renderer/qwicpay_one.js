/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define([
  "jquery",
  "Magento_Checkout/js/view/payment/default",
  "Magento_Checkout/js/action/place-order",
  "Magento_Checkout/js/action/select-payment-method",
  "Magento_Checkout/js/model/payment/additional-validators",
  "mage/url",
  "Magento_Customer/js/model/customer",
  "Magento_Checkout/js/model/quote",
  "Magento_Checkout/js/model/error-processor",
  "Magento_Checkout/js/model/full-screen-loader",
  "Magento_Checkout/js/model/payment-service",
], function (
  $,
  Component,
  placeOrderAction,
  selectPaymentMethodAction,
  additionalValidators,
  url,
  customer,
  quote,
  errorProcessor,
  fullScreenLoader,
  paymentService
) {
  "use strict";

  return Component.extend({
    defaults: {
      template: "Qwicpay_Checkout/payment/form",
    },

    // A flag to check if the payment method is selected
    isPaymentSelected: function () {
      return quote.paymentMethod()
        ? quote.paymentMethod().method === this.item.method
        : false;
    },


    /**
     * This function is called after the place order action, but in our case,
     * it's called directly by our overridden placeOrder function.
     */
    placeOrder: function () {
      var self = this;
      fullScreenLoader.startLoader();

      placeOrderAction(this.getData(), this.messageContainer)
        .done(function (orderId) {
          window.location.replace(
            "/qwicpay/redirect/index?order_id=" + orderId
          );
        })
        .fail(function (response) {
          // ... error handling
        })
        .always(function () {
          fullScreenLoader.stopLoader();
        });
    },

    getCode: function () {
      return "qwicpay_one";
    },

    getData: function () {
      return {
        method: this.item.method,
        additional_data: {
          // Any additional data you need to pass can go here
        },
      };
    },
  });
});
