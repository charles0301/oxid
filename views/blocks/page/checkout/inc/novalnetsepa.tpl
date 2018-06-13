[{if $oView->getNovalnetConfig('blAutoRefill') }]
    [{ assign var="dynvalue" value=$oView->getDynValue()}]
[{/if}]
<noscript>
    <div class="desc" style="color:red;">
        <br/>[{ oxmultilang ident='NOVALNET_NOSCRIPT_MESSAGE' }]
    </div>
    <input type="hidden" name="novalnet_sepa_noscript" value="1">
    <style>#novalnet_sepa_form{display:none;}</style>
</noscript>
[{if $oViewConf->getActiveTheme() == 'flow'}]
    [{if !empty($smarty.session.sCallbackTidnovalnetsepa)}]
        [{if in_array($oView->getNovalnetConfig('iCallbacknovalnetsepa'), array(1, 2))}]
            <div class="form-group">
                <label class="req control-label col-lg-3">[{ oxmultilang ident="NOVALNET_FRAUD_MODULE_PIN" }]</label>
                <div class="col-lg-9">
                    <input type="text" size="20" name="dynvalue[pinno_novalnetsepa]" autocomplete="off" value="">
                </div>
            </div>
            <div class="form-group">
                <label class="req control-label col-lg-3">&nbsp;</label>
                <div class="col-lg-9">
                    <input type="checkbox" size="20" name="dynvalue[newpin_novalnetsepa]">&nbsp;[{ oxmultilang ident="NOVALNET_FRAUD_MODULE_FORGOT_PIN" }]
                </div>
            </div>
        [{/if}]
    [{else}]
        [{assign var="aSepaDetails" value=$oView->getNovalnetPaymentDetails($sPaymentID)}]
        [{assign var="displaySepaForm" value="style='width:100%;'"}]
        [{if $aSepaDetails.iShopType == 1}]
            [{assign var="displaySepaForm" value="style='width:100%; display:none;'"}]
            <input type="hidden" name="dynvalue[novalnet_sepa_new_details]" id="novalnet_sepa_new_details" value=[{$aSepaDetails.blOneClick}]>
            <div class="form-group novalnet_sepa_ref_acc">
                <label class="control-label col-lg-3"><span id="novalnet_sepa_ref_acc" style="color:blue; text-decoration:underline; cursor:pointer;" onclick="changeSepaAccountType(event, 'novalnet_sepa_new_acc')">[{oxmultilang ident="NOVALNET_NEW_ACCOUNT_DETAILS"}]</span></label>
            </div>
            <div class="form-group novalnet_sepa_ref_acc">
                <label class="control-label col-lg-3">[{oxmultilang ident="NOVALNET_SEPA_HOLDER_NAME"}]</label>
                <div class="col-lg-9">
                    <label class="control-label" style="padding-left:0">[{$aSepaDetails.bankaccount_holder}]</label>
                </div>
            </div>
            <div class="form-group novalnet_sepa_ref_acc">
                <label class="control-label col-lg-3">IBAN</label>
                <div class="col-lg-9">
                    <label class="control-label">[{$aSepaDetails.iban}]</label>
                </div>
            </div>
            [{if $aSepaDetails.bic != '123456'}]
                <div class="form-group novalnet_sepa_ref_acc">
                    <label class="control-label col-lg-3">BIC</label>
                    <div class="col-lg-9">
                        <label class="control-label">[{$aSepaDetails.bic}]</label>
                    </div>
                </div>
            [{/if}]
             [{if !empty($smarty.session.blGuaranteeEnablednovalnetsepa) && empty($smarty.session.blGuaranteeForceDisablednovalnetsepa) }]
               <div class="form-group novalnet_sepa_ref_acc">
                    <label class="control-label col-lg-3">[{ oxmultilang ident="NOVALNET_BIRTH_DATE" }]</label>
                    <div class="col-lg-9">
                        <input type="text" class="form-control" size="20" id="novalnet_sepa_birth_date" name="dynvalue[birthdatenovalnetsepa]" value="[{$oView->getNovalnetBirthDate()}]" placeholder="YYYY-MM-DD" autocomplete="off">
                    </div>
            </div>
            [{/if}]
            <div class="form-group novalnet_sepa_new_acc" [{$displaySepaForm}]>
                <label class="control-label col-lg-3"><span id='novalnet_sepa_new_acc' style="color:blue; text-decoration:underline; cursor:pointer;" onclick="changeSepaAccountType(event, 'novalnet_sepa_ref_acc')">[{oxmultilang ident="NOVALNET_GIVEN_ACCOUNT_DETAILS"}]</span></label>
            </div>
        [{/if}]
        <div class="form-group novalnet_sepa_new_acc" [{$displaySepaForm}]>
            <label class="req control-label col-lg-3">[{ oxmultilang ident="NOVALNET_SEPA_HOLDER_NAME" }]</label>
            <div class="col-lg-9">
                <input type="text" class="form-control js-oxValidate js-oxValidate_notEmpty" size="20" id="novalnet_sepa_holder" name="dynvalue[novalnet_sepa_holder]" autocomplete="off" value="[{$oxcmp_user->oxuser__oxfname->value}] [{$oxcmp_user->oxuser__oxlname->value}]" onkeypress="return isValidKeySepa(event);">
            </div>
        </div>
        <div class="form-group novalnet_sepa_new_acc" [{$displaySepaForm}]>
            <label class="req control-label col-lg-3">[{ oxmultilang ident="NOVALNET_COUNTRY" }]</label>
            <div class="col-lg-9">
                <select id="novalnet_sepa_country" class = "form-control">
                    [{foreach from=$oViewConf->getCountryList() item=country}]
                        <option value="[{$country->oxcountry__oxisoalpha2->value}]">[{$country->oxcountry__oxtitle->value}]</option>
                        [{if $oxcmp_user->oxuser__oxcountryid->value == $country->oxcountry__oxid->value}]
                            [{ assign var=countryid value=$country->oxcountry__oxisoalpha2->value}]
                            [{oxscript add="$('#novalnet_sepa_country').val('$countryid');"}]
                        [{/if}]
                    [{/foreach }]
                </select>
            </div>
        </div>
        <div class="form-group novalnet_sepa_new_acc" [{$displaySepaForm}]>
            <label class="req control-label col-lg-3">[{ oxmultilang ident="NOVALNET_SEPA_IBAN" }]</label>
            <div class="col-lg-9">
                <input type="text" class="form-control js-oxValidate js-oxValidate_notEmpty" size="20" id="novalnet_sepa_acc_no" autocomplete="off" onkeypress="return isValidKeySepa(event);"><span id="novalnet_sepa_iban_span"></span>
            </div>
        </div>
        <div class="form-group novalnet_sepa_new_acc" [{$displaySepaForm}]>
            <label class="req control-label col-lg-3">[{ oxmultilang ident="NOVALNET_SEPA_BIC" }]</label>
            <div class="col-lg-9">
                <input type="text" class="form-control" size="20" id="novalnet_sepa_bank_code" autocomplete="off" onkeypress="return isValidKeySepa(event);"><span id="novalnet_sepa_bic_span"></span>
            </div>
        </div>
          [{if !empty($smarty.session.blGuaranteeEnablednovalnetsepa) && empty($smarty.session.blGuaranteeForceDisablednovalnetsepa) }]
               <div class="form-group novalnet_sepa_new_acc" [{$displaySepaForm}]>
                    <label class="req control-label col-lg-3">[{ oxmultilang ident="NOVALNET_BIRTH_DATE" }]</label>
                    <div class="col-lg-9">
                        <input type="text" class="form-control" size="20" id="novalnet_sepa_birth_date" name="dynvalue[birthdatenovalnetsepa]" value="[{$oView->getNovalnetBirthDate()}]" placeholder="YYYY-MM-DD" autocomplete="off">
                    </div>
            </div>
            [{/if}]
        <div class="form-group novalnet_sepa_new_acc" [{$displaySepaForm}]>
            <label class="req control-label col-lg-3">&nbsp;</label>
            <div class="col-lg-9">
                <input type="checkbox" id="novalnet_sepa_mandate_confirm"/>&nbsp;[{ oxmultilang ident="NOVALNET_SEPA_MANDATE_TERMS" }]
                <span class="novalnetloader" id="novalnet_sepa_loader"></span>
                <input type="hidden" id="novalnet_sepa_invalid_message" value="[{ oxmultilang ident="NOVALNET_SEPA_INVALID_DETAILS" }]">
                <input type="hidden" id="novalnet_sepa_unconfirm_message" value="[{ oxmultilang ident="NOVALNET_SEPA_UNCONFIRM_DETAILS" }]">
                <input type="hidden" id="novalnet_sepa_country_invalid_message" value="[{ oxmultilang ident="NOVALNET_SEPA_INVALID_COUNTRY" }]">
                <input type="hidden" id="novalnet_sepa_merchant_invalid_message" value="[{ oxmultilang ident="NOVALNET_INVALID_MERCHANT_DETAILS" }]">
                <input type="hidden" id="novalnet_sepa_vendor_id" value="[{$aSepaDetails.iVendorId}]">
                <input type="hidden" id="novalnet_sepa_vendor_authcode" value="[{$aSepaDetails.sAuthCode}]">
                <input type="hidden" id="novalnet_remote_ip" value="[{$oView->getNovalnetRemoteIp()}]">
                <input type="hidden" id="novalnet_sepa_iban" value="">
                <input type="hidden" id="novalnet_sepa_bic" value="">
            </div>
            [{oxscript include=$oViewConf->getModuleUrl('novalnet', 'out/src/js/novalnetsepa.js')}]
            [{oxstyle  include=$oViewConf->getModuleUrl('novalnet', 'out/src/css/novalnet.css')}]
        </div>
        [{if $oView->getFraudModuleStatus($sPaymentID) }]
            [{if $oView->getNovalnetConfig('iCallbacknovalnetsepa') == 1}]
                <div class="form-group novalnet_sepa_new_acc" id="novalnet_sepa_form" [{$displaySepaForm}]>
                    <label class="req control-label col-lg-3">[{ oxmultilang ident="NOVALNET_FRAUD_MODULE_PHONE" }]</label>
                    <div class="col-lg-9">
                        <input type="text" class="form-control js-oxValidate js-oxValidate_notEmpty" size="20" name="dynvalue[pinbycall_novalnetsepa]" autocomplete="off" value="[{$oxcmp_user->oxuser__oxfon->value}]">
                    </div>
                </div>
            [{elseif $oView->getNovalnetConfig('iCallbacknovalnetsepa') == 2}]
                <div class="form-group novalnet_sepa_new_acc" id="novalnet_sepa_form" [{$displaySepaForm}]>
                    <label class="req control-label col-lg-3">[{ oxmultilang ident="NOVALNET_FRAUD_MODULE_MOBILE" }]</label>
                    <div class="col-lg-9">
                        <input type="text" class="form-control js-oxValidate js-oxValidate_notEmpty" size="20" name="dynvalue[pinbysms_novalnetsepa]" autocomplete="off" value="[{$oxcmp_user->oxuser__oxmobfon->value}]">
                    </div>
                </div>
            [{/if}]
        [{/if}]
        <input type="hidden" name="dynvalue[novalnet_sepa_uniqueid]" id="novalnet_sepa_uniqueid" value="[{if isset($dynvalue.novalnet_sepa_uniqueid) }][{ $dynvalue.novalnet_sepa_uniqueid }][{else}][{ $oView->getUniqueid() }][{/if}]">
        <input type="hidden" name="dynvalue[novalnet_sepa_hash]" id="novalnet_sepa_hash" value="[{if isset($dynvalue.novalnet_sepa_hash) && $oView->getNovalnetConfig('blAutoRefill')}][{$dynvalue.novalnet_sepa_hash}][{else}][{$oView->getLastSepaHash()}][{/if}]">
 [{/if}]
 [{else}]
    <ul class="form" id="novalnet_sepa_form" style="width:100%;">
        [{if !empty($smarty.session.sCallbackTidnovalnetsepa)}]
            [{if in_array($oView->getNovalnetConfig('iCallbacknovalnetsepa'), array(1, 2))}]
                <li>
                    <label>[{ oxmultilang ident="NOVALNET_FRAUD_MODULE_PIN" }]</label>
                    <input type="text" size="20" name="dynvalue[pinno_novalnetsepa]" autocomplete="off" value="">
                </li>
                <li>
                    <label>&nbsp;</label>
                    <input type="checkbox" size="20" name="dynvalue[newpin_novalnetsepa]">&nbsp;[{ oxmultilang ident="NOVALNET_FRAUD_MODULE_FORGOT_PIN" }]
                </li>
            [{/if}]
        [{else}]
            [{assign var="aSepaDetails" value=$oView->getNovalnetPaymentDetails($sPaymentID)}]
            [{assign var="displaySepaForm" value="style='width:100%;'"}]
            [{if $aSepaDetails.iShopType == 1}]
                [{assign var="displaySepaForm" value="style='width:100%; display:none;'"}]
                <input type="hidden" name="dynvalue[novalnet_sepa_new_details]" id="novalnet_sepa_new_details" value=[{$aSepaDetails.blOneClick}]>
                <li class='novalnet_sepa_ref_acc'>
                    <table>
                        <tr>
                            <td colspan="2"><span id="novalnet_sepa_ref_acc" style="color:blue; text-decoration:underline; cursor:pointer;" onclick="changeSepaAccountType(event, 'novalnet_sepa_new_acc')">[{oxmultilang ident="NOVALNET_NEW_ACCOUNT_DETAILS"}]</span></td>
                        </tr>
                        <tr>
                            <td><label>[{oxmultilang ident="NOVALNET_SEPA_HOLDER_NAME" }]</label></td>
                            <td><label>[{$aSepaDetails.bankaccount_holder}]</label></td>
                        </tr>
                        <tr>
                            <td><label>IBAN</label></td>
                            <td><label>[{$aSepaDetails.iban}]</label></td>
                        </tr>
                        [{if $aSepaDetails.bic != '123456'}]
                            <tr>
                                <td><label>BIC</label></td>
                                <td><label>[{$aSepaDetails.bic}]</label></td>
                            </tr>
                        [{/if}]
                    </table>
                </li>
                <li class='novalnet_sepa_new_acc' [{$displaySepaForm}]>
                    <span id='novalnet_sepa_new_acc' style="color:blue; text-decoration:underline; cursor:pointer;" onclick="changeSepaAccountType(event, 'novalnet_sepa_ref_acc')">[{oxmultilang ident="NOVALNET_GIVEN_ACCOUNT_DETAILS"}]</span>
                </li>
            [{/if}]
            <li class='novalnet_sepa_new_acc' [{$displaySepaForm}]>
                <label>[{ oxmultilang ident="NOVALNET_SEPA_HOLDER_NAME" }]</label>
                <input type="text" class="js-oxValidate js-oxValidate_notEmpty" size="20" id="novalnet_sepa_holder" name="dynvalue[novalnet_sepa_holder]" autocomplete="off" value="[{$oxcmp_user->oxuser__oxfname->value}] [{$oxcmp_user->oxuser__oxlname->value}]" onkeypress="return isValidKeySepa(event);">
                <p class="oxValidateError">
                    <span class="js-oxError_notEmpty">[{ oxmultilang ident="ERROR_MESSAGE_INPUT_NOTALLFIELDS" }]</span>
                </p>
            </li>
            <li class='novalnet_sepa_new_acc' [{$displaySepaForm}]>
                <label>[{ oxmultilang ident="NOVALNET_COUNTRY" }]</label>
                <select id="novalnet_sepa_country">
                    [{foreach from=$oViewConf->getCountryList() item=country}]
                        <option value="[{$country->oxcountry__oxisoalpha2->value}]">[{$country->oxcountry__oxtitle->value}]</option>
                        [{if $oxcmp_user->oxuser__oxcountryid->value == $country->oxcountry__oxid->value}]
                            [{ assign var=countryid value=$country->oxcountry__oxisoalpha2->value}]
                            [{oxscript add="$('#novalnet_sepa_country').val('$countryid');"}]
                        [{/if}]
                    [{/foreach }]
                </select>
            </li>
            <li class='novalnet_sepa_new_acc' [{$displaySepaForm}]>
                <label>[{ oxmultilang ident="NOVALNET_SEPA_IBAN" }]</label>
                <input type="text" class="js-oxValidate js-oxValidate_notEmpty" size="20" id="novalnet_sepa_acc_no" autocomplete="off" onkeypress="return isValidKeySepa(event);">&nbsp;<span id="novalnet_sepa_iban_span"></span>
                <p class="oxValidateError">
                    <span class="js-oxError_notEmpty">[{ oxmultilang ident="ERROR_MESSAGE_INPUT_NOTALLFIELDS" }]</span>
                </p>
            </li>
            <li class='novalnet_sepa_new_acc' [{$displaySepaForm}]>
                <label>[{ oxmultilang ident="NOVALNET_SEPA_BIC" }]</label>
                <input type="text" size="20" id="novalnet_sepa_bank_code" autocomplete="off" onkeypress="return isValidKeySepa(event);">&nbsp;<span id="novalnet_sepa_bic_span"></span>
            </li>
            [{if !empty($smarty.session.blGuaranteeEnablednovalnetsepa) && empty($smarty.session.blGuaranteeForceDisablednovalnetsepa) }]
                <li>
                    <label>[{ oxmultilang ident="NOVALNET_BIRTH_DATE" }]</label>
                    <input type="text" size="20" id="novalnet_sepa_birth_date" name="dynvalue[birthdatenovalnetsepa]"
                    value="[{$oView->getNovalnetBirthDate()}]" placeholder="YYYY-MM-DD" autocomplete="off">
                </li>
            [{/if}]
            <li class='novalnet_sepa_new_acc' [{$displaySepaForm}] style="width:100%;">
                <label>&nbsp;</label>
                <input type="checkbox" id="novalnet_sepa_mandate_confirm">&nbsp;[{ oxmultilang ident="NOVALNET_SEPA_MANDATE_TERMS" }]
                <span class="novalnetloader" id="novalnet_sepa_loader"></span>
                <input type="hidden" id="novalnet_sepa_invalid_message" value="[{ oxmultilang ident="NOVALNET_SEPA_INVALID_DETAILS" }]">
                <input type="hidden" id="novalnet_sepa_unconfirm_message" value="[{ oxmultilang ident="NOVALNET_SEPA_UNCONFIRM_DETAILS" }]">
                <input type="hidden" id="novalnet_sepa_country_invalid_message" value="[{ oxmultilang ident="NOVALNET_SEPA_INVALID_COUNTRY" }]">
                <input type="hidden" id="novalnet_sepa_merchant_invalid_message" value="[{ oxmultilang ident="NOVALNET_INVALID_MERCHANT_DETAILS" }]">
                <input type="hidden" id="novalnet_sepa_vendor_id" value="[{$aSepaDetails.iVendorId}]">
                <input type="hidden" id="novalnet_sepa_vendor_authcode" value="[{$aSepaDetails.sAuthCode}]">
                <input type="hidden" id="novalnet_remote_ip" value="[{$oView->getNovalnetRemoteIp()}]">
                <input type="hidden" id="novalnet_sepa_iban" value="">
                <input type="hidden" id="novalnet_sepa_bic" value="">
                [{oxscript include=$oViewConf->getModuleUrl('novalnet', 'out/src/js/novalnetsepa.js')}]
                [{oxstyle  include=$oViewConf->getModuleUrl('novalnet', 'out/src/css/novalnet.css')}]
            </li>

            [{if $oView->getFraudModuleStatus($sPaymentID) }]
                [{if $oView->getNovalnetConfig('iCallbacknovalnetsepa') == 1}]
                    <li class='novalnet_sepa_new_acc' [{$displaySepaForm}]>
                        <label>[{ oxmultilang ident="NOVALNET_FRAUD_MODULE_PHONE" }]</label>
                        <input type="text" class="js-oxValidate js-oxValidate_notEmpty" size="20" name="dynvalue[pinbycall_novalnetsepa]" autocomplete="off" value="[{$oxcmp_user->oxuser__oxfon->value}]">
                        <p class="oxValidateError">
                            <span class="js-oxError_notEmpty">[{ oxmultilang ident="ERROR_MESSAGE_INPUT_NOTALLFIELDS" }]</span>
                        </p>
                    </li>
                [{elseif $oView->getNovalnetConfig('iCallbacknovalnetsepa') == 2}]
                    <li class='novalnet_sepa_new_acc' [{$displaySepaForm}]>
                        <label>[{ oxmultilang ident="NOVALNET_FRAUD_MODULE_MOBILE" }]</label>
                        <input type="text" class="js-oxValidate js-oxValidate_notEmpty" size="20" name="dynvalue[pinbysms_novalnetsepa]" autocomplete="off" value="[{$oxcmp_user->oxuser__oxmobfon->value}]">
                        <p class="oxValidateError">
                            <span class="js-oxError_notEmpty">[{ oxmultilang ident="ERROR_MESSAGE_INPUT_NOTALLFIELDS" }]</span>
                        </p>
                    </li>
                [{/if}]
            [{/if}]
            <input type="hidden" name="dynvalue[novalnet_sepa_uniqueid]" id="novalnet_sepa_uniqueid" value="[{if isset($dynvalue.novalnet_sepa_uniqueid) }][{ $dynvalue.novalnet_sepa_uniqueid }][{else}][{ $oView->getUniqueid() }][{/if}]">
            <input type="hidden" name="dynvalue[novalnet_sepa_hash]" id="novalnet_sepa_hash" value="[{if isset($dynvalue.novalnet_sepa_hash) && $oView->getNovalnetConfig('blAutoRefill') }][{$dynvalue.novalnet_sepa_hash}][{else}][{$oView->getLastSepaHash()}][{/if}]">
        [{/if}]
    </ul>
[{/if}]