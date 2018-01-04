[{assign var="aPaypalDetails" value=$oView->getNovalnetPaymentDetails($sPaymentID)}]
[{assign var="displayPaypalPort" value=""}]
[{if $aPaypalDetails.iShopType == 1}]
        [{assign var="displayPaypalPort" value="style='display:none;'"}]
        <input type="hidden" name="dynvalue[novalnet_paypal_new_details]" id="novalnet_paypal_new_details" value="[{$aPaypalDetails.blOneClick}]">
        <div class="form-group novalnet_paypal_ref_acc">
            <label class="control-label col-lg-3"><span id="novalnet_paypal_ref_acc" style="color:blue; text-decoration:underline; cursor:pointer; white-space:nowrap;" onclick="changePaypalAccountType(event, 'novalnet_paypal_new_acc')">[{ oxmultilang ident="NOVALNET_PAYPAL_NEW_ACCOUNT_DETAILS" }]</span></label>
        </div>
         <div class="form-group novalnet_paypal_ref_acc">
            <label class="control-label col-lg-3">[{ oxmultilang ident="NOVALNET_REFERENCE_TID" }]</label>
            <div class="col-lg-9">
                <label class="control-label">[{$smarty.session.sPaymentRefnovalnetpaypal}]</label>
            </div>
        </div>
        [{if $aPaypalDetails.paypal_transaction_id}]
            <div class="form-group novalnet_paypal_ref_acc">
                <label class="control-label col-lg-3">[{ oxmultilang ident="NOVALNET_PAYPAL_REFERENCE_TID" }]</label>
                <div class="col-lg-9">
                    <label class="control-label">[{$aPaypalDetails.paypal_transaction_id}]</label>
                </div>
            </div>
        [{/if}]

        <div class="form-group novalnet_paypal_new_acc" [{$displayPaypalPort}]>
            <label class="control-label col-lg-3"><span id='novalnet_paypal_new_acc' style="color:blue; text-decoration:underline; cursor:pointer;" onclick="changePaypalAccountType(event, 'novalnet_paypal_ref_acc')">[{ oxmultilang ident="NOVALNET_PAYPAL_GIVEN_ACCOUNT_DETAILS" }]</span></label>
            [{oxscript include=$oViewConf->getModuleUrl('novalnet', 'out/src/js/novalnetpaypal.js')}]
        </div>
[{/if}]

[{block name="checkout_payment_longdesc"}]
    <div class="desc alert alert-info col-lg-offset-3">
        [{if $aPaypalDetails.iShopType == 1}]
            <span class='novalnet_paypal_ref_acc'>
                [{ oxmultilang ident='NOVALNET_PAYPAL_REFERENCE_DESCRIPTION_MESSAGE' }]
            </span>
            <span class='novalnet_paypal_new_acc' [{$displayPaypalPort}]>
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
    </div>
[{/block}]
