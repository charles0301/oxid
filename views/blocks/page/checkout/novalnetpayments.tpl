[{if in_array($sPaymentID, array('novalnetcreditcard', 'novalnetsepa', 'novalnetinvoice', 'novalnetprepayment', 'novalnetonlinetransfer', 'novalnetideal', 'novalnetpaypal', 'novalneteps', 'novalnetgiropay', 'novalnetprzelewy24','novalnetbarzahlen'))}]
    [{assign var="oConf" value=$oViewConf->getConfig()}]
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
                    <a href="[{ oxmultilang ident='NOVALNET_LINK_URL' }]" target="_blank" title="[{$paymentmethod->oxpayments__oxdesc->value}]" style="text-decoration:none;">
                        [{if $sPaymentID == "novalnetcreditcard"}]
                            <img src="[{$oViewConf->getModuleUrl('novalnet','out/img/')}]novalnetvisa.png" alt="[{$paymentmethod->oxpayments__oxdesc->value}]"/>
                            <img src="[{$oViewConf->getModuleUrl('novalnet','out/img/')}]novalnetmaster.png" alt="[{$paymentmethod->oxpayments__oxdesc->value}]"/>

                            [{if $oView->getNovalnetConfig('blAmexActive')}]
                                <img src="[{$oViewConf->getModuleUrl('novalnet','out/img/')}]novalnetamex.png" alt="[{$paymentmethod->oxpayments__oxdesc->value}]"/>
                            [{/if}]
                            [{if $oView->getNovalnetConfig('blMaestroActive')}]
                                <img src="[{$oViewConf->getModuleUrl('novalnet','out/img/')}]novalnetmaestro.png" alt="[{$paymentmethod->oxpayments__oxdesc->value}]"/>
                            [{/if}]
                            [{if $oView->getNovalnetConfig('blCartasiActive')}]
                                <img src="[{$oViewConf->getModuleUrl('novalnet','out/img/')}]novalnetcartasi.png" alt="[{$paymentmethod->oxpayments__oxdesc->value}]"/>
                            [{/if}]
                        [{else}]
                            <img src="[{$oViewConf->getModuleUrl('novalnet','out/img/')}][{$sPaymentID}].png" alt="[{$paymentmethod->oxpayments__oxdesc->value}]"/>
                        [{/if}]

                    </a>
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
                    <div class="alert alert-info col-lg-offset-3 desc">
                        [{if ($sPaymentID == 'novalnetsepa' && !empty($smarty.session.blGuaranteeForceDisablednovalnetsepa)) || ($sPaymentID == 'novalnetinvoice' && !empty($smarty.session.blGuaranteeForceDisablednovalnetinvoice)) }]
                            <span style="color:red">[{ oxmultilang ident='NOVALNET_GUARANTEE_FORCE_DISABLED_MESSAGE' }]</span><br><br>
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
                    </div>
                [{/block}]
            [{/if}]
        </dd>
    </dl>
[{else}]
    [{$smarty.block.parent}]
[{/if}]
