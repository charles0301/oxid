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
 * Script : PaymentController.php
 *
 */

namespace oe\novalnet\Controller;

use oe\novalnet\Classes\NovalnetUtil;

/**
 * Class PaymentController.
 */
class PaymentController extends PaymentController_parent {
    /**
     * Session object
     *
     * @var array
     */
    protected $_oNovalnetSession;

    /**
     * Wrapper to get NovalnetUtil object
     *
     * @var object
     */
    protected $_oNovalnetUtil;
    /**
     * Returns name of template to render
     *
     * @return string
     */
    public function render()
    {
        $this->_oNovalnetSession = $this->getSession();
        $this->_oNovalnetUtil = oxNew(NovalnetUtil::class);
        if ($this->_oNovalnetSession->hasVariable('sNovalnetSession') && $this->_oNovalnetSession->getVariable('sNovalnetSession') != $this->_oNovalnetSession->getId()) {
            $this->_oNovalnetSession->deleteVariable('sNovalnetSession');
            $this->_oNovalnetUtil->clearNovalnetSession();
            $this->_oNovalnetUtil->clearNovalnetPaymentLock();
            $this->_oNovalnetSession->setVariable('sNovalnetSession', $this->_oNovalnetSession->getId());
        } elseif (!$this->_oNovalnetSession->hasVariable('sNovalnetSession')) {
            $this->_oNovalnetSession->setVariable('sNovalnetSession', $this->_oNovalnetSession->getId());
        }
        return parent::render();
    }

    /**
     * Gets payments to show on the payment page
     *
     * @return array
     */
    public function getPaymentList()
    {

        parent::getPaymentList();

        foreach ($this->_oPaymentList as $oPayment) {
            $sPaymentName = $oPayment->oxpayments__oxid->value;
            // checks the payments are Novalnet payments
            if (preg_match("/novalnet/i", $sPaymentName)) {
                $blPaymentLock   = $this->_oNovalnetSession->getVariable('blNovalnetPaymentLock' . $sPaymentName);
                // validates the time to lock the payment
                if ($this->_validateNovalnetConfig() === false || (in_array($sPaymentName, ['novalnetsepa', 'novalnetinvoice']) && ((!empty($blPaymentLock) && $this->_oNovalnetSession->getVariable('sNovalnetPaymentLockTime' . $sPaymentName) > time()) || !$this->getGuaranteePaymentStatus($sPaymentName)))) {
                    // hides the payment on checkout page if the payment lock time dosen't exceed current time
                    unset($this->_oPaymentList[$sPaymentName]);
                } elseif (in_array($sPaymentName, ['novalnetsepa', 'novalnetinvoice']) && (!empty($blPaymentLock) && $this->_oNovalnetSession->getVariable('sNovalnetPaymentLockTime' . $sPaymentName) <= time())) {
                    // shows the payment on checkout page the payment lock time exceeds current time
                    $this->_oNovalnetSession->deleteVariable('blNovalnetPaymentLock' . $sPaymentName);
                    $this->_oNovalnetSession->deleteVariable('sNovalnetPaymentLockTime' . $sPaymentName);
                }
            }
        }

        return $this->_oPaymentList;
    }

    /**
     * Gets Novalnet credential value
     *
     * @param string $sConfig
     *
     * @return string
     */
    public function getNovalnetConfig($sConfig)
    {
        if (empty($aNovalnetConfig = $this->getConfig()->getShopConfVar('aNovalnetConfig', '', 'novalnet')))
            return false;

        $aNovalnetConfig = array_map('trim', $aNovalnetConfig);

        return $aNovalnetConfig[$sConfig];
    }

    /**
     * Gets remote ip address
     *
     * @return string
     */
    public function getNovalnetRemoteIp()
    {
        return $this->_oNovalnetUtil->getIpAddress();
    }

    /**
     * Gets Novalnet notification message
     *
     * @param string $sPaymentId
     *
     * @return string
     */
    public function getNovalnetNotification($sPaymentId)
    {
        return $this->getNovalnetConfig('sBuyerNotify' . $sPaymentId);
    }

    /**
     * Gets Novalnet test mode status for the Novalnet payments
     *
     * @param string $sPaymentId
     *
     * @return boolean
     */
    public function getNovalnetTestmode($sPaymentId)
    {
        return $this->getNovalnetConfig('blTestmode' . $sPaymentId);
    }

    /**
     * Gets the Unique Id
     *
     * @return string
     */
    public function getUniqueid()
    {
        $aKeys = ['a','b','c','d','e','f','g','h','i','j','k','l','m','1','2','3','4','5','6','7','8','9','0'];
        shuffle($aKeys);
        return substr(implode($aKeys, ''), 0, 30);
    }

    /**
     * Get the payment form credentials
     *
     * @param string $sPaymentId
     *
     * @return array
     */
    public function getNovalnetPaymentDetails($sPaymentId)
    {
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(\OxidEsales\Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC);
        $this->oUser  = $this->getUser();
        $aPaymentType = ['novalnetcreditcard' => '"CREDITCARD"', 'novalnetsepa' => '"DIRECT_DEBIT_SEPA", "GUARANTEED_DIRECT_DEBIT_SEPA"', 'novalnetpaypal' => '"PAYPAL"'];
        $iShopType    = (in_array($sPaymentId, ['novalnetsepa', 'novalnetpaypal']) || ($sPaymentId == 'novalnetcreditcard' && $this->getNovalnetConfig('blCC3DActive') != '1')) ? $this->getNovalnetConfig('iShopType' . $sPaymentId) : '0';
        $blOneClick   = $this->_oNovalnetSession->getVariable('blOneClick' . $sPaymentId);
        $aPaymentDetails['iShopType'] = '';

        // checks the shopping type is one click
        if ($iShopType == '1') {
            $aResult = $oDb->getRow('SELECT TID, PROCESS_KEY, MASKED_DETAILS FROM novalnet_transaction_detail WHERE CUSTOMER_ID = "' . $this->oUser->oxuser__oxcustnr->value . '" AND PAYMENT_TYPE IN (' . $aPaymentType[$sPaymentId] . ') AND REFERENCE_TRANSACTION = "0" AND ZERO_TRANSACTION = "0" AND MASKED_DETAILS <> "" ORDER BY ORDER_NO DESC');
            if (!empty($aResult['MASKED_DETAILS']) && !empty($aResult['TID'])) {
                $aPaymentDetails               = unserialize($aResult['MASKED_DETAILS']);
                $aPaymentDetails['iShopType']  = $iShopType;
                $aPaymentDetails['blOneClick'] = !empty($blOneClick) ? $blOneClick : 0;
                $this->_oNovalnetSession->setVariable('sPaymentRef' . $sPaymentId, $aResult['TID']);
                if ($sPaymentId == 'novalnetsepa')
                    $this->_oNovalnetSession->setVariable('sHashRefnovalnetsepa', $aResult['PROCESS_KEY']);
            }
        }
        if ($sPaymentId == 'novalnetsepa') {
            $aNovalnetConfig['vendor']    = $this->getNovalnetConfig('iVendorId');
            $aNovalnetConfig['auth_code'] = $this->getNovalnetConfig('sAuthCode');
            $this->_oNovalnetUtil->setAffiliateCredentials($aNovalnetConfig, $this->oUser->oxuser__oxcustnr->value);
            $aPaymentDetails['iVendorId'] = $aNovalnetConfig['vendor'];
            $aPaymentDetails['sAuthCode'] = $aNovalnetConfig['auth_code'];
        }
        return $aPaymentDetails;
    }

    /**
     * Gets the guarantee payment activation status for direct debit sepa and invoice
     *
     * @param string $sPaymentId
     *
     * @return boolean
     */
    public function getGuaranteePaymentStatus($sPaymentId)
    {
        $oBasket           = $this->_oNovalnetSession->getBasket();
        $dAmount           = str_replace(',', '', number_format($oBasket->getPriceForPayment(), 2)) * 100;
        $blGuaranteeActive = $this->getNovalnetConfig('blGuarantee' . $sPaymentId);
        $this->_oNovalnetSession->deleteVariable('blGuaranteeEnabled' . $sPaymentId);
        $this->_oNovalnetSession->deleteVariable('blGuaranteeForceDisabled' . $sPaymentId);

        // checks to enable the guarantee payment
        if (!empty($blGuaranteeActive)) {
            $sOxAddressId = \OxidEsales\Eshop\Core\Registry::getSession()->getVariable('deladrid');
            $blValidShippingAddress = true;
            if ($sOxAddressId) {
                $oDelAddress  = oxNew(\OxidEsales\Eshop\Application\Model\Address::class);
                $oDelAddress->load($sOxAddressId);
                $oUser        = $this->getUser();
                $aShippingAddress = [$oDelAddress->oxaddress__oxcountryid->value, $oDelAddress->oxaddress__oxzip->value,
                    $oDelAddress->oxaddress__oxcity->value, $oDelAddress->oxaddress__oxstreet->value,
                    $oDelAddress->oxaddress__oxstreetnr->value];

                $aUserAddress = [$oUser->oxuser__oxcountryid->value, $oUser->oxuser__oxzip->value,
                    $oUser->oxuser__oxcity->value, $oUser->oxuser__oxstreet->value, $oUser->oxuser__oxstreetnr->value];

                $blValidShippingAddress = ($aShippingAddress == $aUserAddress);
            }
            $dGuaranteeMinAmount = trim($this->getNovalnetConfig('dGuaranteeMinAmount' . $sPaymentId)) ? trim($this->getNovalnetConfig('dGuaranteeMinAmount' . $sPaymentId)) : 2000;
            $dGuaranteeMaxAmount = trim($this->getNovalnetConfig('dGuaranteeMaxAmount' . $sPaymentId)) ? trim($this->getNovalnetConfig('dGuaranteeMaxAmount' . $sPaymentId)) : 500000;

            if ($blValidShippingAddress && in_array($this->_oNovalnetUtil->getCountryISO($this->getUser()->oxuser__oxcountryid->value), ['DE', 'AT', 'CH']) && $oBasket->getBasketCurrency()->name == 'EUR' && ($dAmount >= $dGuaranteeMinAmount && $dAmount <= $dGuaranteeMaxAmount)) {
                $this->_oNovalnetSession->setVariable('blGuaranteeEnabled' . $sPaymentId, 1);
            } elseif ($this->getNovalnetConfig('blGuaranteeForce' . $sPaymentId) != '1') {
                $this->_oNovalnetSession->setVariable('blGuaranteeForceDisabled' . $sPaymentId, 1);
            }
        }
        return true;
    }

    /**
     * Gets the fraud module activation status for credit card, direct debit sepa and invoice
     *
     * @param string $sPaymentId
     *
     * @return boolean
     */
    public function getFraudModuleStatus($sPaymentId)
    {
        $oSess                     = $this->_oNovalnetUtil->oSession;
        $dAmount                   = str_replace(',', '', number_format($oSess->getBasket()->getPriceForPayment(), 2)) * 100;
        $dNovalnetFraudModuleLimit = $this->getNovalnetConfig('dCallbackAmount' . $sPaymentId);

        // checks to enable the fraud module status
        if (!$oSess->getVariable('blGuaranteeEnabled' . $sPaymentId) && !$oSess->getVariable('blGuaranteeForceDisabled' . $sPaymentId) && $this->getNovalnetConfig('iCallback' . $sPaymentId) != '' && (!is_numeric($dNovalnetFraudModuleLimit) || $dAmount >= $dNovalnetFraudModuleLimit) && in_array($this->_oNovalnetUtil->getCountryISO($this->getUser()->oxuser__oxcountryid->value), ['DE', 'AT', 'CH'])) {
            $oSess->setVariable('blCallbackEnabled' . $sPaymentId, 1);
            return true;
        }

        $oSess->deleteVariable('blCallbackEnabled' . $sPaymentId);
        return false;
    }

    /**
     * Get the Sepa hash for payment refill of sepa
     *
     * @return string
     */
    public function getLastSepaHash()
    {
        if ($this->getNovalnetConfig('blAutoFillSepa') == 1 ) {
            $sPaymentId = $this->_oNovalnetSession->getVariable('blGuaranteeEnablednovalnetsepa') ? '40' : '37';
            $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(\OxidEsales\Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC);
            $aResult    = $oDb->getRow('SELECT PROCESS_KEY, PAYMENT_ID FROM novalnet_transaction_detail WHERE CUSTOMER_ID = "' . $this->oUser->oxuser__oxcustnr->value . '" ORDER BY ORDER_NO DESC');
            return (!empty($aResult['PROCESS_KEY']) && $aResult['PAYMENT_ID'] == $sPaymentId) ? $aResult['PROCESS_KEY'] : '';
        }
        return '';
    }

    /**
     * Get the birth date for guarantee payments
     *
     * @return string
     */
    public function getNovalnetBirthDate()
    {
        $oUser = $this->getUser();
        return date('Y-m-d', strtotime(isset($oUser->oxuser__oxbirthdate->rawValue) && $oUser->oxuser__oxbirthdate->rawValue != '0000-00-00' ? $oUser->oxuser__oxbirthdate->rawValue : date('Y-m-d')));
    }

    /**
     * Get the Novalnet signature for the Creditcard form
     *
     * @return string
     */
    public function getNovalnetSignature()
    {
        $sLanguageParam = '&ln=' .  $this->_oNovalnetUtil->oLang->getLanguageAbbr(); // getting language and set the language parameter
        return base64_encode(trim($this->getNovalnetConfig('iActivationKey')) . '&' . $this->_oNovalnetUtil->getIpAddress() . '&' . $this->_oNovalnetUtil->getIpAddress(true)) . $sLanguageParam;
    }

    /**
     * Validates Novalnet credentials
     *
     * @return boolean
     */
    private function _validateNovalnetConfig()
    {
        $sProcessKey = $this->getNovalnetConfig('iActivationKey');
        return !empty($sProcessKey);
    }
}
