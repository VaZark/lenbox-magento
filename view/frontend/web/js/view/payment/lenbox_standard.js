define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'lenbox_standard',
                component: 'Lenbox_CbnxPayment/js/view/payment/method-renderer/lenbox_standardmethod'
            }
        );
        return Component.extend({});
    }
);