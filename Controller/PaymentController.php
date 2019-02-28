<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU General Public License
 * that is bundled with this package in the file freeware_license_agreement.txt
 *
 * @author Novalnet <technic@novalnet.de>
 * @copyright Novalnet
 * @license GNU General Public License
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
     * Returns payment name
     *
     * @var array
     */
    protected $_aPaymentType = ['novalnetcreditcard' => '"CREDITCARD"', 'novalnetsepa' => '"DIRECT_DEBIT_SEPA", "GUARANTEED_DIRECT_DEBIT_SEPA"', 'novalnetpaypal' => '"PAYPAL"'];

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
     * Gets Novalnet test mode status for the Novalnet payments
     *
     * @param string $sPaymentId
     *
     * @return boolean
     */
    public function getNovalnetZeroAmountStatus($sPaymentId)
    {
        return $this->getNovalnetConfig('iShopType' . $sPaymentId);
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
        $iShopType    = (in_array($sPaymentId, ['novalnetsepa', 'novalnetpaypal']) || ($sPaymentId == 'novalnetcreditcard' && $this->getNovalnetConfig('blCC3DActive') != '1' && $this->getNovalnetConfig('blCC3DFraudActive') != '1')) ? $this->getNovalnetConfig('iShopType' . $sPaymentId) : '0';

        // checks the shopping type is one click
        if ($iShopType == '1') {
            $aResult = $oDb->getRow('SELECT TID, MASKED_DETAILS FROM novalnet_transaction_detail WHERE CUSTOMER_ID = "' . $this->oUser->oxuser__oxcustnr->value . '" AND PAYMENT_TYPE IN (' . $this->_aPaymentType[$sPaymentId] . ') AND REFERENCE_TRANSACTION = "0" AND ZERO_TRANSACTION = "0" AND MASKED_DETAILS <> "" ORDER BY ORDER_NO DESC');
            if (!empty($aResult['MASKED_DETAILS']) && !empty($aResult['TID'])) {
                $aPaymentDetails               = unserialize($aResult['MASKED_DETAILS']);
                $this->_oNovalnetSession->setVariable('sPaymentRef' . $sPaymentId, $aResult['TID']);
            }
        }
        return $aPaymentDetails;
    }

     /**
     * Get Shopping type details
     *
     * @param string $sPaymentId
     *
     * @return array
     */
    public function getShoppingTypeDetails($sPaymentId)
    {
        $iShopType    = (in_array($sPaymentId, ['novalnetsepa', 'novalnetpaypal']) || ($sPaymentId == 'novalnetcreditcard' && $this->getNovalnetConfig('blCC3DActive') != '1')) ? $this->getNovalnetConfig('iShopType' . $sPaymentId) : '0';
        $aShoppingTypeDetails = [];
        $blOneClick   = $this->_oNovalnetSession->getVariable('blOneClick' . $sPaymentId);

        $aShoppingTypeDetails['iShopType']  = $iShopType;
        $aShoppingTypeDetails['blOneClick'] = !empty($blOneClick) ? $blOneClick : '0';
        return $aShoppingTypeDetails;
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
        $this->clearNovalnetGuaranteeSession($sPaymentId);

        // checks to enable the guarantee payment
        if (!empty($blGuaranteeActive)) {
            $sOxAddressId = \OxidEsales\Eshop\Core\Registry::getSession()->getVariable('deladrid');
            $blGuaranteeAddressCheck = true;
            if ($sOxAddressId) {
                $oDelAddress  = oxNew(\OxidEsales\Eshop\Application\Model\Address::class);
                $oDelAddress->load($sOxAddressId);
                $oUser        = $this->getUser();
                $aShippingAddress = [$oDelAddress->oxaddress__oxcountryid->value, $oDelAddress->oxaddress__oxzip->value,
                    $oDelAddress->oxaddress__oxcity->value, $oDelAddress->oxaddress__oxstreet->value,
                    $oDelAddress->oxaddress__oxstreetnr->value];

                $aUserAddress = [$oUser->oxuser__oxcountryid->value, $oUser->oxuser__oxzip->value,
                    $oUser->oxuser__oxcity->value, $oUser->oxuser__oxstreet->value, $oUser->oxuser__oxstreetnr->value];

                $blGuaranteeAddressCheck = ($aShippingAddress == $aUserAddress);
            }
            $dGuaranteeMinAmount = trim($this->getNovalnetConfig('dGuaranteeMinAmount' . $sPaymentId)) ? trim($this->getNovalnetConfig('dGuaranteeMinAmount' . $sPaymentId)) : 999;

            $blGuaranteeMinAmtCheck          = ($dAmount >= $dGuaranteeMinAmount);
            $blGuaranteeCurrencyCheck        = $oBasket->getBasketCurrency()->name == 'EUR';
            $blGuaranteeCountryCheck         = in_array($this->_oNovalnetUtil->getCountryISO($this->getUser()->oxuser__oxcountryid->value), ['DE', 'AT', 'CH']);

            if($blGuaranteeMinAmtCheck  && $blGuaranteeCurrencyCheck && $blGuaranteeAddressCheck && $blGuaranteeCountryCheck)
            {
               $this->_oNovalnetSession->setVariable('blGuaranteeEnabled' . $sPaymentId, 1);
            } elseif ($this->getNovalnetConfig('blGuaranteeForce' . $sPaymentId) != '1') {
                $this->_oNovalnetSession->setVariable('blGuaranteeForceDisabled' . $sPaymentId, 1);

                if(empty($blGuaranteeMinAmtCheck))
                {
                    $this->_oNovalnetSession->setVariable('blGuaranteeAmt' . $sPaymentId, 1);
                    if ($dGuaranteeMinAmount >= 999) {
						$sCurrecny = $oBasket->getBasketCurrency()->name;
						$sMinAmount = $this->_oNovalnetUtil->oLang->formatCurrency($dGuaranteeMinAmount/100, $this->_oNovalnetUtil->oConfig->getCurrencyObject($sCurrecny));
						$this->_oNovalnetSession->setVariable('dGetGuaranteeAmount' . $sPaymentId, $sMinAmount);
						$this->_oNovalnetSession->setVariable('dGetGuaranteeAmt' . $sPaymentId, $dGuaranteeMinAmount);
					}
                }
                if(empty($blGuaranteeCurrencyCheck))
                    $this->_oNovalnetSession->setVariable('blGuaranteeCurrency' . $sPaymentId, 1);
                if(empty($blGuaranteeAddressCheck))
                    $this->_oNovalnetSession->setVariable('blGuaranteeAddress' . $sPaymentId, 1);
                if(empty($blGuaranteeCountryCheck))
                    $this->_oNovalnetSession->setVariable('blGuaranteeCountry' . $sPaymentId, 1);
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
        $oSession                  = $this->_oNovalnetUtil->oSession;
        $dAmount                   = str_replace(',', '', number_format($oSession->getBasket()->getPriceForPayment(), 2)) * 100;
        $dNovalnetFraudModuleLimit = $this->getNovalnetConfig('dCallbackAmount' . $sPaymentId);

        // checks to enable the fraud module status
        if (!$oSession->getVariable('blGuaranteeEnabled' . $sPaymentId) && !$oSession->getVariable('blGuaranteeForceDisabled' . $sPaymentId) && $this->getNovalnetConfig('iCallback' . $sPaymentId) != '' && (!is_numeric($dNovalnetFraudModuleLimit) || $dAmount >= $dNovalnetFraudModuleLimit) && in_array($this->_oNovalnetUtil->getCountryISO($this->getUser()->oxuser__oxcountryid->value), ['DE', 'AT', 'CH'])) {
            $oSession->setVariable('blCallbackEnabled' . $sPaymentId, 1);
            return true;
        }

        $oSession->deleteVariable('blCallbackEnabled' . $sPaymentId);
        return false;
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
        return base64_encode("vendor=".$this->getNovalnetConfig('iVendorId'). '&' . "product=" .$this->getNovalnetConfig('iProductId') .'&'. "server_ip=". $this->_oNovalnetUtil->getIpAddress(true). '&'. $this->_oNovalnetUtil->oLang->getLanguageAbbr());
    }
	
	/**
     * Get the Company field value
     *
     * @return string
     */
	public function getCompanyFieldValue()
    {
        $oUser = $this->getUser();
        $oAddress = $oUser->getSelectedAddress();
        return (!empty($oUser->oxuser__oxcompany->value) ? $oUser->oxuser__oxcompany->value : (!empty($oAddress->oxaddress__oxcompany->value) ? $oAddress->oxaddress__oxcompany->value : ''));        
    }
    
    /**
     * Get the Currency value
     *
     * @return string
     */
    public function getCurrencyalue() {
		$oBasket           = $this->_oNovalnetSession->getBasket();
		return $oBasket->getBasketCurrency()->name;
	}
    
    /**
     * Validates Novalnet credentials
     *
     * @return boolean
     */
    private function _validateNovalnetConfig()
    {
        $sProcessKey = $this->getNovalnetConfig('iActivationKey');
        $sAuthCode = $this->getNovalnetConfig('sAuthCode');
        return !empty($sProcessKey) && !empty($sAuthCode);
    }


    /**
     * Clear Novalnet Guarantee Session
     *
     * @param string $sPaymentId
     */
     public function clearNovalnetGuaranteeSession($sPaymentId)
    {
        $this->_oNovalnetSession->deleteVariable('blGuaranteeEnabled' . $sPaymentId);
        $this->_oNovalnetSession->deleteVariable('blGuaranteeForceDisabled' . $sPaymentId);
        $this->_oNovalnetSession->deleteVariable('blGuaranteeAmt' . $sPaymentId);
        $this->_oNovalnetSession->deleteVariable('blGuaranteeCurrency' . $sPaymentId);
        $this->_oNovalnetSession->deleteVariable('blGuaranteeAddress' . $sPaymentId);
        $this->_oNovalnetSession->deleteVariable('blGuaranteeCountry' . $sPaymentId);
        $this->_oNovalnetSession->deleteVariable('dGetGuaranteeAmt' . $sPaymentId);
        $this->_oNovalnetSession->deleteVariable('dGetGuaranteeAmount' . $sPaymentId);
    }
}
