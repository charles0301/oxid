$(document).ready(function() {
    $('.novalnet_paypal_new_acc').hide();
    if($('#novalnet_paypal_new_details').length && $('#novalnet_paypal_new_details').val() == 1) {
        $('.novalnet_paypal_new_acc').show();
        $('.novalnet_paypal_ref_acc').hide();
    }
});

/**
 * Manages the onclick shopping form
 */
function changePaypalAccountType(event, accType)
{
    var currentAccType = event.target.id;
    $('.' + currentAccType).hide();
    $('.' + accType).show();
    if (accType == 'novalnet_paypal_new_acc')
        $('#novalnet_paypal_new_details').val(1);
    else
        $('#novalnet_paypal_new_details').val(0);
}
