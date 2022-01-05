define(
    [

        'ko',
        'jquery',
        'Magento_Checkout/js/model/totals',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/view/payment/default',
        'OrientSwiss_ZoodPay/js/action/set-payment-method-action',
        'jquery/jquery.cookie',
        'jquery/jquery-storageapi',
        'mage/cookies',

    ],
    function (ko, $, totals,quote ,Component,  setPaymentMethodAction ) {
        'use strict';

        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false,
                template: 'OrientSwiss_ZoodPay/payment/zoodpayment.html',
                selectedServiceJson : ko.observable(0),
                zoodpaySelectedService:ko.observable(false),
                serviceCounter : 0

            },

            zoodpayTermsChecked: ko.observable(false),

            selectedServiceCheck: ko.observable(''),


            selectedServiceClick : function (data,event) {
                
                var service_data={"service_code":data.service_code,"service_type":data.service_type};

                //Setting cookie The Value of Selected Service by User

                this.serviceCounter++;
                if( this.serviceCounter > (window.checkoutConfig.payment.zoodpayment.zoodpayAvailableService).length)
                {

                    $.mage.cookies.set('selected_service',JSON.stringify(service_data));
                    this.zoodpaySelectedService(true);
                }
            },



            getCurrentLocale: function(){
                //console.log(window.checkoutConfig.payment.zoodpayment.zoodpayLan);
                return  window.checkoutConfig.payment.zoodpayment.zoodpayLan;

            },


            getzoodpayAvailableService: function() {



                let zas = window.checkoutConfig.payment.zoodpayment.zoodpayAvailableService

                for (var i = 0; i < zas.length; ++i)
                {


                    if(zas[i]['service_installment_bool'])
                    {
                      let   total = 0;
                        if (totals.totals._latestValue['base_grand_total'] > 0){
                            total = totals.totals._latestValue['base_grand_total'];
                        }
                        else {
                            total = window.checkoutConfig.quoteData.base_grand_total
                        }

                       if(total>0)
                       {
                           let monthlyPayment = total/zas[i]['service_installment']
                          // zas[i]['service_monthly_text'] = zas[i]['service_installment'] + " Monthly1111 " + zas[i]['service_type'] + " of " + monthlyPayment.toFixed(2)+" "+ totals.totals._latestValue['quote_currency_code']+ " With ZoodPay " + "("+zas[i]['service_code']+")";
                           zas[i]['service_monthly_text'] =  zas[i]['service_type'] +' '+ zas[i]['service_of']+' '+ monthlyPayment.toFixed(2)+" "+ totals.totals._latestValue['quote_currency_code'];
                       }



                    }


                }

                return ko.observableArray(zas);




                // return  ko.observableArray( window.checkoutConfig.payment.zoodpayment.zoodpayAvailableService);
               // return  ko.observableArray( window.checkoutConfig.payment.zoodpayment.zoodpayAvailableService);
            },



            getzoodpayTCURL: function() {
                return  window.checkoutConfig.payment.zoodpayment.zoodpayTCURL;
            },

            getzoodpayCallBackURL: function() {
                return  window.checkoutConfig.payment.zoodpayment.zoodpayCallBackURL;
            },


            afterPlaceOrder: function () {


                setPaymentMethodAction(this.messageContainer);
                return false;
            }

        });
    }
);




