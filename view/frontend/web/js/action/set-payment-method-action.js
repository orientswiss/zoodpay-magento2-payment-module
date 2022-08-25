define(
    [
        'jquery',
        'mage/storage',
        'jquery/jquery.cookie',
        'jquery/jquery-storageapi',
        'mage/cookies',
        'mage/url'

    ],

    function ( $  )


    {
        'use strict';


        return function (messageContainer) {


            /*
                Redirect to  ZoodPay Payment UI
             */

            $.mage.redirect(window.checkoutConfig.payment.zoodpayment.zoodpayCallBackURL);
        };
    }
);