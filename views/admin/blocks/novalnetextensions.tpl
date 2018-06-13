[{$smarty.block.parent}]
[{if isset($aNovalnetPayments) && $paymentType->oxuserpayments__oxpaymentsid->value|in_array:$aNovalnetPayments}]
    [{oxscript include="js/libs/jquery.min.js"}]
    [{oxscript include=$oViewConf->getModuleUrl('novalnet', 'out/admin/src/js/novalnetorders.js')}]
    [{if $blOnHold}]
        <br>
        <form action="[{$oViewConf->getSelfLink()}]" method="post" onsubmit="return validateManageProcess();">
            [{ $oViewConf->getHiddenSid() }]
            <input type="hidden" name="cl" value="order_overview">
            <input type="hidden" name="fnc" value="performNovalnetAction">
            <input type="hidden" name="oxid" value="[{ $oxid }]">
            <input type="hidden" name="novalnet[sRequestType]" value="status_change">
            <input type="hidden" name="novalnet[iOrderNo]" value="[{$edit->oxorder__oxordernr->value}]">

            <table cellspacing="1" cellpadding="0" style="padding: 5px; border: 1px solid #A9A9A9;" width="220">
                <tr>
                    <td class="edittext" align="center" colspan="2">
                        <b>[{ oxmultilang ident="NOVALNET_MANAGE_TRANSACTION_TITLE" }]</b>
                    </td>
                </tr>
                [{if $sOnHoldFailure != ''}]
                    <tr>
                        <td align="center" colspan="3">
                            <p style="color:red; word-break:break-all;">[{ $sOnHoldFailure }]</p>
                        </td>
                    </tr>
                [{/if}]
                <tr>
                    <td class="edittext" align="left">
                        [{ oxmultilang ident="NOVALNET_MANAGE_TRANSACTION_LABEL" }]
                    </td>
                    <td class="edittext" align="left">
                        <select id='novalnet_manage_status' name="novalnet[sTransStatus]">
                            <option value="" selected>[{ oxmultilang ident="NOVALNET_PLEASE_SELECT" }]</option>
                            <option value="100">[{ oxmultilang ident="NOVALNET_CONFIRM" }]</option>
                            <option value="103">[{ oxmultilang ident="NOVALNET_CANCEL" }]</option>
                        </select>
                        <input type="hidden" id="novalnet_invalid_status" value="[{oxmultilang ident="NOVALNET_INVALID_STATUS" }]">
                        <input type="hidden" id="novalnet_confirm_capture" value="[{oxmultilang ident="NOVALNET_CONFIRM_CAPTURE" }]">
                        <input type="hidden" id="novalnet_confirm_cancel" value="[{oxmultilang ident="NOVALNET_CONFIRM_CANCEL" }]">
                    </td>
                </tr>
                <tr>
                    <td class="edittext" align="center" colspan="2">
                        <input type="submit" value="[{oxmultilang ident="NOVALNET_UPDATE" }]">
                    </td>
                </tr>
            </table>
        </form>
    [{/if}]
    [{if $blAmountUpdate}]
        <br>
        <form action="[{$oViewConf->getSelfLink() }]" method="post" onsubmit="return confirmUpdateProcess();">
            [{ $oViewConf->getHiddenSid() }]
            <input type="hidden" name="cl" value="order_overview">
            <input type="hidden" name="fnc" value="performNovalnetAction">
            <input type="hidden" name="oxid" value="[{ $oxid }]">
            <input type="hidden" name="novalnet[sRequestType]" value="amount_update">
            <input type="hidden" name="novalnet[iOrderNo]" value="[{$edit->oxorder__oxordernr->value}]">

            <table cellspacing="1" cellpadding="0" style="padding: 5px; border: 1px solid #A9A9A9;" width="220">
                <tr>
                    <td class="edittext" align="center" colspan="3">
                        <b>[{ oxmultilang ident="NOVALNET_UPDATE_AMOUNT_TITLE" }]</b>
                    </td>
                </tr>
                [{if $sAmountUpdateFailure != ''}]
                    <tr>
                        <td align="center" colspan="3">
                            <p style="color:red; word-break:break-all;">[{ $sAmountUpdateFailure }]</p>
                        </td>
                    </tr>
                [{/if}]
                <tr>
                    <td class="edittext" align="left">
                        [{ oxmultilang ident="NOVALNET_UPDATE_AMOUNT_LABEL" }]
                    </td>
                    <td class="edittext" align="left">
                        <input type="text" size="15" autocomplete="off" name="novalnet[sUpdateAmount]" value="[{$dNovalnetAmount}]" onkeypress="return isValidExtensionKey(event);">
                        <input type="hidden" id="novalnet_invalid_amount" value="[{ oxmultilang ident="NOVALNET_INVALID_AMOUNT" }]" >
                        <input type="hidden" id="novalnet_confirm_amount" value="[{oxmultilang ident="NOVALNET_CONFIRM_AMOUNT_UPDATE" }]">
                    </td>
                    <td class="edittext" align="left">
                        [{ oxmultilang ident="NOVALNET_CENTS" }]
                    </td>
                </tr>
                <tr>
                    <td class="edittext" align="center" colspan="3">
                        <input type="submit" value="[{oxmultilang ident="NOVALNET_UPDATE" }]">
                    </td>
                </tr>
            </table>
        </form>
    [{/if}]
    [{if $blAmountRefund}]
        <br>
        <form action="[{$oViewConf->getSelfLink() }]" method="post" onsubmit="return validateRefundProcess();">
            [{ $oViewConf->getHiddenSid() }]
            <input type="hidden" name="cl" value="order_overview">
            <input type="hidden" name="fnc" value="performNovalnetAction">
            <input type="hidden" name="oxid" value="[{ $oxid }]">
            <input type="hidden" name="novalnet[sRequestType]" value="amount_refund">
            <input type="hidden" name="novalnet[iOrderNo]" value="[{$edit->oxorder__oxordernr->value}]">

            <table cellspacing="1" cellpadding="0" style="padding: 5px; border: 1px solid #A9A9A9;" width="220">
                <tr>
                    <td class="edittext" align="center" colspan="3">
                        <b>[{ oxmultilang ident="NOVALNET_REFUND_AMOUNT_TITLE" }]</b>
                    </td>
                </tr>
                [{if $sAmountRefundFailure != ''}]
                    <tr>
                        <td align="center" colspan="3">
                            <p style="color:red; word-break:break-all;">[{ $sAmountRefundFailure }]</p>
                        </td>
                    </tr>
                [{/if}]
                <tr>
                    <td class="edittext" align="left">
                        [{ oxmultilang ident="NOVALNET_REFUND_AMOUNT_LABEL" }]
                    </td>
                    <td class="edittext" align="left">
                        <input type="text" size="15" id="novalnet_refund_amount" name="novalnet[sRefundAmount]" autocomplete="off" value="[{$dNovalnetAmount}]" onkeypress="return isValidExtensionKey(event);">
                        <input type="hidden" id="novalnet_invalid_refund_amount" value="[{ oxmultilang ident="NOVALNET_INVALID_AMOUNT" }]" >
                        <input type="hidden" id="novalnet_confirm_refund" value="[{oxmultilang ident="NOVALNET_CONFIRM_REFUND" }]">
                    </td>
                    <td class="edittext" align="left">
                        [{ oxmultilang ident="NOVALNET_CENTS" }]
                    </td>
                </tr>
                [{if $blRefundRef}]
                    <tr>
                        <td class="edittext" align="left">
                            [{ oxmultilang ident="NOVALNET_REFUND_REFERENCE_LABEL" }]
                        </td>
                        <td class="edittext" align="left" colspan="2">
                            <input type="text" size="15" autocomplete="off" name="novalnet[sRefundRef]" id="refund_ref" value="">
                        </td>
                    </tr>
                [{/if}]
                <tr>
                    <td class="edittext" align="center" colspan="3">
                        <input type="submit" value="[{oxmultilang ident="NOVALNET_UPDATE" }]">
                    </td>
                </tr>
            </table>
        </form>
    [{/if}]
    [{if $blDuedateUpdate}]
        <br>
        <form action="[{$oViewConf->getSelfLink() }]" method="post" onsubmit="return confirmUpdateProcess();">
            [{ $oViewConf->getHiddenSid() }]
            <input type="hidden" name="cl" value="order_overview">
            <input type="hidden" name="fnc" value="performNovalnetAction">
            <input type="hidden" name="oxid" value="[{ $oxid }]">
            <input type="hidden" name="novalnet[sRequestType]" value="amount_duedate_update">
            <input type="hidden" name="novalnet[iOrderNo]" value="[{$edit->oxorder__oxordernr->value}]">

            <table cellspacing="1" cellpadding="0" style="padding: 5px; border: 1px solid #A9A9A9;" width="220">
                <tr>
                    <td class="edittext" align="center" colspan="3">
                        <b>
                        [{if $sKey =='59' }]
                            [{ oxmultilang ident="NOVALNET_BARZAHLEN_DUE_DATE_UPDATE_TITLE" }]
                        [{else}]
                            [{ oxmultilang ident="NOVALNET_UPDATE_AMOUNT_DUEDATE_TITLE" }]
                        [{/if}]
                        </b>
                    </td>
                </tr>
                [{if $sDuedateUpdateFailure != ''}]
                    <tr>
                        <td align="center" colspan="3">
                            <p style="color:red; word-break:break-all;">[{ $sDuedateUpdateFailure }]</p>
                        </td>
                    </tr>
                [{/if}]
                <tr>
                    <td class="edittext" align="left">
                        [{ oxmultilang ident="NOVALNET_UPDATE_AMOUNT_LABEL" }]
                    </td>
                    <td class="edittext" align="left">
                        <input type="text" size="15" autocomplete="off" id="novalnet_update_amount" name="novalnet[sUpdateAmount]" onkeypress="return isValidExtensionKey(event);" value="[{$dNovalnetAmount}]">
                        <input type="hidden" id="novalnet_invalid_amount" value="[{ oxmultilang ident="NOVALNET_INVALID_AMOUNT" }]" >
                    </td>
                    <td class="edittext" align="left">
                        [{ oxmultilang ident="NOVALNET_CENTS" }]
                    </td>
                </tr>
                <tr>
                    <td class="edittext" align="left">
                        [{if $sKey =='59' }]
                            [{ oxmultilang ident="NOVALNET_BARZAHLEN_DUE_DATE_LABEL" }]
                        [{else}]
                            [{ oxmultilang ident="NOVALNET_UPDATE_DUEDATE_LABEL" }]
                        [{/if}]
                    </td>
                    <td class="edittext" align="left">
                        <input type="text" size="15" id="novalnet_new_duedate" autocomplete="off" name="novalnet[sUpdateDate]" onkeypress="return isValidExtensionKey(event);" value="[{$sNovalnetDueDate}]" >

                        [{if $sKey =='59' }]
                        <input type="hidden" id="novalnet_confirm_duedate" value="[{ oxmultilang ident="NOVALNET_CONFIRM_SLIPDATE_UPDATE" }]" >
                        <input type="hidden" id="novalnet_invalid_duedate" value="[{ oxmultilang ident="NOVALNET_INVALID_SLIPEDATE" }]" >
                        [{else}]
                        <input type="hidden" id="novalnet_confirm_duedate" value="[{ oxmultilang ident="NOVALNET_CONFIRM_DUEDATE_UPDATE" }]" >
                        <input type="hidden" id="novalnet_invalid_duedate" value="[{ oxmultilang ident="NOVALNET_INVALID_DUEDATE" }]" >
                        [{/if}]
                    </td>
                    <td class="edittext" align="left">
                        (YYYY-MM-DD)
                    </td>
                </tr>
                <tr>
                    <td class="edittext" align="center" colspan="3">
                        <input type="submit" value="[{oxmultilang ident="NOVALNET_UPDATE" }]">
                    </td>
                </tr>
            </table>
        </form>
    [{/if}]
    [{if $blSubsCancel}]
        <br>
        <form action="[{$oViewConf->getSelfLink() }]" method="post" onsubmit="return validateSubscriptionCancelReason();">
            [{ $oViewConf->getHiddenSid() }]
            <input type="hidden" name="cl" value="order_overview">
            <input type="hidden" name="fnc" value="performNovalnetAction">
            <input type="hidden" name="oxid" value="[{ $oxid }]">
            <input type="hidden" name="novalnet[sRequestType]" value="subscription_cancel">
            <input type="hidden" name="novalnet[iOrderNo]" value="[{$edit->oxorder__oxordernr->value}]">

            <table cellspacing="1" cellpadding="0" style="padding: 5px; border: 1px solid #A9A9A9;" width="220">
                <tr>
                    <td class="edittext" align="center" colspan="2">
                        <b>[{ oxmultilang ident="NOVALNET_SUBSCRIPTION_CANCEL_TITLE" }]</b>
                    </td>
                </tr>
                [{if $sSubsCancelFailure != ''}]
                    <tr>
                        <td class="edittext" align="center" colspan="2">
                            <p style="color:red; word-break:break-all;">[{ $sSubsCancelFailure }]</p>
                        </td>
                    </tr>
                [{/if}]
                <tr>
                    <td class="edittext" align="left">
                        [{ oxmultilang ident="NOVALNET_SUBSCRIPTION_CANCEL_LABEL" }]
                    </td>
                    <td class="edittext" align="left">
                        <select name="novalnet[sSubsCancel]" id="novalnet_cancel_reason">
                            <option value=''>[{ oxmultilang ident="NOVALNET_PLEASE_SELECT" }]</option>
                            [{foreach from=$oView->getNovalnetSubsReasons() item=reason}]
                                <option value='[{$reason}]'>[{$reason}]</option>
                            [{/foreach}]
                        </select>
                        <input type="hidden" id="novalnet_invalid_cancel_reason" value="[{oxmultilang ident="NOVALNET_INVALID_CANCEL_REASON" }]">
                        <input type="hidden" id="novalnet_cancel_reason_message" value="[{oxmultilang ident='NOVALNET_CANCEL_REASON'}]">
                    </td>
                </tr>
                <tr>
                    <td class="edittext" align="center" colspan="2">
                        <input type="submit" value="[{oxmultilang ident="NOVALNET_UPDATE" }]">
                    </td>
                </tr>
            </table>
        </form>
    [{/if}]
    [{if $blZeroBook}]
        <br>
        <form action="[{$oViewConf->getSelfLink() }]" method="post" onsubmit="return validateBookProcess();">
            [{ $oViewConf->getHiddenSid() }]
            <input type="hidden" name="cl" value="order_overview">
            <input type="hidden" name="fnc" value="performNovalnetAction">
            <input type="hidden" name="oxid" value="[{ $oxid }]">
            <input type="hidden" name="novalnet[sRequestType]" value="amount_book">
            <input type="hidden" name="novalnet[iOrderNo]" value="[{$edit->oxorder__oxordernr->value}]">

            <table cellspacing="1" cellpadding="0" style="padding: 5px; border: 1px solid #A9A9A9;" width="220">
                <tr>
                    <td class="edittext" align="center" colspan="3">
                        <b>[{ oxmultilang ident="NOVALNET_BOOK_AMOUNT_TITLE" }]</b>
                    </td>
                </tr>
                [{if $sZeroBookFailure != ''}]
                    <tr>
                        <td align="center" colspan="3">
                            <p style="color:red; word-break:break-all;">[{ $sZeroBookFailure }]</p>
                        </td>
                    </tr>
                [{/if}]
                <tr>
                    <td class="edittext" align="left">
                        [{ oxmultilang ident="NOVALNET_BOOK_AMOUNT_LABEL" }]
                    </td>
                    <td class="edittext" align="left">
                        <input type="text" size="15" id="novalnet_book_amount" name="novalnet[sBookAmount]" autocomplete="off" value="[{$dOrderAmount}]" onkeypress="return isValidExtensionKey(event);">
                        <input type="hidden" id="novalnet_invalid_amount" value="[{ oxmultilang ident="NOVALNET_INVALID_AMOUNT" }]" >
                        <input type="hidden" id="novalnet_confirm_book_amount" value="[{ oxmultilang ident="NOVALNET_CONFIRM_BOOKED" }]" >
                    </td>
                    <td class="edittext" align="left">
                        [{ oxmultilang ident="NOVALNET_CENTS" }]
                    </td>
                </tr>
                <tr>
                    <td class="edittext" align="center" colspan="3">
                        <input type="submit" id='novalnet_book' value="[{oxmultilang ident="NOVALNET_UPDATE" }]">
                    </td>
                </tr>
            </table>
        </form>
    [{/if}]
[{/if}]
