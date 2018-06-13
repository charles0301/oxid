<noscript>
    <div class="desc" style="color:red;">
        <br/>[{ oxmultilang ident='NOVALNET_NOSCRIPT_MESSAGE' }]
    </div>
    <input type="hidden" name="novalnet_cc_noscript" value="1">
    <style>#novalnet_cc_form{display:none;}</style>
</noscript>
[{assign var="aCCDetails" value=$oView->getNovalnetPaymentDetails($sPaymentID)}]
[{assign var="displayCCForm" value="style='margin:0;'"}]
    [{if $aCCDetails.iShopType == 1}]
        [{assign var="displayCCForm" value="style='display:none; margin:0;'"}]
        <input type="hidden" name="dynvalue[novalnet_cc_new_details]" id="novalnet_cc_new_details" value="[{$aCCDetails.blOneClick}]">
        <div class="form-group novalnet_cc_ref_acc" id="novalnet_cc_form">
            <label class="control-label col-lg-3"><span id="novalnet_cc_ref_acc" style="color:blue; text-decoration:underline; cursor:pointer;" onclick="changeCCAccountType(event, 'novalnet_cc_new_acc')">[{ oxmultilang ident="NOVALNET_NEW_CARD_DETAILS" }]</span></label>
        </div>
        <div class="form-group novalnet_cc_ref_acc" id="novalnet_cc_form">
            <label class="control-label col-lg-3">[{ oxmultilang ident="NOVALNET_CREDITCARD_TYPE" }]</label>
            <div class="col-lg-9">
                <label class="control-label">[{$aCCDetails.cc_type}]</label>
            </div>
        </div>
        <div class="form-group novalnet_cc_ref_acc" id="novalnet_cc_form">
            <label class="control-label col-lg-3">[{ oxmultilang ident="NOVALNET_CREDITCARD_HOLDER_NAME" }]</label>
            <div class="col-lg-9">
                <label class="control-label">[{$aCCDetails.cc_holder}]</label>
            </div>
        </div>
        <div class="form-group novalnet_cc_ref_acc" id="novalnet_cc_form">
            <label class="control-label col-lg-3">[{ oxmultilang ident="NOVALNET_CREDITCARD_NUMBER" }]</label>
            <div class="col-lg-9">
                <label class="control-label">[{$aCCDetails.cc_no}]</label>
            </div>
        </div>
        <div class="form-group novalnet_cc_ref_acc" id="novalnet_cc_form">
            <label class="control-label col-lg-3">[{ oxmultilang ident="NOVALNET_CREDITCARD_EXPIRY_DATE" }]</label>
            <div class="col-lg-9">
                <label class="control-label">[{$aCCDetails.cc_exp_month}]/[{$aCCDetails.cc_exp_year}]</label>
            </div>
        </div>
        <div class="form-group novalnet_cc_new_acc" id="novalnet_cc_form" [{$displayCCForm}]>
            <label class="control-label col-lg-3"><span id='novalnet_cc_new_acc' style="color:blue; text-decoration:underline; cursor:pointer;" onclick="changeCCAccountType(event, 'novalnet_cc_ref_acc')">[{ oxmultilang ident="NOVALNET_GIVEN_CARD_DETAILS" }]</span></label>
        </div>
    [{/if}]
    <div class="form-group novalnet_cc_new_acc" id="novalnet_cc_form" [{$displayCCForm}]>
        [{assign var=sNovalnetSignature value=$oView->getNovalnetSignature()}]
        <div class="alert alert-info col-lg-offset-3 desc" style="color:red;display:none;" id="novalnet_invalid_card_details">
        </div>
        <iframe onload="loadCreditcardIframe()" id="novalnetiframe" src="https://secure.novalnet.de/cc?signature=[{$sNovalnetSignature}]" style="border-style:none !important;" width="100%" height="100%">
        </iframe>

        [{* Iframe default style *}]
        <input type="hidden" id="novalnet_cc_default_label" value="[{$oView->getNovalnetConfig('sCreditcardDefaultLabel')}]">
        <input type="hidden" id="novalnet_cc_default_input" value="[{$oView->getNovalnetConfig('sCreditcardDefaultInput')}]">
        <input type="hidden" id="novalnet_cc_default_css"   value="[{$oView->getNovalnetConfig('sCreditcardDefaultCss')}]">
        <input type="hidden" id="novalnet_cc_holder_label"  value="[{$oView->getNovalnetConfig('sCreditcardHolderLabel')}]">
        <input type="hidden" id="novalnet_cc_holder_input"  value="[{$oView->getNovalnetConfig('sCreditcardHolderInput')}]">
        <input type="hidden" id="novalnet_cc_number_label"  value="[{$oView->getNovalnetConfig('sCreditcardNumberLabel')}]">
        <input type="hidden" id="novalnet_cc_number_input"  value="[{$oView->getNovalnetConfig('sCreditcardNumberInput')}]">
        <input type="hidden" id="novalnet_cc_exp_label"     value="[{$oView->getNovalnetConfig('sCreditcardExpLabel')}]">
        <input type="hidden" id="novalnet_cc_exp_input"     value="[{$oView->getNovalnetConfig('sCreditcardExpInput')}]">
        <input type="hidden" id="novalnet_cc_cvc_label"     value="[{$oView->getNovalnetConfig('sCreditcardCVCLabel')}]">
        <input type="hidden" id="novalnet_cc_cvc_input"     value="[{$oView->getNovalnetConfig('sCreditcardCVCInput')}]">

        [{* Iframe custom text *}]
        <input type="hidden" id="novalnet_cc_holder_label_text"  value="[{oxmultilang ident="NOVALNET_CREDITCARD_HOLDER_NAME" alternative="" }]">
        <input type="hidden" id="novalnet_cc_number_label_text"  value="[{oxmultilang ident="NOVALNET_CREDITCARD_NUMBER" alternative="" }]">
        <input type="hidden" id="novalnet_cc_exp_label_text"     value="[{oxmultilang ident="NOVALNET_CREDITCARD_EXPIRY_DATE" alternative="" }]">
        <input type="hidden" id="novalnet_cc_cvc_input_text"     value="[{oxmultilang ident="NOVALNET_CREDITCARD_CVC" alternative="" }]">
        <input type="hidden" id="novalnet_cc_holder_placeholder" value="[{oxmultilang ident="NOVALNET_CREDITCARD_HOLDER_NAME_PLACEHOLDER" alternative="" }]">
        <input type="hidden" id="novalnet_cc_number_placeholder" value="[{oxmultilang ident="NOVALNET_CREDITCARD_NUMBER_PLACEHOLDER" alternative="" }]">
        <input type="hidden" id="novalnet_cc_exp_placeholder"    value="[{oxmultilang ident="NOVALNET_CREDITCARD_EXPIRY_DATE_PLACEHOLDER" alternative="" }]">
        <input type="hidden" id="novalnet_cc_cvc_placeholder"    value="[{oxmultilang ident="NOVALNET_CREDITCARD_CVC_PLACEHOLDER" alternative="" }]">
        <input type="hidden" id="novalnet_cc_cvc_hint"           value="[{oxmultilang ident="NOVALNET_CREDITCARD_CVC_HINT" alternative="" }]">
        <input type="hidden" id="novalnet_cc_error_text"         value="[{oxmultilang ident="NOVALNET_CREDITCARD_ERROR_TEXT" alternative="" }]">

        [{* Novalnet Variables *}]
        <input type="hidden" id="novalnet_cc_hash" name="dynvalue[novalnet_cc_hash]">
        <input type="hidden" id="novalnet_cc_uniqueid" name="dynvalue[novalnet_cc_uniqueid]">
        [{oxscript include=$oViewConf->getModuleUrl('novalnet', 'out/src/js/novalnetcreditcard.js')}]
    </div>
[{block name="checkout_payment_longdesc"}]
    <div class="desc alert alert-info col-lg-offset-3">
        [{if $oView->getNovalnetConfig('blCC3DActive') == '1' || $oView->getNovalnetConfig('blCC3DFraudActive') == '1'}]
            [{ oxmultilang ident='NOVALNET_CC_REDIRECT_DESCRIPTION_MESSAGE' }]
        [{else}]
            [{ $paymentmethod->oxpayments__oxlongdesc->getRawValue()}]
        [{/if}]
        [{if $oView->getNovalnetNotification($sPaymentID) != '' }]
            <br><br>[{$oView->getNovalnetNotification($sPaymentID)}]
        [{/if}]
        [{if $oView->getNovalnetTestmode($sPaymentID) }]
            <br><br><span style="color:red">[{ oxmultilang ident='NOVALNET_TEST_MODE_MESSAGE' }]</span>
        [{/if}]
    </div>
[{/block}]
