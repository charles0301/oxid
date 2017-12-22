/**
 * Confirms the manage transaction process
 *
 * @returns {boolean}
 */
function validateManageProcess()
{
    if ($('#novalnet_manage_status').length && $('#novalnet_manage_status').val() == '') {
        alert($('#novalnet_invalid_status').val());
        return false;
    }

    if ($('#novalnet_manage_status').val() == '100') {
        var confirmMessage = $('#novalnet_confirm_capture').val();
    } else {
        var confirmMessage = $('#novalnet_confirm_cancel').val();
    }
    if (!confirm(confirmMessage)) {
        return false;
    }
    return true;
}

/**
 * Validates the sepa details for refund and confirms to refund
 *
 * @returns {boolean}
 */
function validateRefundProcess()
{
    if ($('#novalnet_refund_amount').val() == '' || $('#novalnet_refund_amount').val() < 1) {
        alert($('#novalnet_invalid_refund_amount').val());
        return false;
    }

    var confirmMessage = $('#novalnet_confirm_refund').val();
    if (!confirm(confirmMessage)) {
        return false;
    }
    return true;
}

/**
 * Confirms the duedate or amount update
 *
 * @returns {boolean}
 */
function confirmUpdateProcess()
{
    if ($('#novalnet_update_amount').val() == '' || $('#novalnet_update_amount').val() < 1) {
        alert($('#novalnet_invalid_amount').val());
        return false;
    }
    if ($('#novalnet_new_duedate').length) {
        if ($('#novalnet_new_duedate').val() == '') {
            alert($('#novalnet_invalid_duedate').val());
            return false;
        }
        var confirmMessage = $('#novalnet_confirm_duedate').val();
    } else {
        var confirmMessage = $('#novalnet_confirm_amount').val();
    }
    if (!confirm(confirmMessage)) {
        return false;
    }
    return true;
}

/**
 * Validates the subscription cancel reason
 *
 * @returns {boolean}
 */
function validateSubscriptionCancelReason()
{
    if ($('#novalnet_cancel_reason').val() == '') {
        alert($('#novalnet_invalid_cancel_reason').val());
        return false;
    } else {
        if (!confirm($('#novalnet_cancel_reason_message').val())) {
            return false;
        }
    }
    return true;
}

/**
 * Validates the transaction booking process
 *
 * @returns {boolean}
 */
function validateBookProcess()
{
    var confirmMessage = $('#novalnet_confirm_book_amount').val();
    if (!confirm(confirmMessage)) {
        return false;
    }
    if ($('#novalnet_book_amount').val() == '' || $('#novalnet_book_amount').val() < 1) {
        alert($('#novalnet_invalid_amount').val());
        return false;
    }
    $('#novalnet_book').attr('disabled', 'disabled');
    return true;
}

/**
 * Validates the triggered key is numeric
 *
 * @returns {boolean}
 */
function isValidExtensionKey(event)
{
    var keycode = ('which' in event) ? event.which : event.keyCode;
    var reg = /^(?:[0-9]+$)/;
    if (event.target.id == 'novalnet_new_duedate') {
        var reg = /^(?:[0-9-]+$)/;
    }

    return (reg.test(String.fromCharCode(keycode)) || keycode == 0 || keycode == 8 || (event.ctrlKey == true && keycode == 114)) ? true : false;
}
