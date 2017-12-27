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
 * Script : PaymentGateway.php
 *
 */
namespace oe\novalnet\Model;

use oe\novalnet\Classes\NovalnetUtil;

class PaymentGateway extends PaymentGateway_parent {
    /**
     * Session object
     *
     * @var object
     */
    protected $_oNovalnetSession;

    protected $_oNovalnetUtil;

    /**
     * Get current payment
     *
     * @var string
     */
    protected $_sCurrentPayment;

    protected $_sLastError;

    /**
     * Executes payment, returns true on success.
     *
     * @param double $dAmount
     * @param object &$oOrder
     *
     * @return boolean
     */
    public function executePayment($dAmount, & $oOrder)
    {
        $this->_sCurrentPayment = $oOrder->sCurrentPayment;

        // checks the current payment method is not a Novalnet payment. If yes then skips the execution of this function
        if (!preg_match("/novalnet/i", $this->_sCurrentPayment))
            return parent::executePayment($dAmount, $oOrder);

        $this->_oNovalnetSession = $this->getSession();
        $this->_oNovalnetUtil = oxNew(NovalnetUtil::class);
        $sCallbackTid           = $this->_oNovalnetSession->getVariable('sCallbackTid' . $this->_sCurrentPayment);

        // verifies payment call type is to handle fraud prevention or redirect payment response or proceed payment
        if ($sCallbackTid) { // if true proceeds second call for Novalnet fraud prevention

            // validates the order amount of the transaction and the current cart amount are differed
            if ($this->_validateNovalnetCallbackAmount($dAmount) === false)
                return false;

            // performs the fraud prevention second call for transaction
            $aPinResponse = $this->_oNovalnetUtil->doFraudModuleSecondCall($this->_sCurrentPayment);

            // handles the fraud prevention second call response of the transaction
            if ($this->_validateNovalnetPinResponse($aPinResponse) === false)
                return false;

        } elseif ($this->_oNovalnetUtil->oConfig->getRequestParameter('tid') && $this->_oNovalnetUtil->oConfig->getRequestParameter('status')) {

            // checks to validate the redirect response
            if ($this->_validateNovalnetRedirectResponse() === false)
                return false;

        } else {

            // performs the transaction call
            $aNovalnetResponse = $this->_oNovalnetUtil->doPayment($oOrder);

            if ($aNovalnetResponse['status'] != '100') {
                $this->_sLastError = $this->_oNovalnetUtil->setNovalnetPaygateError($aNovalnetResponse);
                return false;
            }

            $blCallbackEnabled = $this->_oNovalnetSession->getVariable('blCallbackEnabled' . $this->_sCurrentPayment);

            // checks callback enabled to set the message for fraud prevention type
            if ($blCallbackEnabled) {
                $sFraudModuleMessage = '';
                $this->_oNovalnetSession->setVariable('sCallbackTid' . $this->_sCurrentPayment, $aNovalnetResponse['tid']);
                $iCallbackType = $this->_oNovalnetUtil->getNovalnetConfigValue('iCallback' . $this->_sCurrentPayment);
                if ($iCallbackType == 1) {
                    $sFraudModuleMessage = $this->_oNovalnetUtil->oLang->translateString('NOVALNET_FRAUD_MODULE_PHONE_MESSAGE');
                } elseif ($iCallbackType == 2) {
                    $sFraudModuleMessage = $this->_oNovalnetUtil->oLang->translateString('NOVALNET_FRAUD_MODULE_MOBILE_MESSAGE');
                }
                $this->_sLastError = $sFraudModuleMessage;
                return false;
            }
        }

        // return for success payment
        return true;
    }

    /**
     * Validates Novalnet redirect payment's response
     *
     * @return boolean
     */
    private function _validateNovalnetRedirectResponse()
    {
        $aNovalnetResponse = $_REQUEST;
        // checks the transaction status is success
        if ($aNovalnetResponse['status'] == '100' || ($this->_sCurrentPayment == 'novalnetpaypal' && $aNovalnetResponse['status'] == '90')) {

            // checks the hash value validation for redirect payments
            if ($this->_oNovalnetUtil->checkHash($aNovalnetResponse) === false) {
                $this->_sLastError = $this->_oNovalnetUtil->oLang->translateString('NOVALNET_CHECK_HASH_FAILED_ERROR');
                return false;
            }
            $this->_oNovalnetSession->setVariable('aNovalnetGatewayResponse', $aNovalnetResponse);
        } else {
            $this->_sLastError = $this->_oNovalnetUtil->setNovalnetPaygateError($aNovalnetResponse);
            return false;
        }
        return true;
    }

    /**
     * Validates order amount for Novalnet fraud module
     *
     * @param double $dAmount
     *
     * @return boolean
     */
    private function _validateNovalnetCallbackAmount($dAmount)
    {
        $dCurrentAmount          = str_replace(',', '', number_format($dAmount, 2)) * 100;
        $dNovalnetCallbackAmount = $this->_oNovalnetSession->getVariable('dCallbackAmount' . $this->_sCurrentPayment);

        // terminates the transaction if cart amount and transaction amount in first call are differed
        if ($dNovalnetCallbackAmount != $dCurrentAmount) {
            $this->_oNovalnetUtil->clearNovalnetSession();
            $this->_sLastError = $this->_oNovalnetUtil->oLang->translateString('NOVALNET_FRAUD_MODULE_AMOUNT_CHANGE_ERROR');
            return false;
        }
        return true;
    }

    /**
     * Validates Novalnet response of fraud prevention second call
     *
     * @param array $aPinResponse
     *
     * @return boolean
     */
    private function _validateNovalnetPinResponse($aPinResponse)
    {
        if ($aPinResponse['status'] != '100') {

            //  hides the payment for the user on next 30 minutes if wrong pin provided more than three times
            if ($aPinResponse['status'] == '0529006') {
                $this->_oNovalnetSession->setVariable('blNovalnetPaymentLock' . $this->_sCurrentPayment, 1);
                $this->_oNovalnetSession->setVariable('sNovalnetPaymentLockTime' . $this->_sCurrentPayment, time() + (30 * 60));
            } elseif ($aPinResponse['status'] == '0529008') {
                $this->_oNovalnetSession->deleteVariable('sCallbackTid'. $this->_sCurrentPayment);
            }
            $this->_sLastError = $this->_oNovalnetUtil->setNovalnetPaygateError($aPinResponse);
            return false;
        }
        return true;
    }
}
?>
