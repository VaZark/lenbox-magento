define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/redirect-on-success',
        'mage/url'
    ],
    function (Component, redirectOnSuccessAction, url) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Lenbox_CbnxPayment/payment/standard'
            },
            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },
            getInstructions: function () {
                return window.checkoutConfig.payment.lenbox_instructions;
            },
            afterPlaceOrder: function () {
                if(window.checkoutConfig.payment.lenbox_checkout_mode == "3") {
                    redirectOnSuccessAction.redirectUrl = url.build('lenbox/standard/redirect');
                    this.redirectAfterPlaceOrder = true;
                }
            },
        });
    }
);
