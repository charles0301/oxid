<?php
/**
 * Novalnet payment method module
 * This module is used for real time processing of
 * Novalnet transaction of customers.
 *
 * Copyright (c) Novalnet
 *
 * Released under the GNU General Public License
 * This free contribution made by request.
 * If you have found this script useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * Script : InputValidator.php
 *
 */

namespace oe\novalnet\Core;

use oe\novalnet\Classes\NovalnetUtil;

class InputValidator extends InputValidator_parent
{

    /**
     * Required fields for Novalnet sepa card payment
     *
     * @var array
     */
    protected $_aRequiredSepaFields = array( 'novalnet_sepa_holder', 'novalnet_sepa_hash', 'novalnet_sepa_uniqueid' );

    /**
     * Novalnet payments
     *
     * @var array
     */
    protected $_aNovalnetPayments = array( 'novalnetcreditcard', 'novalnetsepa', 'novalnetinvoice', 'novalnetprepayment', 'novalnetonlinetransfer', 'novalnetideal', 'novalnetpaypal', 'novalneteps', 'novalnetgiropay', 'novalnetprzelewy24','novalnetbarzahlen' );

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

        if (in_array($sPaymentId, $this->_aNovalnetPayments)) {
            $this->oNovalnetUtil = oxNew(NovalnetUtil::class);
            $this->oNovalnetOxUtils = \OxidEsales\Eshop\Core\Registry::getUtils();

            if (!function_exists('curl_init') || !function_exists('crc32') || !function_exists('bin2hex') || !function_exists('base64_encode') || !function_exists('base64_decode') || !function_exists('pack')) {
                $this->oNovalnetOxUtils->redirect($this->oNovalnetUtil->setRedirectURL($this->oNovalnetUtil->oLang->translateString('NOVALNET_INVALID_PHP_PACKAGE')));
            }

            $oUser  = $this->getUser();
            $sFirstName = $oUser->oxuser__oxfname->value;
            $sLastName  = $oUser->oxuser__oxlname->value;

            if(empty($sFirstName) || empty($sLastName)) {
                $sName = $sFirstName . $sLastName;
                list($sFirstName, $sLastName) = preg_match('/\s/',$sName) ? explode(' ', $sName, 2) : array($sName, $sName);
            }

            if (empty($sFirstName) || empty($sLastName) || !oxNew(\OxidEsales\Eshop\Core\MailValidator::class)->isValidEmail($oUser->oxuser__oxusername->value)) {
                $this->oNovalnetOxUtils->redirect($this->oNovalnetUtil->setRedirectURL($this->oNovalnetUtil->oLang->translateString('NOVALNET_INVALID_NAME_EMAIL')));
            }

            $this->sCurrentPayment = $sPaymentId;
            $this->dAmount         = str_replace(',', '', number_format(\OxidEsales\Eshop\Core\Registry::getSession()->getBasket()->getPriceForPayment(), 2)) * 100;

            $blOk                  = true;
            $sCallbackTid          = $this->oNovalnetUtil->oSession->getVariable('sCallbackTid' . $sPaymentId);
            $this->iCallbackType   = $this->oNovalnetUtil->getNovalnetConfigValue('iCallback' . $sPaymentId);

            if (is_array($aDynValue))
                $aDynValue = array_map('trim', $aDynValue);

            // Checks the payment call is for processing the payment or fraud prevention second call
            if (empty($sCallbackTid)) {
                $this->oNovalnetUtil->clearNovalnetFraudModulesSession();

                // validate age for guaranteed payments - invoice and direct debit sepa
                if ($this->oNovalnetUtil->oSession->getVariable('blGuaranteeEnabled' . $sPaymentId) && $this->oNovalnetUtil->oSession->getVariable('blGuaranteeForceDisabled' . $sPaymentId) != '1') {

                    $sNovalnetBirthDate = date('Y-m-d', strtotime($aDynValue['birthdate' . $sPaymentId]));
                    $sErrorMessage = '';
                    if ($aDynValue['birthdate' . $sPaymentId] == '') {
                        $sErrorMessage = $this->oNovalnetUtil->oLang->translateString('NOVALNET_EMPTY_BIRTHDATE_ERROR');
                    } elseif ($aDynValue['birthdate' . $sPaymentId] != $sNovalnetBirthDate) {
                        $sErrorMessage = $this->oNovalnetUtil->oLang->translateString('NOVALNET_INVALID_DATE_ERROR');
                    } elseif (time() < strtotime('+18 years', strtotime($sNovalnetBirthDate))) {
                        $sErrorMessage = $this->oNovalnetUtil->oLang->translateString('NOVALNET_INVALID_BIRTHDATE_ERROR');
                    } else {
                        $aDynValue['birthdate' . $sPaymentId] = $sNovalnetBirthDate;
                    }

                    if ($sErrorMessage != '') {
                        if ($this->oNovalnetUtil->getNovalnetConfigValue('blGuaranteeForce' . $this->sCurrentPayment) != '1') {
                            $this->oNovalnetOxUtils->redirect($this->oNovalnetUtil->setRedirectURL($sErrorMessage));
                        } else {
                            $this->oNovalnetUtil->oSession->deleteVariable('blGuaranteeEnabled' . $sPaymentId);
                        }
                    }
                } elseif ($this->oNovalnetUtil->oSession->getVariable('blGuaranteeForceDisabled' . $sPaymentId)) {
                    $sErrorMessage = $this->oNovalnetUtil->oLang->translateString('NOVALNET_GUARANTEE_FORCE_DISABLED_MESSAGE');
                    $this->oNovalnetOxUtils->redirect($this->oNovalnetUtil->setRedirectURL($sErrorMessage));
                }
                $this->oNovalnetUtil->oSession->setVariable('anovalnetdynvalue', $aDynValue);

                // checks to validate credit card or sepa account details
                if ($sPaymentId == 'novalnetcreditcard') {
                    $blOk = $this->_validateCreditCardInputData($aDynValue);
                } elseif ($sPaymentId == 'novalnetsepa') {
                    $blOk = $this->_validateSepaInputData($aDynValue);
                }

                $blCallbackEnabled = $this->oNovalnetUtil->oSession->getVariable('blCallbackEnabled' . $sPaymentId);
                // checks to validates the pin number
                if (!empty($blOk) && !empty($blCallbackEnabled)) {
                    $blOk = $this->_validateFraudModuleCallData($aDynValue);
                }
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
        if (in_array($this->iCallbackType, array(1, 2)) && empty($aDynValue['newpin_' . $this->sCurrentPayment])) {
            $sErrorMessage = '';
            if (empty($aDynValue['pinno_' . $this->sCurrentPayment]))
                $sErrorMessage = $this->oNovalnetUtil->oLang->translateString('NOVALNET_FRAUD_MODULE_PIN_EMPTY');
            elseif (!preg_match('/^[a-zA-Z0-9]+$/',$aDynValue['pinno_' . $this->sCurrentPayment]))
                $sErrorMessage = $this->oNovalnetUtil->oLang->translateString('NOVALNET_FRAUD_MODULE_PIN_INVALID');

            if (!empty($sErrorMessage))
                $this->oNovalnetOxUtils->redirect($this->oNovalnetUtil->setRedirectURL($sErrorMessage));
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
        if (!empty($blNoScriptCreditcard))
            $this->oNovalnetOxUtils->redirect($this->oNovalnetUtil->setRedirectURL($this->oNovalnetUtil->oLang->translateString('NOVALNET_NOSCRIPT_MESSAGE')));
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
        if (!empty($blNoScriptSepa))
            $this->oNovalnetOxUtils->redirect($this->oNovalnetUtil->setRedirectURL($this->oNovalnetUtil->oLang->translateString('NOVALNET_NOSCRIPT_MESSAGE')));

        if (!isset($aDynValue['novalnet_sepa_new_details']) || $aDynValue['novalnet_sepa_new_details'] == 1) {
            foreach ($this->_aRequiredSepaFields as $sRequiredFields) {
                if (empty($aDynValue[$sRequiredFields])) {
                    $sErrorMessage = $this->oNovalnetUtil->oLang->translateString('NOVALNET_SEPA_INVALID_DETAILS');
                    $this->oNovalnetOxUtils->redirect($this->oNovalnetUtil->setRedirectURL($sErrorMessage));
                }
            }
        }
        if (isset($aDynValue['novalnet_sepa_new_details'])) {
            $this->oNovalnetUtil->oSession->setVariable('blOneClicknovalnetsepa', $aDynValue['novalnet_sepa_new_details']);
            if ($aDynValue['novalnet_sepa_new_details'] == 0)
                $this->oNovalnetUtil->oSession->deleteVariable('blCallbackEnablednovalnetsepa');
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
        if ($this->iCallbackType == '1' && !is_numeric($aDynValue['pinbycall_' . $this->sCurrentPayment]))
            $sErrorMessage = $this->oNovalnetUtil->oLang->translateString('NOVALNET_FRAUD_MODULE_PHONE_INVALID');
        elseif ($this->iCallbackType == '2' && !is_numeric($aDynValue['pinbysms_' . $this->sCurrentPayment]))
            $sErrorMessage = $this->oNovalnetUtil->oLang->translateString('NOVALNET_FRAUD_MODULE_MOBILE_INVALID');

        if (!empty($sErrorMessage))
            $this->oNovalnetOxUtils->redirect($this->oNovalnetUtil->setRedirectURL($sErrorMessage));

        return true;
    }
}
?>
