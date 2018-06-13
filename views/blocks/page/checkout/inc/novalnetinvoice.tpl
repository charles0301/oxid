[{if !empty($smarty.session.sCallbackTidnovalnetinvoice)}]
        [{if in_array($oView->getNovalnetConfig('iCallbacknovalnetinvoice'), array(1, 2))}]
            <div class="form-group">
                <label class="req control-label col-lg-3">[{ oxmultilang ident="NOVALNET_FRAUD_MODULE_PIN" }]</label>
                <div class="col-lg-9">
                    <input type="text" size="20" name="dynvalue[pinno_novalnetinvoice]" autocomplete="off" value="">
                </div>
            </div>
            <div class="form-group">
                <label class="req control-label col-lg-3">&nbsp;</label>
                <div class="col-lg-9">
                    <input type="checkbox" size="20" name="dynvalue[newpin_novalnetinvoice]">[{ oxmultilang ident="NOVALNET_FRAUD_MODULE_FORGOT_PIN" }]
                </div>
            </div>
        [{/if}]
[{else}]
        [{if !empty($smarty.session.blGuaranteeEnablednovalnetinvoice) }]
            <div class="form-group">
                <label class="req control-label col-lg-3">[{ oxmultilang ident="NOVALNET_BIRTH_DATE" }]</label>
                <div class="col-lg-9">
                    <input type="text" size="20" id="novalnet_invoice_birth_date" name="dynvalue[birthdatenovalnetinvoice]"  value="[{$oView->getNovalnetBirthDate()}]" placeholder="YYYY-MM-DD" autocomplete="off">
                </div>
            </div>
        [{/if}]
        [{if $oView->getFraudModuleStatus($sPaymentID) }]
            [{if $oView->getNovalnetConfig('iCallbacknovalnetinvoice') == 1}]
                <div class="form-group">
                    <label class="req control-label col-lg-3">[{ oxmultilang ident="NOVALNET_FRAUD_MODULE_PHONE" }]</label>
                    <div class="col-lg-9">
                        <input type="text" class="js-oxValidate js-oxValidate_notEmpty" size="20" name="dynvalue[pinbycall_novalnetinvoice]" autocomplete="off" value="[{$oxcmp_user->oxuser__oxfon->value}]" >
                    </div>
                </div>
            [{elseif $oView->getNovalnetConfig('iCallbacknovalnetinvoice') == 2}]
                <div class="form-group">
                    <label class="req control-label col-lg-3">[{ oxmultilang ident="NOVALNET_FRAUD_MODULE_MOBILE" }]</label>
                    <div class="col-lg-9">
                        <input type="text" class="js-oxValidate js-oxValidate_notEmpty" size="20" name="dynvalue[pinbysms_novalnetinvoice]" autocomplete="off" value="[{$oxcmp_user->oxuser__oxmobfon->value}]" >
                    </div>
                </div>
            [{/if}]
        [{/if}]
[{/if}]
