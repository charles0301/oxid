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
 */

namespace oe\novalnet\Controller\Admin;

use oe\novalnet\Classes\NovalnetUtil;

/**
 * Class AdminController.
 */
class AdminController extends \OxidEsales\Eshop\Application\Controller\Admin\ShopConfiguration
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = 'novalnetconfig.tpl';

    /**
     * Auto Config Url.
     *
     * @var string
     */
    protected $_sAutoConfigUrl = 'https://payport.novalnet.de/autoconfig';

    /**
     * Passes Novalnet configuration parameters
     * to Smarty and returns name of template file "novalnetconfig.tpl".
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        $myConfig = \OxidEsales\Eshop\Core\Registry::getConfig();
        $this->_aViewData['aNovalnetConfig'] = $myConfig->getShopConfVar('aNovalnetConfig', '', 'novalnet');
        return $this->_sThisTemplate;
    }

     /**
     * Saves changed Novalnet configuration parameters.
     */
    public function save()
    {
        $myConfig = \OxidEsales\Eshop\Core\Registry::getConfig();
        $aNovalnetConfig = $myConfig->getRequestParameter('aNovalnetConfig');

        // checks to validate the Novalnet configuration before saving
        if ($this->_validateNovalnetConfig($aNovalnetConfig) === true) {
           $aNovalnetConfig = array_map('strip_tags', $aNovalnetConfig);
           $myConfig->saveShopConfVar('arr', 'aNovalnetConfig', $aNovalnetConfig, '', 'novalnet');
        }
    }


    /**
     * Validates Novalnet credentials
     *
     * @param array $aNovalnetConfig
     *
     * @return boolean
     */
    private function _validateNovalnetConfig($aNovalnetConfig)
    {
        $oLang           = \OxidEsales\Eshop\Core\Registry::getLang();
        $aNovalnetConfig = array_map('trim', $aNovalnetConfig);
        if (empty($aNovalnetConfig['iActivationKey']) || empty($aNovalnetConfig['sTariffId'])) {
            $this->_aViewData['sNovalnetError'] = $oLang->translateString('NOVALNET_INVALID_CONFIG_ERROR');
            return false;
        }

        if (!empty($aNovalnetConfig['iDueDatenovalnetsepa']) && (!is_numeric($aNovalnetConfig['iDueDatenovalnetsepa']) || $aNovalnetConfig['iDueDatenovalnetsepa'] > 14)) {
            $this->_aViewData['sNovalnetError'] = $oLang->translateString('NOVALNET_INVALID_SEPA_CONFIG_ERROR');
            return false;
        }

        foreach (['novalnetinvoice', 'novalnetcreditcard', 'novalnetsepa', 'novalnetpaypal'] as $sPaymentName) {
            if (!empty($aNovalnetConfig['dOnholdLimit'. $sPaymentName]) && !is_numeric($aNovalnetConfig['dOnholdLimit'. $sPaymentName])) {
                $this->_aViewData['sNovalnetError'] = $oLang->translateString('NOVALNET_INVALID_CONFIG_ERROR');
                return false;
            }
        }
        foreach (['novalnetsepa', 'novalnetinvoice'] as $sPaymentName) {
            if ($aNovalnetConfig['dGuaranteeMinAmount' . $sPaymentName] != '' && (!is_numeric($aNovalnetConfig['dGuaranteeMinAmount' . $sPaymentName]) || $aNovalnetConfig['dGuaranteeMinAmount' . $sPaymentName] < 999)) {
                $this->_aViewData['sNovalnetError'] = $oLang->translateString('NOVALNET_INVALID_GUARANTEE_MINIMUM_AMOUNT_ERROR');
                return false;
            }
        }
        return true;
    }

    /**
     * Get hash value
     *
     */
    public function getMerchantDetails()
    {
        $oNovalnetUtil = oxNew(NovalnetUtil::class);
        $aRequest  = ['hash' => trim($this->getConfig()->getRequestParameter('hash')),
                      'lang' => $oNovalnetUtil->oLang->getLanguageAbbr()];
        $aResponse = $oNovalnetUtil->doCurlRequest($aRequest, $this->_sAutoConfigUrl, true, false);
        echo json_encode(['details'=> 'true','response' => $aResponse]);
        exit();
    }

    /**
     * Get Shop admin URL
     *
     * @return string
     */
    public function getNovalnetShopUrl() {
        $oConfig = \OxidEsales\Eshop\Core\Registry::getConfig();
        // override cause of admin dir
        $sURL = $oConfig->getConfigParam('sShopURL') . $oConfig->getConfigParam('sAdminDir') . "/";

        if ($oConfig->getConfigParam('sAdminSSLURL')) {
            $sURL = $oConfig->getConfigParam('sAdminSSLURL');
        }
        return $sURL;
    }

}
