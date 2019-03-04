<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU General Public License
 * that is mentioned with this package in the file Installation_Guide-oxid_6.0.x-6.1.2_novalnet_11.3.0.pdf
 *
 * @author Novalnet <technic@novalnet.de>
 * @copyright Novalnet AG
 * @license GNU General Public License
 *
 */

namespace oe\novalnet\Controller;

use oe\novalnet\Classes\NovalnetUtil;

/**
 * Class OrderController.
 */
class OrderController extends OrderController_parent
{
    /**
     * Receives Novalnet response for redirect payment
     *
     * @return boolean
     */
    public function novalnetGatewayReturn()
    {
        $oConfig = $this->getConfig();
        $oNovalnetUtil = oxNew(NovalnetUtil::class);
        $oNovalnetUtil->oSession->deleteVariable('sNovalnetRedirectURL');
        $oNovalnetUtil->oSession->deleteVariable('aNovalnetRedirectRequest');
        $sKey    = $oConfig->getRequestParameter('key') ? $oConfig->getRequestParameter('key') : $oConfig->getRequestParameter('payment_id');

        // checks to verify the current payment is Novalnet payment
        if (in_array($sKey, ['6', '33', '34', '49', '50', '69', '78'])) {
            $sInputVal3                   = $oConfig->getRequestParameter('inputval3');
            $sInputVal4                   = $oConfig->getRequestParameter('inputval4');
            $_POST['shop_lang']           = ($sInputVal3) ? $sInputVal3 : $_POST['shop_lang'] ;
            $_POST['stoken']              = ($sInputVal4) ? $sInputVal4 : $_POST['stoken'];
            $_POST['actcontrol']          = $oConfig->getTopActiveView()->getViewConfig()->getTopActiveClassName();
            $_POST['sDeliveryAddressMD5'] = $this->getDeliveryAddressMD5();
            if (!$oConfig->getRequestParameter('ord_agb') && $oConfig->getConfigParam( 'blConfirmAGB'))
                $_POST['ord_agb'] = 1;

            if ($oConfig->getConfigParam('blEnableIntangibleProdAgreement')) {
                if (!$oConfig->getRequestParameter('oxdownloadableproductsagreement'))
                    $_POST['oxdownloadableproductsagreement'] = 1;

                if (!$oConfig->getRequestParameter('oxserviceproductsagreement'))
                    $_POST['oxserviceproductsagreement'] = 1;
            }
        }
        $oNovalnetUtil->oLang->setBaseLanguage($oConfig->getRequestParameter('shop_lang'));
        return $this->execute();
    }
}
?>
