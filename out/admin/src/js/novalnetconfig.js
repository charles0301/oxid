/**
 * Toggles the Novalnet payment configuration
 *
 */
function novalnetToggleMe(element)
{
    var paymentConfig = $(element).attr('payment_id');

    if ($('#' + paymentConfig).css('display') == 'none') {
        $('#' + paymentConfig).css('display', 'block');
        $(element).css('background-position', '0 -18px');
    }
    else {
        $('#' + paymentConfig).css('display', 'none');
        $(element).css('background-position', '0 0');
    }
}

$(document).ready(function() {
    setNovalnetConfig();
    $('#novalnet_activation_key').change(function () { setNovalnetConfig(); });
    $('#novalnet_config_submit').click(function (e) {
        e.preventDefault();

        if ($('#ajax_process').attr('value') == 1 ) {
            $('.novalnet_config_form').submit();
        } else {
            $('#ajax_process').attr('value', 2);
        }
        return true;
    });
    $('input[name="aNovalnetConfig[iDueDatenovalnetsepa]"],input[name="aNovalnetConfig[iDueDatenovalnetinvoice]"],input[name="aNovalnetConfig[iDueDatenovalnetbarzahlen]"]').keydown(function (event) {
        if (event.keyCode == 46 || event.keyCode == 8 || event.keyCode == 9 || event.keyCode == 27 || event.keyCode == 13 || (event.keyCode == 65 && event.ctrlKey === true) || (event.keyCode >= 35 && event.keyCode <= 39)) {
            return;
        }
        else {
            if (event.shiftKey || (event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105)) {
            event.preventDefault();
            }
        }
    });
});

/**
 * Sets the Novalnet credentials
 *
 */
function setNovalnetConfig()
{
    var novalnetActivationKey = $.trim($('#novalnet_activation_key').val());
    if (novalnetActivationKey != '') {
        $('#ajax_process').attr('value', 0);
        getMerchantConfigs(novalnetActivationKey);
    }
}
/**
 * Sends the api call to get the vendor credentials
 *
 */
function getMerchantConfigs(novalnetActivationKey)
{
    var stoken   = $('#stoken').val();
    var params   = { 'hash': novalnetActivationKey }
    var shopurl  = $('#getUrl').val();
    var formurl  = shopurl+"index.php?cl=novalnetadmincontroller&fnc=getMerchantDetails&stoken="+stoken;

    $.ajax({
    url: formurl,
    type: 'POST',
    data: params,
    dataType: 'json',
    success: function(resultData) {
            if(resultData.details == 'true') {
                var response = $.parseJSON(resultData.response);
                if(response.status == '100') {
                    if (response.vendor != undefined && response.product != undefined) {
                        var tariff = response.tariff;
                        $('#novalnet_vendorid').val(response.vendor);
                        $('#novalnet_authcode').val(response.auth_code);
                        $('#novalnet_productid').val(response.product);
                        $('#novalnet_accesskey').val(response.access_key);
                        var novalnetSavedTariff = $('#novalnet_saved_tariff').val();
                        $.each(tariff, function( index, value ) {
                            var tariff_val = value.type + '-' + index;
                            $('<option/>', { text  : value.name, value : tariff_val }).appendTo('#novalnet_tariffid');
                            });
                        $('#novalnet_tariffid option[value=' + novalnetSavedTariff + ']').attr('selected', 'selected');
                    }
            } else {
                if (response.status == '106') {
                    alert($('#ipErrorOne').val() + response.ip + $('#ipErrorTwo').val());
                } else {
                    alert(response.config_result);
                    $('#novalnet_activation_key').val('')
                    $('#novalnet_tariffid').val('')
                }
            }

            if ($('#ajax_process').attr('value') == 2 ){
                $('.novalnet_config_form').submit();
            }
            $('#ajax_process').attr('value', 1);
        }
    }
    });
}
