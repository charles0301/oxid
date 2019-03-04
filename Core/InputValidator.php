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

namespace oe\novalnet\Core;

use oe\novalnet\Classes\NovalnetUtil;

/**
 * Class InputValidator.
 */
class InputValidator extends InputValidator_parent
{

    /**
     * Required fields for Novalnet sepa card payment
     *
     * @var array
     */
    protected $_aRequiredSepaFields = ['novalnet_sepa_holder', 'novalnet_sepa_iban' ];

    /**
     * Wrapper to get NovalnetUtil object
     *
     * @var object
     */
    protected $_oNovalnetUtil;

   /**
     * Wrapper to get utils object
     *
     * @var object
     */
    protected $_oNovalnetOxUtils;

    /**
     * Required fields for getting current payment name
     *
     * @var array
     */
    protected $_sCurrentPayment;

    /**
     * Required fields for Fraudmodule
     *
     * @var string
     */
    protected $_iCallbackType;

    /**
     * Validates payments input data from payment page
     *
     * @param string $sPaymentId
     * @param array  &$aDynValue
     *
     * @return boolean
     */
    public function validatePaymentInputData($sPaymentId, & $aDynValue)
    {
         if (preg_match("/novalnet/i", $sPaymentId)) {
            $this->_oNovalnetUtil = oxNew(NovalnetUtil::class);
            $this->_oNovalnetOxUtils = \OxidEsales\Eshop\Core\Registry::getUtils();

            $oUser  = $this->getUser();

            list($sFirstName, $sLastName) = $this->_oNovalnetUtil->retriveName($oUser);

            if (empty($sFirstName) || empty($sLastName) || !oxNew(\OxidEsales\Eshop\Core\MailValidator::class)->isValidEmail($oUser->oxuser__oxusername->value))
                $this->_oNovalnetOxUtils->redirect($this->_oNovalnetUtil->setRedirectURL($this->_oNovalnetUtil->oLang->translateString('NOVALNET_INVALID_NAME_EMAIL')));

            $this->_sCurrentPayment = $sPaymentId;

            $blOk                  = true;
            $sCallbackTid          = $this->_oNovalnetUtil->oSession->getVariable('sCallbackTid' . $sPaymentId);
            $this->_iCallbackType   = $this->_oNovalnetUtil->getNovalnetConfigValue('iCallback' . $sPaymentId);

            if (is_array($aDynValue))
                $aDynValue = array_map('trim', $aDynValue);

            // Checks the payment call is for processing the payment or fraud prevention second call
            if (empty($sCallbackTid)) {
                $this->_oNovalnetUtil->clearNovalnetFraudModulesSession();

                // validate age for guaranteed payments - invoice and direct debit sepa
                if ($this->_oNovalnetUtil->oSession->getVariable('blGuaranteeEnabled' . $sPaymentId) && $this->_oNovalnetUtil->oSession->getVariable('blGuaranteeForceDisabled' . $sPaymentId) != '1') {
					$oUser = $this->getUser();	
					$oAddress = $oUser->getSelectedAddress();
					$sCompany = (!empty($oUser->oxuser__oxcompany->value) ? $oUser->oxuser__oxcompany->value : (!empty($oAddress->oxaddress__oxcompany->value) ? $oAddress->oxaddress__oxcompany->value : ''));     				
                    $sNovalnetBirthDate = date('Y-m-d', strtotime($aDynValue['birthdate' . $sPaymentId]));
                    $sErrorMessage = '';
                    if ($sCompany == '') {
						if ($aDynValue['birthdate' . $sPaymentId] == '') {
							$sErrorMessage = $this->_oNovalnetUtil->oLang->translateString('NOVALNET_EMPTY_BIRTHDATE_ERROR');
						} elseif ($aDynValue['birthdate' . $sPaymentId] != $sNovalnetBirthDate) {
							$sErrorMessage = $this->_oNovalnetUtil->oLang->translateString('NOVALNET_INVALID_DATE_ERROR');
						} elseif (time() < strtotime('+18 years', strtotime($sNovalnetBirthDate))) {
							$sErrorMessage = $this->_oNovalnetUtil->oLang->translateString('NOVALNET_INVALID_BIRTHDATE_ERROR');
						} else {
							$aDynValue['birthdate' . $sPaymentId] = $sNovalnetBirthDate;
						}
					}
                    if ($sErrorMessage != '') {
                        if ($this->_oNovalnetUtil->getNovalnetConfigValue('blGuaranteeForce' . $this->_sCurrentPayment) != '1') {
                            $this->_oNovalnetOxUtils->redirect($this->_oNovalnetUtil->setRedirectURL($sErrorMessage));
                        } else {
                            $this->_oNovalnetUtil->oSession->deleteVariable('blGuaranteeEnabled' . $sPaymentId);
                        }
                    }
                } elseif ($this->_oNovalnetUtil->oSession->getVariable('blGuaranteeForceDisabled' . $sPaymentId)) {
                    $sErrorMessage = $this->_oNovalnetUtil->oLang->translateString('NOVALNET_GUARANTEE_FORCE_DISABLED_MESSAGE');
                    $this->_oNovalnetOxUtils->redirect($this->_oNovalnetUtil->setRedirectURL($sErrorMessage));
                }
                $this->_oNovalnetUtil->oSession->setVariable('anovalnetdynvalue', $aDynValue);

                // checks to validate credit card or sepa account details
                if ($sPaymentId == 'novalnetcreditcard') {
                    $blOk = $this->_validateCreditCardInputData($aDynValue);
                } elseif ($sPaymentId == 'novalnetsepa') {
                    $blOk = $this->_validateSepaInputData($aDynValue);
                }

                $blCallbackEnabled = $this->_oNovalnetUtil->oSession->getVariable('blCallbackEnabled' . $sPaymentId);
                // checks to validates the pin number
                if ($blOk && $blCallbackEnabled)
                    $blOk = $this->_validateFraudModuleCallData($aDynValue);
            }
            else {
                $blOk = $this->_validateFraudModulePinData($aDynValue);
            }

            return $blOk;
        } else {
            parent::validatePaymentInputData($sPaymentId, $aDynValue);
        }
    }

    /**
     * Validates pin number
     *
     * @param array $aDynValue
     *
     * @return boolean
     */
    private function _validateFraudModulePinData($aDynValue)
    {
        // checks to validate the pin number for pin by callback and pin by sms
        if (in_array($this->_iCallbackType, [1, 2]) && empty($aDynValue['newpin_' . $this->_sCurrentPayment])) {
            $sErrorMessage = '';
            if (empty($aDynValue['pinno_' . $this->_sCurrentPayment]))
                $sErrorMessage = $this->_oNovalnetUtil->oLang->translateString('NOVALNET_FRAUD_MODULE_PIN_EMPTY');
            elseif (!preg_match('/^[a-zA-Z0-9]+$/',$aDynValue['pinno_' . $this->_sCurrentPayment]))
                $sErrorMessage = $this->_oNovalnetUtil->oLang->translateString('NOVALNET_FRAUD_MODULE_PIN_INVALID');

            if ($sErrorMessage)
                $this->_oNovalnetOxUtils->redirect($this->_oNovalnetUtil->setRedirectURL($sErrorMessage));
        }
        return true;
    }

    /**
     * Validates credit card details
     *
     * @param array $aDynValue
     *
     * @return boolean
     */
    private function _validateCreditCardInputData($aDynValue)
    {
        $blNoScriptCreditcard = $this->getConfig()->getRequestParameter('novalnet_cc_noscript');

        // checks to validate the java script presence
        if ($blNoScriptCreditcard)
            $this->_oNovalnetOxUtils->redirect($this->_oNovalnetUtil->setRedirectURL($this->_oNovalnetUtil->oLang->translateString('NOVALNET_NOSCRIPT_MESSAGE')));
         if (isset($aDynValue['novalnet_cc_new_details'])) {
            $this->_oNovalnetUtil->oSession->setVariable('blOneClicknovalnetcreditcard', $aDynValue['novalnet_cc_new_details']);
            if ($aDynValue['novalnet_cc_new_details'] == 0)
                $this->_oNovalnetUtil->oSession->deleteVariable('blCallbackEnablednovalnetcreditcard');
        }
        return true;
    }

    /**
     * Validates direct debit sepa details
     *
     * @param array $aDynValue
     *
     * @return boolean
     */
    private function _validateSepaInputData($aDynValue)
    {
        $blNoScriptSepa = $this->getConfig()->getRequestParameter('novalnet_sepa_noscript');

        // checks to validate the Java script presence
        if ($blNoScriptSepa)
            $this->_oNovalnetOxUtils->redirect($this->_oNovalnetUtil->setRedirectURL($this->_oNovalnetUtil->oLang->translateString('NOVALNET_NOSCRIPT_MESSAGE')));

        if (!isset($aDynValue['novalnet_sepa_new_details']) || $aDynValue['novalnet_sepa_new_details'] == 1) {
            foreach ($this->_aRequiredSepaFields as $sRequiredFields) {
                if (empty($aDynValue[$sRequiredFields])) {
                    $sErrorMessage = $this->_oNovalnetUtil->oLang->translateString('NOVALNET_SEPA_INVALID_DETAILS');
                    $this->_oNovalnetOxUtils->redirect($this->_oNovalnetUtil->setRedirectURL($sErrorMessage));
                }
            }
        }
        if (isset($aDynValue['novalnet_sepa_new_details'])) {
            $this->_oNovalnetUtil->oSession->setVariable('blOneClicknovalnetsepa', $aDynValue['novalnet_sepa_new_details']);
            if ($aDynValue['novalnet_sepa_new_details'] == 0)
                $this->_oNovalnetUtil->oSession->deleteVariable('blCallbackEnablednovalnetsepa');
        }
        return true;
    }

    /**
     * Validates the contact details for fraud modules
     *
     * @param array $aDynValue
     *
     * @return boolean
     */
    private function _validateFraudModuleCallData($aDynValue)
    {
        $sErrorMessage = '';

        // checks to validate the form fields used in fraud prevention
        if ($this->_iCallbackType == '1' && !is_numeric($aDynValue['pinbycall_' . $this->_sCurrentPayment]))
            $sErrorMessage = $this->_oNovalnetUtil->oLang->translateString('NOVALNET_FRAUD_MODULE_PHONE_INVALID');

        elseif ($this->_iCallbackType == '2' && !is_numeric($aDynValue['pinbysms_' . $this->_sCurrentPayment]))
            $sErrorMessage = $this->_oNovalnetUtil->oLang->translateString('NOVALNET_FRAUD_MODULE_MOBILE_INVALID');

        if ($sErrorMessage)
            $this->_oNovalnetOxUtils->redirect($this->_oNovalnetUtil->setRedirectURL($sErrorMessage));

        return true;
    }
}
?>
