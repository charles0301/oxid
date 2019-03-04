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
        [{assign var="aShoppingDetails" value=$oView->getShoppingTypeDetails($sPaymentID)}]
        [{assign var="displaySepaForm" value="style='width:100%;'"}]
        [{if $aShoppingDetails.iShopType == 1 && $aSepaDetails != ''}]
            [{assign var="displaySepaForm" value="style='width:100%; display:none;'"}]
            <input type="hidden" name="dynvalue[novalnet_sepa_new_details]" id="novalnet_sepa_new_details" value=[{$aShoppingDetails.blOneClick}]>
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
            <div class="form-group novalnet_sepa_new_acc" [{$displaySepaForm}]>
                <label class="control-label col-lg-3"><span id="novalnet_sepa_new_acc" style="color:blue; text-decoration:underline; cursor:pointer;" onclick="changeSepaAccountType(event, 'novalnet_sepa_ref_acc')">[{oxmultilang ident="NOVALNET_GIVEN_ACCOUNT_DETAILS"}]</span></label>
            </div>
        [{/if}]
        <div class="form-group novalnet_sepa_new_acc" [{$displaySepaForm}]>
            <label class="req control-label col-lg-3">[{ oxmultilang ident="NOVALNET_SEPA_HOLDER_NAME" }]</label>
            <div class="col-lg-9">
                <input type="text" class="form-control js-oxValidate js-oxValidate_notEmpty" size="20" id="novalnet_sepa_holder" name="dynvalue[novalnet_sepa_holder]" autocomplete="off" value="[{$oxcmp_user->oxuser__oxfname->value}] [{$oxcmp_user->oxuser__oxlname->value}]" onkeypress="return isValidKeySepa(event);">
            </div>
        </div>
        <div class="form-group novalnet_sepa_new_acc" [{$displaySepaForm}]>
            <label class="req control-label col-lg-3">[{ oxmultilang ident="NOVALNET_SEPA_IBAN" }]</label>
            <div class="col-lg-9">
                <input type="text" class="form-control js-oxValidate js-oxValidate_notEmpty" size="20" id="novalnet_sepa_acc_no" name="dynvalue[novalnet_sepa_iban]" value= "[{$smarty.session.refillSepaiban}]" autocomplete="off"><span id="novalnet_sepa_iban_span"></span>
            </div>
        </div>
        [{if !empty($smarty.session.blGuaranteeEnablednovalnetsepa) && empty($smarty.session.blGuaranteeForceDisablednovalnetsepa) && $oView->getCompanyFieldValue() == '' }]
		   <div class="form-group">
				<label class="control-label col-lg-3">[{ oxmultilang ident="NOVALNET_BIRTH_DATE" }]</label>
				<div class="col-lg-9">
					<input type="text" class="form-control" size="20" id="novalnet_sepa_birth_date" name="dynvalue[birthdatenovalnetsepa]" value="[{$oView->getNovalnetBirthDate()}]" placeholder="YYYY-MM-DD" autocomplete="off">
				</div>
		</div>
		[{/if}]
            [{if $aShoppingDetails.iShopType == '1' }]
            <div class="form-group novalnet_sepa_new_acc" id="novalnet_sepa_save_card">
                    <label class="req control-label col-lg-3">&nbsp;</label>
                    <div class="col-lg-9">
						<input type="hidden" size="20" name="dynvalue[novalnet_sepa_save_card]" value="0">
                        <input type="checkbox" size="20" name="dynvalue[novalnet_sepa_save_card]" value="1" [{if $dynvalue.novalnet_sepa_save_card == 1}]checked="checked"[{/if}]>&nbsp;[{ oxmultilang ident="NOVALNET_SEPA_SAVE_CARD_DATA" }]
                    </div>
            </div>
            [{/if}]
			[{oxscript include=$oViewConf->getModuleUrl('novalnet', 'out/src/js/novalnetsepa.js')}]
			[{oxstyle  include=$oViewConf->getModuleUrl('novalnet', 'out/src/css/novalnet.css')}]
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
           [{assign var="aShoppingDetails" value=$oView->getShoppingTypeDetails($sPaymentID)}]
            [{assign var="displaySepaForm" value="style='width:100%;'"}]
            [{if $aShoppingDetails.iShopType == 1 && $aSepaDetails != ''}]
                [{assign var="displaySepaForm" value="style='width:100%; display:none;'"}]
                <input type="hidden" name="dynvalue[novalnet_sepa_new_details]" id="novalnet_sepa_new_details" value=[{$aShoppingDetails.blOneClick}]>
                <li class="novalnet_sepa_ref_acc">
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
                    </table>
                    <input type="hidden" id="novalnet_sepa_reference_tid" name="dynvalue[novalnet_sepa_reference_tid]" value=[{$aSepaDetails.tid}]>
                </li>
                <li class="novalnet_sepa_new_acc" [{$displaySepaForm}]>
                    <span id="novalnet_sepa_new_acc" style="color:blue; text-decoration:underline; cursor:pointer;" onclick="changeSepaAccountType(event, 'novalnet_sepa_ref_acc')">[{oxmultilang ident="NOVALNET_GIVEN_ACCOUNT_DETAILS"}]</span>
                </li>
            [{/if}]
            <li class="novalnet_sepa_new_acc" [{$displaySepaForm}]>
                <label>[{ oxmultilang ident="NOVALNET_SEPA_HOLDER_NAME" }]</label>
                <input type="text" class="js-oxValidate js-oxValidate_notEmpty" size="20" id="novalnet_sepa_holder" name="dynvalue[novalnet_sepa_holder]" autocomplete="off" value="[{$oxcmp_user->oxuser__oxfname->value}] [{$oxcmp_user->oxuser__oxlname->value}]" onkeypress="return isValidKeySepa(event);">
                <p class="oxValidateError">
                    <span class="js-oxError_notEmpty">[{ oxmultilang ident="ERROR_MESSAGE_INPUT_NOTALLFIELDS" }]</span>
                </p>
            </li>
            <li class="novalnet_sepa_new_acc" [{$displaySepaForm}]>
                <label>[{ oxmultilang ident="NOVALNET_SEPA_IBAN" }]</label>
                <input type="text" class="js-oxValidate js-oxValidate_notEmpty" size="20" id="novalnet_sepa_acc_no" autocomplete="off" name="dynvalue[novalnet_sepa_iban]" value= "[{$smarty.session.refillSepaiban}]" onkeypress="return isValidKeySepa(event);">&nbsp;<span id="novalnet_sepa_iban_span"></span>
                <p class="oxValidateError">
                    <span class="js-oxError_notEmpty">[{ oxmultilang ident="ERROR_MESSAGE_INPUT_NOTALLFIELDS" }]</span>
                </p>
            </li>
            [{if !empty($smarty.session.blGuaranteeEnablednovalnetsepa) && empty($smarty.session.blGuaranteeForceDisablednovalnetsepa) && $oView->getCompanyFieldValue() == '' }]
                <li>
                    <label>[{ oxmultilang ident="NOVALNET_BIRTH_DATE" }]</label>
                    <input type="text" size="20" id="novalnet_sepa_birth_date" name="dynvalue[birthdatenovalnetsepa]"
                    value="[{$oView->getNovalnetBirthDate()}]" placeholder="YYYY-MM-DD" autocomplete="off">
                </li>
            [{/if}]
            [{if $aShoppingDetails.iShopType == '1' }]
                <li class="novalnet_sepa_new_acc" [{$displaySepaForm}]>
                    <label>&nbsp;</label>
						<input type="hidden" size="20" name="dynvalue[novalnet_sepa_save_card]" value="0">
                        <input type="checkbox" size="20" name="dynvalue[novalnet_sepa_save_card]" value="1" [{if $dynvalue.novalnet_sepa_save_card == 1}]checked="checked"[{/if}]>&nbsp;[{ oxmultilang ident="NOVALNET_SEPA_SAVE_CARD_DATA" }]
                </li>
            [{/if}]
            <li class="novalnet_sepa_new_acc" [{$displaySepaForm}] style="width:100%;">
                [{oxscript include=$oViewConf->getModuleUrl('novalnet', 'out/src/js/novalnetsepa.js')}]
                [{oxstyle  include=$oViewConf->getModuleUrl('novalnet', 'out/src/css/novalnet.css')}]
            </li>
            [{if $oView->getFraudModuleStatus($sPaymentID) }]
                [{if $oView->getNovalnetConfig('iCallbacknovalnetsepa') == 1}]
                    <li class="novalnet_sepa_new_acc" [{$displaySepaForm}]>
                        <label>[{ oxmultilang ident="NOVALNET_FRAUD_MODULE_PHONE" }]</label>
                        <input type="text" class="js-oxValidate js-oxValidate_notEmpty" size="20" name="dynvalue[pinbycall_novalnetsepa]" autocomplete="off" value="[{$oxcmp_user->oxuser__oxfon->value}]">
                        <p class="oxValidateError">
                            <span class="js-oxError_notEmpty">[{ oxmultilang ident="ERROR_MESSAGE_INPUT_NOTALLFIELDS" }]</span>
                        </p>
                    </li>
                [{elseif $oView->getNovalnetConfig('iCallbacknovalnetsepa') == 2}]
                    <li class="novalnet_sepa_new_acc" [{$displaySepaForm}]>
                        <label>[{ oxmultilang ident="NOVALNET_FRAUD_MODULE_MOBILE" }]</label>
                        <input type="text" class="js-oxValidate js-oxValidate_notEmpty" size="20" name="dynvalue[pinbysms_novalnetsepa]" autocomplete="off" value="[{$oxcmp_user->oxuser__oxmobfon->value}]">
                        <p class="oxValidateError">
                            <span class="js-oxError_notEmpty">[{ oxmultilang ident="ERROR_MESSAGE_INPUT_NOTALLFIELDS" }]</span>
                        </p>
                    </li>
                [{/if}]
            [{/if}]
        [{/if}]
    </ul>
[{/if}]
