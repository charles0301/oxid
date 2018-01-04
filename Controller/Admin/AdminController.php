<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU General Public License
 * that is bundled with this package in the file license.txt
 *
 * @author Novalnet <technic@novalnet.de>
 * @copyright Novalnet
 * @license GNU General Public License
 *
 * Script : AdminController.php
 *
 */

namespace oe\novalnet\Controller\Admin;

use oe\novalnet\Classes\NovalnetUtil;

/**
 * Class AdminController.
 */
class AdminController extends \OxidEsales\Eshop\Application\Controller\Admin\ShopConfiguration
{

    protected $_sThisTemplate = 'novalnetconfig.tpl';

    /**
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

    public function save()
    {
        $oLang    = \OxidEsales\Eshop\Core\Registry::getLang();
        $myConfig = \OxidEsales\Eshop\Core\Registry::getConfig();
        $aNovalnetConfig = $myConfig->getRequestParameter('aNovalnetConfig');
        // checks to validate the Novalnet configuration before saving
        if ($this->_validateNovalnetConfig($aNovalnetConfig) === true) {
           $aNovalnetConfig = array_map('strip_tags', $aNovalnetConfig);
           $myConfig->saveShopConfVar('arr', 'aNovalnetConfig', $aNovalnetConfig, '', 'novalnet');
        }
    }

    /**
     * Gets server IP
     *
     * @param boolean $blServer
     *
     * @return string
     */
    public function getNovalnetIPAddress($blServer = false)
    {
        $oNovalnetUtil = oxNew(NovalnetUtil::class);
        return $oNovalnetUtil->getIpAddress($blServer);
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

        if (!function_exists('curl_init') || !function_exists('crc32') || !function_exists('bin2hex') || !function_exists('base64_encode') || !function_exists('base64_decode') || !function_exists('pack')) {
            $this->_aViewData['sNovalnetError'] = $oLang->translateString('NOVALNET_INVALID_PHP_PACKAGE');
            return false;
        }
        if (empty($aNovalnetConfig['iActivationKey']) || !is_numeric($aNovalnetConfig['iVendorId']) || !is_numeric($aNovalnetConfig['iProductId']) || empty($aNovalnetConfig['sTariffId']) || empty($aNovalnetConfig['sAuthCode']) || empty($aNovalnetConfig['sAccessKey'])) {
            $this->_aViewData['sNovalnetError'] = $oLang->translateString('NOVALNET_INVALID_CONFIG_ERROR');
            return false;
        } elseif ((!empty($aNovalnetConfig['dOnholdLimit'])
                    && !is_numeric($aNovalnetConfig['dOnholdLimit'])) || (!empty($aNovalnetConfig['sReferrerID'])
                    && !is_numeric($aNovalnetConfig['sReferrerID'])) || (!empty($aNovalnetConfig['iGatewayTimeOut'])
                    && !is_numeric($aNovalnetConfig['iGatewayTimeOut']))) {
            $this->_aViewData['sNovalnetError'] = $oLang->translateString('NOVALNET_INVALID_CONFIG_ERROR');
            return false;
        } elseif ((!empty($aNovalnetConfig['sTariffPeriod']) && !preg_match('/[1-9][0-9]*[dmy]{1}$/', $aNovalnetConfig['sTariffPeriod'])) || (!empty($aNovalnetConfig['sTariffPeriod2']) && !preg_match('/[1-9][0-9]*[dmy]{1}$/', $aNovalnetConfig['sTariffPeriod2']))) {
            $this->_aViewData['sNovalnetError'] = $oLang->translateString('NOVALNET_INVALID_TARIFF_PERIOD_ERROR');
            return false;
        } elseif (((!empty($aNovalnetConfig['dTariffPeriod2Amount']) || !empty($aNovalnetConfig['sTariffPeriod2']))
                    && (!is_numeric($aNovalnetConfig['dTariffPeriod2Amount']) || !preg_match('/[a-zA-Z0-9]+$/', $aNovalnetConfig['sTariffPeriod2'])))
                    || (!empty($aNovalnetConfig['sTariffPeriod']) && !preg_match('/[a-zA-Z0-9]+$/', $aNovalnetConfig['sTariffPeriod']))) {
            $this->_aViewData['sNovalnetError'] = $oLang->translateString('NOVALNET_INVALID_CONFIG_ERROR');
            return false;
        } elseif (!empty($aNovalnetConfig['sCallbackMailToAddr']) || !empty($aNovalnetConfig['sCallbackMailBccAddr'])) {
            $aToMailAddress  = explode(',', $aNovalnetConfig['sCallbackMailToAddr']);
            $aMailAddress = array_map('trim', $aToMailAddress);
            foreach ($aMailAddress as $sMailAddress) {
                if (!empty($sMailAddress) && !oxNew(\OxidEsales\Eshop\Core\MailValidator::class)->isValidEmail($sMailAddress)) {
                    $this->_aViewData['sNovalnetError'] = $oLang->translateString('NOVALNET_INVALID_CONFIG_ERROR');
                    return false;
                }
            }

            $aBccMailAddress = explode(',', $aNovalnetConfig['sCallbackMailBccAddr']);
            $aMailAddress = array_map('trim', $aBccMailAddress);
            foreach ($aMailAddress as $sMailAddress) {
                if (!empty($sMailAddress) && !oxNew(\OxidEsales\Eshop\Core\MailValidator::class)->isValidEmail($sMailAddress)) {
                    $this->_aViewData['sNovalnetError'] = $oLang->translateString('NOVALNET_INVALID_CONFIG_ERROR');
                    return false;
                }
            }
        }
        if (!empty($aNovalnetConfig['iDueDatenovalnetsepa']) && (!is_numeric($aNovalnetConfig['iDueDatenovalnetsepa']) || $aNovalnetConfig['iDueDatenovalnetsepa'] < 7)) {
            $this->_aViewData['sNovalnetError'] = $oLang->translateString('NOVALNET_INVALID_SEPA_CONFIG_ERROR');
            return false;
        }

        foreach (['novalnetinvoice', 'novalnetprepayment'] as $sPaymentName) {
            if (empty($aNovalnetConfig['blRefOne' . $sPaymentName]) && empty($aNovalnetConfig['blRefTwo' . $sPaymentName]) && empty($aNovalnetConfig['blRefThree' . $sPaymentName])) {
                $this->_aViewData['sNovalnetError'] = $oLang->translateString('NOVALNET_INVALID_INVOICE_REF_CONFIG_ERROR');
                return false;
            }
        }

        foreach (['novalnetsepa', 'novalnetinvoice'] as $sPaymentName) {
            if ($aNovalnetConfig['dGuaranteeMinAmount' . $sPaymentName] != '' && (!is_numeric($aNovalnetConfig['dGuaranteeMinAmount' . $sPaymentName]) || $aNovalnetConfig['dGuaranteeMinAmount' . $sPaymentName] < 2000 || $aNovalnetConfig['dGuaranteeMinAmount' . $sPaymentName] > 500000)) {
                $this->_aViewData['sNovalnetError'] = $oLang->translateString('NOVALNET_INVALID_GUARANTEE_MINIMUM_AMOUNT_ERROR');
                return false;
            } elseif ($aNovalnetConfig['dGuaranteeMaxAmount' . $sPaymentName] != '' &&  (!is_numeric($aNovalnetConfig['dGuaranteeMaxAmount' . $sPaymentName]) || $aNovalnetConfig['dGuaranteeMaxAmount' . $sPaymentName] > 500000 || $aNovalnetConfig['dGuaranteeMaxAmount' . $sPaymentName] < 2000)) {
                $this->_aViewData['sNovalnetError'] = $oLang->translateString('NOVALNET_INVALID_GUARANTEE_MAXIMUM_AMOUNT_ERROR');
                return false;
            } elseif ($aNovalnetConfig['dGuaranteeMinAmount' . $sPaymentName] != '' && $aNovalnetConfig['dGuaranteeMaxAmount' . $sPaymentName] != '' && $aNovalnetConfig['dGuaranteeMinAmount' . $sPaymentName] >= $aNovalnetConfig['dGuaranteeMaxAmount' . $sPaymentName]) {
                $this->_aViewData['sNovalnetError'] = $oLang->translateString('NOVALNET_INVALID_GUARANTEE_MAXIMUM_AMOUNT_ERROR');
                return false;
            }
        }
        return true;
    }

    /**
     * Gets current language
     *
     * @return string
     */
    public function getNovalnetLanguage()
    {
        $iLang = \OxidEsales\Eshop\Core\Registry::getLang()->getTplLanguage();
        return  \OxidEsales\Eshop\Core\Registry::getLang()->getLanguageAbbr($iLang);
    }
}
