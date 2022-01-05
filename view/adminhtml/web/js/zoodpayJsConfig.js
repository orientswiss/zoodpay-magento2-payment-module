define(['jquery'], function($){
    "use strict";

    $("input[value*='API is down']").css("color", "red");
    $("input[value*='API is healthy']").css("color", "green");


if($("#payment_other_zoodpayment_zoodpay_api_health_hidden").val()==1)
{
    $("#payment_other_zoodpayment_zoodpay_api_health").css("color", "green");

}
else
$("#payment_other_zoodpayment_zoodpay_api_health").css("color", "red");

$("#row_payment_other_zoodpayment_zoodpay_api_health_hidden").hide();

});
