<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU General Public License
 * that is mentioned with this package in the file Installation_Guide.pdf
 *
 * @author Novalnet <technic@novalnet.de>
 * @copyright Novalnet AG
 * @license GNU General Public License
 *
 * Script : NovalnetThankyou.php
 *
 */
namespace oe\novalnet\Controller;

class NovalnetThankyou extends NovalnetThankyou_parent {

    public function render() {
        $sTemplate = parent::render();
        $oOrder     = $this->getOrder();
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(\OxidEsales\Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC);
        $iOrderNr = $oOrder->oxorder__oxordernr->value;
        
        if ($oOrder->oxorder__oxpaymenttype->value == 'novalnetbarzahlen') {
            $sSql = 'SELECT ADDITIONAL_DATA FROM novalnet_transaction_detail where ORDER_NO = "' . $iOrderNr . '"';
            $aPaymentRef = $oDb->getRow($sSql);
            $sData = unserialize($aPaymentRef['ADDITIONAL_DATA']);
            $aToken = explode('|', $sData['cp_checkout_token']);
            $sBarzahlenLink = ($aToken[1] == 1) ? 'https://cdn.barzahlen.de/js/v2/checkout-sandbox.js' : 'https://cdn.barzahlen.de/js/v2/checkout.js';    
            $this->_aViewData['aNovalnetBarzahlensUrl'] = $sBarzahlenLink;
            $this->_aViewData['aNovalnetToken'] = $aToken[0];
        }
        return $sTemplate;
    }
}
