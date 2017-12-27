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
    } else {
        $('#novalnet_vendorid, #novalnet_authcode, #novalnet_productid, #novalnet_accesskey, #novalnet_activation_key' ).val('');
        $('#novalnet_tariffid').find('option').remove();
    }
}
/**
 * Sends the api call to get the vendor credentials
 *
 */
function getMerchantConfigs(novalnetActivationKey)
{
    //~ var systemIp    = $('#system_ip').val();
    var systemIp    = '127.0.0.1';
    //~ var remoteIp    = $('#remote_ip').val();
    var remoteIp    = '127.0.0.1';
    var language    = $('#language').val();
    var novalnetUrl = 'https://payport.novalnet.de/autoconfig';
    var params      = 'system_ip=' + systemIp + '&remote_ip=' + remoteIp + '&api_config_hash=' + novalnetActivationKey + '&lang=' + language;

    if ('XDomainRequest' in window && window.XDomainRequest !== null) {
        var xdr = new XDomainRequest(); //Use Microsoft XDR
        xdr.open('POST', novalnetUrl);
        xdr.onload = function () {
            processResult(xdr.responseText);
        };
        xdr.onerror = function() { return true; };
        xdr.send(params);
    }
    else {
        var xmlhttp = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
        xmlhttp.onreadystatechange=function() {
            if (xmlhttp.readyState==4 && xmlhttp.status==200) {
                processResult(xmlhttp.responseText);
            }
        };
        xmlhttp.open('POST', novalnetUrl, true);
        xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xmlhttp.send(params);
    }
}

/**
 * Process the api call result
 *
 */
function processResult(response)
{
    var hashString = $.parseJSON(response);
    if (hashString.tariff_id != undefined) {
        var novalnetSavedTariff = $('#novalnet_saved_tariff').val();
        novalnetTariffValue = hashString.tariff_id.split(',');
        novalnetTariffName  = hashString.tariff_name.split(',');
        novalnetTariffType  = hashString.tariff_type.split(',');
        $('#novalnet_tariffid').find('option').remove();
        for (i = 0; i < novalnetTariffValue.length; i++) {
            var tariffName  = novalnetTariffName[i].split(':');
            var tariffValue = novalnetTariffValue[i].split(':');
            var tariffType  = novalnetTariffType[i].split(':');
            tariffName  = (tariffName[2] != undefined) ? tariffName[1] + ':' + tariffName[2] : tariffName[1];
            tariffValue = tariffType[1] + '-' + tariffValue[1].trim();
            $('<option/>', { text  : tariffName, value : tariffValue }).appendTo('#novalnet_tariffid');
        }
        $('#novalnet_tariffid option[value=' + novalnetSavedTariff + ']').attr('selected', 'selected');
        $('#novalnet_vendorid').val(hashString.vendor_id);
        $('#novalnet_authcode').val(hashString.auth_code);
        $('#novalnet_productid').val(hashString.product_id);
        $('#novalnet_accesskey').val(hashString.access_key);
    } else {
        alert(hashString.config_result);
    }
    if ($('#ajax_process').attr('value') == 2 ){
        $('.novalnet_config_form').submit();
    }
    $('#ajax_process').attr('value', 1);
}
