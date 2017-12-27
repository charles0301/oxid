$(document).ready(function() {
    var novalnetSepaForm   = $('#novalnet_sepa_mandate_confirm').closest('form').attr('id');
    var novalnetSepasubmit = $('#'+novalnetSepaForm).find(':submit');
    $(novalnetSepasubmit).click(function(e) {
        if($("input[name=paymentid]:checked").val() == 'novalnetsepa' && (!$('#novalnet_sepa_new_details').length || $('#novalnet_sepa_new_details').val() == 1)) {
            if(!$('#novalnet_sepa_mandate_confirm').is(':checked')) {
                alert($('#novalnet_sepa_unconfirm_message').val());
                return false;
            }
        }
    });

    if($('#novalnet_sepa_new_details').length && $('#novalnet_sepa_new_details').val() == 1) {
        $('.novalnet_sepa_new_acc').show();
        $('.novalnet_sepa_ref_acc').hide();
    }

    if($("input[name=paymentid]:checked").val() == 'novalnetsepa' && $('#novalnet_sepa_hash').length && $('#novalnet_sepa_hash').val() != '') {
        separefillcall();
    }
    $('#novalnet_sepa_mandate_confirm').click(function() {
        if($('input[name=paymentid]:checked').val() == 'novalnetsepa' && (!$('#novalnet_sepa_new_details').length || $('#novalnet_sepa_new_details').val() == 1)) {
            if($('#novalnet_sepa_mandate_confirm').is(':checked')) {
                if($('#novalnet_sepa_country').val().length == 0) {
                    $('#novalnet_sepa_mandate_confirm').attr('checked', false);
                    alert($('#novalnet_sepa_country_invalid_message').val());
                }
                else if(!isNaN($('#novalnet_sepa_acc_no').val()) && !isNaN($('#novalnet_sepa_bank_code').val())) {
                    sepaibanbiccall();
                }
                else if(isNaN($('#novalnet_sepa_acc_no').val()) && (isNaN($('#novalnet_sepa_bank_code').val()) || !$('#novalnet_sepa_bank_code').val())) {
                    sepahashcall();
                }
                else {
                    $('#novalnet_sepa_mandate_confirm').attr('checked',false);
                    alert($('#novalnet_sepa_invalid_message').val());
                }
            }
            else {
                sepa_mandate_unconfirm_process();
            }
        }
    });

    $('#novalnet_sepa_holder, #novalnet_sepa_country, #novalnet_sepa_acc_no, #novalnet_sepa_bank_code').change(function() {
        sepa_mandate_unconfirm_process();
    });
});

/**
 * Manages mandate unconfirm preocess
 *
 */
function sepa_mandate_unconfirm_process()
{
    $('#novalnet_sepa_iban_span, #novalnet_sepa_bic_span').html('');
    $('#novalnet_sepa_iban, #novalnet_sepa_bic').val('');
    $('#novalnet_sepa_mandate_confirm').attr('checked',false);
    $('#novalnet_sepa_loader').hide();
}

/**
 * Toggles account type while onclick shopping enabled for sepa
 *
 */
function changeSepaAccountType(event, accType)
{
    var currentAccType = event.target.id;
    $('.' + currentAccType).hide();
    $('.' + accType).show();
    if (accType == 'novalnet_sepa_new_acc')
        $('#novalnet_sepa_new_details').val(1);
    else
        $('#novalnet_sepa_new_details').val(0);

    $('#novalnet_sepa_mandate_confirm').attr('checked',false);
}

/**
 * Sends IBAN BIC call to Novalnet server
 *
 * @returns {boolean}
 */
function sepaibanbiccall()
{
    $("#novalnet_sepa_loader").show();
    novalnet_vendor = $('#novalnet_sepa_vendor_id').val();
    novalnet_auth_code = $('#novalnet_sepa_vendor_authcode').val();
    novalnet_sepa_uniqueid = $('#novalnet_sepa_uniqueid').val();
    novalnet_sepa_holder = removeSpecialCharactersSepa($.trim($('#novalnet_sepa_holder').val()), 'holder');
    novalnet_sepa_country = $('#novalnet_sepa_country').val();
    novalnet_sepa_account_no = removeSpecialCharactersSepa($('#novalnet_sepa_acc_no').val(). replace(/[^a-z0-9]+/gi, ''), 'iban');
    novalnet_sepa_bank_code = removeSpecialCharactersSepa($('#novalnet_sepa_bank_code').val(). replace(/[^a-z0-9]+/gi, ''), 'bic');
    novalnet_remote_ip = $('#novalnet_remote_ip').val();

    if(novalnet_vendor == undefined || novalnet_vendor == '' || novalnet_auth_code == undefined || novalnet_auth_code == '') {
        $('#novalnet_sepa_mandate_confirm').attr('checked', false);
        $("#novalnet_sepa_loader").hide();
        alert($('#novalnet_sepa_merchant_invalid_message').val());
        return false;
    }

    if(novalnet_sepa_holder == undefined || novalnet_sepa_holder == '' || novalnet_sepa_country == undefined || novalnet_sepa_country == '' || novalnet_sepa_account_no == undefined || novalnet_sepa_account_no == '' || novalnet_sepa_bank_code == undefined || novalnet_sepa_bank_code == '' || novalnet_sepa_uniqueid == undefined || novalnet_sepa_uniqueid == '') {
        $('#novalnet_sepa_mandate_confirm').attr('checked', false);
        $("#novalnet_sepa_loader").hide();
        alert($('#novalnet_sepa_invalid_message').val());
        return false;
    }

    var sepaPayportParams = $.param({ 'account_holder' : novalnet_sepa_holder, 'bank_account' : novalnet_sepa_account_no, 'bank_code' : novalnet_sepa_bank_code, 'vendor_id' : novalnet_vendor, 'vendor_authcode' : novalnet_auth_code, 'bank_country' : novalnet_sepa_country, 'unique_id' : novalnet_sepa_uniqueid, 'get_iban_bic' : '1', 'remote_ip' : novalnet_remote_ip });

    sepaCrossDomainRequest(sepaPayportParams, "iban_bic");
}

/**
 * Sends hash call to Novalnet server
 *
 */
function sepahashcall()
{
     $("#novalnet_sepa_loader").show();
     novalnet_vendor = $('#novalnet_sepa_vendor_id').val();
     novalnet_auth_code = $('#novalnet_sepa_vendor_authcode').val();
     novalnet_sepa_uniqueid = $('#novalnet_sepa_uniqueid').val();
     novalnet_sepa_holder = removeSpecialCharactersSepa($.trim($('#novalnet_sepa_holder').val()), 'holder');
     novalnet_sepa_country = $('#novalnet_sepa_country').val();
     novalnet_sepa_account_no = removeSpecialCharactersSepa($('#novalnet_sepa_acc_no').val(). replace(/[^a-z0-9]+/gi, ''),'iban');
     novalnet_sepa_bank_code = removeSpecialCharactersSepa($('#novalnet_sepa_bank_code').val(). replace(/[^a-z0-9]+/gi, ''),'bic');
     novalnet_sepa_iban = $('#novalnet_sepa_iban').val();
     novalnet_sepa_bic = $('#novalnet_sepa_bic').val();
     novalnet_remote_ip = $('#novalnet_remote_ip').val();

    if(novalnet_vendor == undefined || novalnet_vendor == '' || novalnet_auth_code == undefined ||  novalnet_auth_code == '') {
        $("#novalnet_sepa_loader").hide();
        alert($('#novalnet_sepa_merchant_invalid_message').val());
        $('#novalnet_sepa_mandate_confirm').attr('checked',false);
        return false;
    }
    if(novalnet_sepa_country == 'DE' && isNaN(novalnet_sepa_account_no)) {
        novalnet_sepa_iban = novalnet_sepa_account_no;
        novalnet_sepa_bic  = novalnet_sepa_bank_code;
        if (!novalnet_sepa_bic.length ) {
            novalnet_sepa_bic = '123456';
        }
        novalnet_sepa_account_no = '';
        novalnet_sepa_bank_code  = '';
    } else if(isNaN(novalnet_sepa_account_no) && isNaN(novalnet_sepa_bank_code)) {
        novalnet_sepa_iban = novalnet_sepa_account_no;
        novalnet_sepa_bic  = novalnet_sepa_bank_code;
    }
    if(novalnet_sepa_holder == '' || novalnet_sepa_country == '' || (novalnet_sepa_iban == '' && novalnet_sepa_bic == '') || novalnet_sepa_uniqueid == '') {
        $("#novalnet_sepa_loader").hide();
        alert($('#novalnet_sepa_invalid_message').val());
        sepa_mandate_unconfirm_process();
        return false;
    }
    var sepaPayportParams = $.param({ 'account_holder' : novalnet_sepa_holder, 'bank_account' : novalnet_sepa_account_no, 'bank_code' : novalnet_sepa_bank_code, 'vendor_id' : novalnet_vendor, 'vendor_authcode' : novalnet_auth_code, 'bank_country' : novalnet_sepa_country, 'unique_id' : novalnet_sepa_uniqueid, 'sepa_data_approved' : '1', 'mandate_data_req' : '1', 'iban' : novalnet_sepa_iban, 'bic' : novalnet_sepa_bic, 'remote_ip' : novalnet_remote_ip });

    sepaCrossDomainRequest(sepaPayportParams, "hash");
}

/**
 * Sends refill call to Novalnet server
 *
 */
function separefillcall()
{
    $("#novalnet_sepa_loader").show();
    novalnet_vendor = $('#novalnet_sepa_vendor_id').val();
    novalnet_auth_code = $('#novalnet_sepa_vendor_authcode').val();
    novalnet_sepa_uniqueid = $('#novalnet_sepa_uniqueid').val();
    novalnet_sepa_hash = $('#novalnet_sepa_hash').val();
    novalnet_remote_ip = $('#novalnet_remote_ip').val();

    if(novalnet_vendor == undefined || novalnet_vendor == '' || novalnet_auth_code == undefined || novalnet_auth_code == '' || novalnet_sepa_uniqueid == undefined || novalnet_sepa_uniqueid == '') {
        $("#novalnet_sepa_loader").hide();
        return false;
    }

    var sepaPayportParams = $.param({ 'vendor_id' : novalnet_vendor, 'vendor_authcode' : novalnet_auth_code, 'unique_id' : novalnet_sepa_uniqueid, 'sepa_hash' : novalnet_sepa_hash, 'sepa_data_approved' : '1', 'mandate_data_req' : '1', 'remote_ip' : novalnet_remote_ip });

    sepaCrossDomainRequest(sepaPayportParams, "refill");
}

/**
 * Validates entered key in input of sepa form
 *
 * @returns {boolean}
 */
function isValidKeySepa(evt)
{
    var keycode = ('which' in evt) ? evt.which : evt.keyCode;
    var reg = /^(?:[A-Za-z0-9]+$)/;
    if(evt.target.id == 'novalnet_sepa_holder') {
        var reg = /[^0-9\[\]\/\\#,+@!^()$~%'"=:;<>{}\_\|*?`]/g
    }
    return (reg.test(String.fromCharCode(keycode)) || keycode == 0 || keycode == 8 || (evt.ctrlKey == true && keycode == 114) || (evt.target.id == 'novalnet_sepa_holder' && keycode == 45)) ? true : false;
}

/**
 * Sends cross domain request to Novalnet server
 *
 * @returns {boolean}
 */
function sepaCrossDomainRequest(params, type)
{
    if ('XDomainRequest' in window && window.XDomainRequest !== null) {
        var xdr = new XDomainRequest(); //Use Microsoft XDR
        xdr.open('POST', 'https://payport.novalnet.de/sepa_iban');
        xdr.onload = function () {
            var response = $.parseJSON(this.responseText);
            if (response.hash_result == 'success') {
                processCrossDomainResponseSepa(response, type)
            } else {
                $('#novalnet_sepa_loader').hide();
                alert(response.hash_result);
                return false;
            }
        };
        xdr.onerror = function() {
            $('#novalnet_sepa_loader').hide();
            return true;
        };
        xdr.send(params);
    } else {
        $.ajax({
            type     : 'POST',
            url      : 'https://payport.novalnet.de/sepa_iban',
            data     : params,
            dataType : 'json',
            success  : function(response) {
                if(response.hash_result == 'success') {
                    processCrossDomainResponseSepa(response, type);
                } else {
                    $('#novalnet_sepa_loader').hide();
                    alert(response.hash_result);
                    return false;
                }
            },
            error    : function(response) {
                $('#novalnet_sepa_loader').hide();
                return true;
            }
        });
    }
}

/**
 * Process the result from the Novalnet server
 *
 */
function processCrossDomainResponseSepa(response, type)
{
    if(type == 'hash') {
        if(response.sepa_hash) {
            $('#novalnet_sepa_hash').val(response.sepa_hash);
        } else {
            alert($('#novalnet_sepa_invalid_message').val());
        }
        $('#novalnet_sepa_loader').hide();
    } else if(type == 'iban_bic') {
        if ((response.IBAN).length && (response.BIC).length) {
            $('#novalnet_sepa_iban_span').html("<b>IBAN: " + response.IBAN + "</b>");
            $('#novalnet_sepa_bic_span').html("<b>BIC: " + response.BIC + "</b>");
            $('#novalnet_sepa_iban').val(response.IBAN);
            $('#novalnet_sepa_bic').val(response.BIC);
            $("#novalnet_sepa_loader").hide();
            sepahashcall();
        }
        else {
            sepa_mandate_unconfirm_process();
            alert($('#novalnet_sepa_invalid_message').val());
        }
    } else {
        var hash_string = response.hash_string.split('&');
        var arrayresult = {};
        for (var i=0, len=hash_string.length; i<len; i++) {
            if (hash_string[i] == '' || hash_string[i].indexOf("=") == -1) {
                hash_string[i] = hash_string[i-1] + '&' +hash_string[i];
            }
            var hash_result_val = hash_string[i].split('=');
            arrayresult[hash_result_val[0]] = hash_result_val[1];
        }
        $('#novalnet_sepa_holder').val(arrayresult.account_holder);
        $('#novalnet_sepa_country').val(arrayresult.bank_country);
        $('#novalnet_sepa_acc_no').val(arrayresult.iban);
        if (arrayresult.bic != '123456') {
            $('#novalnet_sepa_bank_code').val(arrayresult.bic);
        }
    }
    $('#novalnet_sepa_loader').hide();
}

/**
 * Remove special characters and spaces
 *
 */
function removeSpecialCharactersSepa(value, req)
{
     if (value != 'undefined' || value != '') {
        value.replace(/^\s+|\s+$/g, '');
        if (req != 'undefined' && req == 'holder') {
            return value.replace(/[\/\\|\]\[|#@,+()`'$~%":;*?<>!^{}=_]/g, '');
        } else {
            return value.replace(/[\/\\|\]\[|#@,+()`'$~%.":;*?<>!^{}=_-]/g, '');
        }
    }
}
