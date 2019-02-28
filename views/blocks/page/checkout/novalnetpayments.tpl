[{if in_array($sPaymentID, array('novalnetcreditcard', 'novalnetsepa', 'novalnetinvoice', 'novalnetprepayment', 'novalnetonlinetransfer', 'novalnetideal', 'novalnetpaypal', 'novalneteps', 'novalnetgiropay', 'novalnetprzelewy24','novalnetbarzahlen'))}]
    [{assign var="oConf" value=$oViewConf->getConfig()}]
        [{assign var="sPaymentHomePageUrl" value=$sPaymentID|cat:"_url"}]
    
    <dl>
        <dt>
            <input id="payment_[{$sPaymentID}]" type="radio" name="paymentid" value="[{$sPaymentID}]" [{if $oView->getCheckedPaymentId() == $paymentmethod->oxpayments__oxid->value}]checked[{/if}]>
            <label for="payment_[{$sPaymentID}]"><b>[{$paymentmethod->oxpayments__oxdesc->value}]
         [{if $paymentmethod->getPrice()}]
            [{assign var="oPaymentPrice" value=$paymentmethod->getPrice()}]
            [{if $oViewConf->isFunctionalityEnabled('blShowVATForPayCharge')}]
                ([{oxprice price=$oPaymentPrice->getNettoPrice() currency=$currency}]
                [{if $oPaymentPrice->getVatValue() > 0}]
                    [{oxmultilang ident="PLUS_VAT"}] [{oxprice price=$oPaymentPrice->getVatValue() currency=$currency}]
                [{/if}])
			 [{else}]
					([{oxprice price=$oPaymentPrice->getBruttoPrice() currency=$currency}])
             [{/if}]
          [{/if}]
            </b></label>          
              [{if $oView->getNovalnetConfig('blPaymentLogo')}]
                <span>
                       [{if $sPaymentID == "novalnetcreditcard"}]
						<a href="[{ oxmultilang ident='NOVALNETCREDITCARD_URL' }]" target="_blank" title="[{$paymentmethod->oxpayments__oxdesc->value}]" style="text-decoration:none;">
                            <img src="[{$oViewConf->getModuleUrl('novalnet','out/img/')}]novalnetvisa.png" alt="[{$paymentmethod->oxpayments__oxdesc->value}]"/>
                            <img src="[{$oViewConf->getModuleUrl('novalnet','out/img/')}]novalnetmaster.png" alt="[{$paymentmethod->oxpayments__oxdesc->value}]"/>

                            [{if $oView->getNovalnetConfig('blAmexActive')}]
                                <img src="[{$oViewConf->getModuleUrl('novalnet','out/img/')}]novalnetamex.png" alt="[{$paymentmethod->oxpayments__oxdesc->value}]"/>
                            [{/if}]
                            [{if $oView->getNovalnetConfig('blMaestroActive')}]
                                <img src="[{$oViewConf->getModuleUrl('novalnet','out/img/')}]novalnetmaestro.png" alt="[{$paymentmethod->oxpayments__oxdesc->value}]"/>
                            [{/if}]                            
                        </a>
                        [{/if}]
                        [{if in_array($sPaymentID, array('novalnetsepa', 'novalnetinvoice', 'novalnetprepayment', 'novalnetpaypal', 'novalnetbarzahlen', 'novalnetonlinetransfer', 'novalnetideal', 'novalneteps', 'novalnetgiropay', 'novalnetprzelewy24')) }]
							<a href="[{oxmultilang ident=$sPaymentHomePageUrl|upper}]" target="_blank" title="[{$paymentmethod->oxpayments__oxdesc->value}]" style="text-decoration:none;">
							<img src="[{$oViewConf->getModuleUrl('novalnet','out/img/')}][{$sPaymentID}].png" alt="[{$paymentmethod->oxpayments__oxdesc->value}]"/>
							</a>							
                        [{/if}]
                                        
                </span>
            [{/if}]
        </dt>
        <dd class="[{if $oView->getCheckedPaymentId() == $paymentmethod->oxpayments__oxid->value}]activePayment[{/if}]">
          [{if $sPaymentID == "novalnetcreditcard"}]
                [{include file="novalnetcreditcard.tpl"}]
            [{elseif $sPaymentID == "novalnetsepa"}]
                [{include file="novalnetsepa.tpl"}]
            [{elseif $sPaymentID == "novalnetinvoice"}]
                [{include file="novalnetinvoice.tpl"}]
            [{elseif $sPaymentID == "novalnetpaypal"}]
                [{include file="novalnetpaypal.tpl"}]
            [{/if}]
            [{if !in_array($sPaymentID, array('novalnetcreditcard', 'novalnetpaypal')) }]
                [{block name="checkout_payment_longdesc"}]
                 <div class="alert alert-info desc">
				 [{if $sPaymentID == 'novalnetsepa'}]
                     <a data-toggle="collapse" style = "cursor:pointer;" data-target="#sepa_mandate_information">
					 [{ oxmultilang ident='NOVALNET_SEPA_DECLARATION' }]
                     </a>
                     <div class="collapse panel panel-default" id="sepa_mandate_information" style="padding:5px;">
                     [{ oxmultilang ident='NOVALNET_SEPA_AUTHORIZE' }]
                     [{ oxmultilang ident='NOVALNET_SEPA_CREDITOR_IDENTIFIER' }]
                     [{ oxmultilang ident='NOVALNET_SEPA_CLAIM_NOTES' }]
                     </div>
                 [{/if}]
                 [{if ($sPaymentID == 'novalnetsepa' && !empty($smarty.session.blGuaranteeForceDisablednovalnetsepa)) || ($sPaymentID == 'novalnetinvoice' && !empty($smarty.session.blGuaranteeForceDisablednovalnetinvoice)) }]
                            <span style="color:red">[{ oxmultilang ident='NOVALNET_GUARANTEE_FORCE_DISABLED_MESSAGE' }]</span><br><br>
                            [{/if}]
                            
                            [{if ($sPaymentID == 'novalnetsepa') && !empty($smarty.session.blGuaranteeForceDisablednovalnetsepa)}]
                            [{if ($sPaymentID == 'novalnetsepa') && !empty($smarty.session.blGuaranteeAmtnovalnetsepa) && ($smarty.session.dGetGuaranteeAmtnovalnetsepa >= 999)}]
                            <span style="color:red">[{ oxmultilang ident='NOVALNET_GUARANTEE_AMOUNT_ERROR_MESSAGE' }] [{$smarty.session.dGetGuaranteeAmountnovalnetsepa}] [{$oView->getCurrencyalue()}]</span><br><br>                            
                            [{/if}]
                            [{/if}]
                            
                            [{if ($sPaymentID == 'novalnetinvoice' && !empty($smarty.session.blGuaranteeForceDisablednovalnetinvoice)) }]
                            [{if ($sPaymentID == 'novalnetinvoice') && !empty($smarty.session.blGuaranteeAmtnovalnetinvoice) && ($smarty.session.dGetGuaranteeAmtnovalnetinvoice >= 999)}]
                            <span style="color:red">[{ oxmultilang ident='NOVALNET_GUARANTEE_AMOUNT_ERROR_MESSAGE' }] [{$smarty.session.dGetGuaranteeAmountnovalnetinvoice}] [{$oView->getCurrencyalue()}]</span><br><br>                            
                            [{/if}]
                            [{/if}]
                            
                            [{if ($sPaymentID == 'novalnetsepa' && !empty($smarty.session.blGuaranteeForceDisablednovalnetsepa)) || ($sPaymentID == 'novalnetinvoice' && !empty($smarty.session.blGuaranteeForceDisablednovalnetinvoice)) }]
                                                        
                            [{if ($sPaymentID == 'novalnetsepa' && !empty($smarty.session.blGuaranteeCurrencynovalnetsepa)) || ($sPaymentID == 'novalnetinvoice' && !empty($smarty.session.blGuaranteeCurrencynovalnetinvoice)) }]
                            <span style="color:red">[{ oxmultilang ident='NOVALNET_GUARANTEE_CURRENCY_ERROR_MESSAGE' }]</span><br><br>
                            [{/if}]
                            [{if ($sPaymentID == 'novalnetsepa' && !empty($smarty.session.blGuaranteeAddressnovalnetsepa)) || ($sPaymentID == 'novalnetinvoice' && !empty($smarty.session.blGuaranteeAddressnovalnetinvoice)) }]
                            <span style="color:red">[{ oxmultilang ident='NOVALNET_GUARANTEE_ADDRESS_MISMATCH_ERROR_MESSAGE' }]</span><br><br>
                            [{/if}]
                            [{if ($sPaymentID == 'novalnetsepa' && !empty($smarty.session.blGuaranteeCountrynovalnetsepa)) || ($sPaymentID == 'novalnetinvoice' && !empty($smarty.session.blGuaranteeCountrynovalnetinvoice)) }]
                            <span style="color:red">[{ oxmultilang ident='NOVALNET_GUARANTEE_COUNTRY_ERROR_MESSAGE' }]</span><br><br>
                            [{/if}]
                                
                        [{/if}]
                        [{if $paymentmethod->oxpayments__oxlongdesc->value|trim}]
                            [{ $paymentmethod->oxpayments__oxlongdesc->getRawValue()}]

                            [{if in_array($sPaymentID, array('novalnetonlinetransfer', 'novalnetideal', 'novalneteps', 'novalnetgiropay', 'novalnetprzelewy24')) }]
                                <br>[{ oxmultilang ident='NOVALNET_REDIRECT_DESCRIPTION_MESSAGE' }]
                            [{/if}]
                        [{/if}]
                        [{if $oView->getNovalnetNotification($sPaymentID) != '' }]
                            <br><br>[{$oView->getNovalnetNotification($sPaymentID)}]
                        [{/if}]
                        [{if $oView->getNovalnetTestmode($sPaymentID) }]
                            <br><br><span style="color:red">[{ oxmultilang ident='NOVALNET_TEST_MODE_MESSAGE' }]</span>
                        [{/if}]
                        [{if $oView->getNovalnetZeroAmountStatus($sPaymentID) == '2' && $sPaymentID != 'novalnetsepa'}]
                            <br><span style="color:red">[{ oxmultilang ident='NOVALNET_ZEROAMOUNT_BOOKING_MESSAGE' }]</span>
                        [{/if}]
                        [{if $oView->getNovalnetZeroAmountStatus($sPaymentID) == '2' && $sPaymentID == 'novalnetsepa'}]
                        [{if !empty($smarty.session.blGuaranteeEnablednovalnetsepa)}]
                            <br><span style="color:red">[{ oxmultilang ident='NOVALNET_ZEROAMOUNT_BOOKING_MESSAGE' }]</span>
                        [{/if}]
                        [{/if}]
                    </div>
                [{/block}]
            [{/if}]
        </dd>
    </dl>
[{else}]
    [{$smarty.block.parent}]
[{/if}]
