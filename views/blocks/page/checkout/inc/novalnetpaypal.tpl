[{assign var="aPaypalDetails" value=$oView->getNovalnetPaymentDetails($sPaymentID)}]
[{assign var="aShoppingDetails" value=$oView->getShoppingTypeDetails($sPaymentID)}]
[{oxscript include=$oViewConf->getModuleUrl('novalnet', 'out/src/js/novalnetpaypal.js')}]
[{if $oViewConf->getActiveTheme() == 'flow'}]
 [{if $aShoppingDetails.iShopType == '1' && $aPaypalDetails == ''}]
               <div class="form-group">
                    <label class="req control-label col-lg-3">&nbsp;</label>
                    <div class="col-lg-9">
						<input type="hidden" size="20" name="dynvalue[novalnet_paypal_save_card]" value="0">
                        <input type="checkbox" size="20" name="dynvalue[novalnet_paypal_save_card]" value="1" [{if $dynvalue.novalnet_paypal_save_card == 1}]checked="checked"[{/if}]>&nbsp;[{ oxmultilang ident="NOVALNET_PAYPAL_SAVE_CARD_DATA" }]
                    </div>
               </div>
 [{/if}]     

 [{if $aShoppingDetails.iShopType == '1' && $aPaypalDetails != ''}]
        [{assign var="displayPaypalPort" value="style='display:none;'"}]
        <input type="hidden" name="dynvalue[novalnet_paypal_new_details]" id="novalnet_paypal_new_details" value="[{$aShoppingDetails.blOneClick}]">
        
        <div class="form-group novalnet_paypal_ref_acc">
            <label class="control-label col-lg-3"><span id="novalnet_paypal_ref_acc" style="color:blue; text-decoration:underline; cursor:pointer; white-space:nowrap;" onclick="changePaypalAccountType(event, 'novalnet_paypal_new_acc')">[{ oxmultilang ident="NOVALNET_PAYPAL_NEW_ACCOUNT_DETAILS" }]</span></label>
        </div>
        [{if $smarty.session.sPaymentRefnovalnetpaypal}]
            <div class="form-group novalnet_paypal_ref_acc">
            <label class="control-label col-lg-3">[{ oxmultilang ident="NOVALNET_REFERENCE_TID" }]</label>
            <div class="col-lg-9">
                <label class="control-label">[{$smarty.session.sPaymentRefnovalnetpaypal}]</label>
            </div>
        </div>
        [{/if}]
        [{if $aPaypalDetails.paypal_transaction_id}]
            <div class="form-group novalnet_paypal_ref_acc">
                <label class="control-label col-lg-3">[{ oxmultilang ident="NOVALNET_PAYPAL_REFERENCE_TID" }]</label>
                <div class="col-lg-9">
                    <label class="control-label">[{$aPaypalDetails.paypal_transaction_id}]</label>
                </div>
            </div>
        [{/if}]
        <div class="form-group novalnet_paypal_new_acc" [{$displayPaypalPort}]>
            <label class="control-label col-lg-3"><span id="novalnet_paypal_new_acc" style="color:blue; text-decoration:underline; cursor:pointer;" onclick="changePaypalAccountType(event, 'novalnet_paypal_ref_acc')">[{ oxmultilang ident="NOVALNET_PAYPAL_GIVEN_ACCOUNT_DETAILS" }]</span></label>
        </div>    
        <div class="form-group novalnet_paypal_new_acc" [{$displayPaypalPort}]>
                    <label class="req control-label col-lg-3">&nbsp;</label>
                    <div class="col-lg-9">
						<input type="hidden" size="20" name="dynvalue[novalnet_paypal_save_card]" value="0">
                        <input type="checkbox" size="20" name="dynvalue[novalnet_paypal_save_card]" value="1" [{if $dynvalue.novalnet_paypal_save_card == 1}]checked="checked"[{/if}]>&nbsp;[{ oxmultilang ident="NOVALNET_PAYPAL_SAVE_CARD_DATA" }]
                    </div>
         </div>    
 [{/if}]
              
[{else}]
    <ul class="form">
		 [{if $aShoppingDetails.iShopType == '1' && $aPaypalDetails == ''}]		 
		 <li>
			<input type="hidden" size="20" name="dynvalue[novalnet_save_card]" value="0">
            <input type="checkbox" size="20" name="dynvalue[novalnet_save_card]" value="1" id="novalnet_save_card" [{if $dynvalue.novalnet_save_card == 1}]checked="checked"[{/if}]>&nbsp;[{ oxmultilang ident="NOVALNET_PAYPAL_SAVE_CARD_DATA" }]
          </li>
        [{/if}]
 
        [{if $aShoppingDetails.iShopType == '1' && $aPaypalDetails != ''}]
            [{assign var="displayPaypalPort" value="style='display:none;'"}]
            <input type="hidden" name="dynvalue[novalnet_paypal_new_details]" id="novalnet_paypal_new_details" value="[{$aShoppingDetails.blOneClick}]">
            <li class="novalnet_paypal_ref_acc">
                <table>
                    <tr>
                        <td colspan="2"><span id="novalnet_paypal_ref_acc" style="color:blue; text-decoration:underline; cursor:pointer;" onclick="changePaypalAccountType(event, 'novalnet_paypal_new_acc')">[{ oxmultilang ident="NOVALNET_PAYPAL_NEW_ACCOUNT_DETAILS" }]</span></td>
                    </tr>
                    [{if $smarty.session.sPaymentRefnovalnetpaypal}]
                    <tr>
                        <td><label>[{ oxmultilang ident="NOVALNET_REFERENCE_TID" }]</label></td>
                        <td><label>[{$smarty.session.sPaymentRefnovalnetpaypal}]</label></td>
                    </tr>
                    [{/if}]
                     [{if $aPaypalDetails.paypal_transaction_id}]
                        <tr>
                            <td><label>[{ oxmultilang ident="NOVALNET_PAYPAL_REFERENCE_TID" }]</label></td>
                            <td><label>[{$aPaypalDetails.paypal_transaction_id}]</label></td>
                        </tr>
                    [{/if}]
                </table>
            </li>
            <li class="novalnet_paypal_new_acc" [{$displayPaypalPort}]>
                <span id="novalnet_paypal_new_acc" style="color:blue; text-decoration:underline; cursor:pointer;" onclick="changePaypalAccountType(event, 'novalnet_paypal_ref_acc')">[{ oxmultilang ident="NOVALNET_PAYPAL_GIVEN_ACCOUNT_DETAILS" }]</span>
                [{oxscript include=$oViewConf->getModuleUrl('novalnet', 'out/src/js/novalnetpaypal.js')}]
            </li>

            <li class="novalnet_paypal_new_acc">
						<input type="hidden" size="20" name="dynvalue[novalnet_save_card]" value="0">
                        <input type="checkbox" size="20" name="dynvalue[novalnet_save_card]" value="1" id="novalnet_save_card" [{if $dynvalue.novalnet_save_card == 1}]checked="checked"[{/if}]>&nbsp;[{ oxmultilang ident="NOVALNET_PAYPAL_SAVE_CARD_DATA" }]
            </li>
        [{/if}]
    </ul>
[{/if}]
[{block name="checkout_payment_longdesc"}]
    <div class="desc alert alert-info">
        [{if $aPaypalDetails.iShopType == 1}]
            <span class="novalnet_paypal_ref_acc">
                [{ oxmultilang ident='NOVALNET_PAYPAL_REFERENCE_DESCRIPTION_MESSAGE' }]
            </span>
            <span class="novalnet_paypal_new_acc" [{$displayPaypalPort}]>
                [{ $paymentmethod->oxpayments__oxlongdesc->getRawValue() }]
                <br>[{ oxmultilang ident='NOVALNET_REDIRECT_DESCRIPTION_MESSAGE' }]
            </span>
        [{else}]
            [{ $paymentmethod->oxpayments__oxlongdesc->getRawValue() }]
            <br>[{ oxmultilang ident='NOVALNET_REDIRECT_DESCRIPTION_MESSAGE' }]
        [{/if}]
        [{if $oView->getNovalnetNotification($sPaymentID) != '' }]
            <br><br>[{$oView->getNovalnetNotification($sPaymentID)}]
        [{/if}]
        [{if $oView->getNovalnetTestmode($sPaymentID) }]
            <br><br><span style="color:red">[{ oxmultilang ident='NOVALNET_TEST_MODE_MESSAGE' }]</span>
        [{/if}]
        [{if $oView->getNovalnetZeroAmountStatus($sPaymentID) == '2'}]
             <br><span style="color:red">[{ oxmultilang ident='NOVALNET_ZEROAMOUNT_BOOKING_MESSAGE' }]</span>
        [{/if}]
    </div>
[{/block}]
