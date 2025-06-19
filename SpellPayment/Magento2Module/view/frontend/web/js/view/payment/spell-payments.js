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
                type: 'spellpayment_checkout',
                component: 'SpellPayment_Magento2Module/js/view/payment/method-renderer/checkout-method'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
