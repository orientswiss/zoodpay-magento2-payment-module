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
                type: 'zoodpayment',
                component: 'OrientSwiss_ZoodPay/js/view/payment/method-renderer/zoodpayment'
            }
        );
        return Component.extend({});
    }
);
