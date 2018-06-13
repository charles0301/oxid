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

namespace oe\novalnet\Model;

use oe\novalnet\Classes\NovalnetUtil;

/**
 * Class PaymentGateway.
 */
class PaymentGateway extends PaymentGateway_parent
{
    /**
     * Get Util class
     *
     * @var string
     */
    protected $_oNovalnetUtil;

    /**
     * Get current payment
     *
     * @var string
     */
    protected $_sCurrentPayment;

    /**
     * Get Error message
     *
     * @var string
     */
    protected $_sLastError;
     /**
     * Novalnet redirection payments
     *
     * @var array
     */
    private $_aRedirectPayments = ['novalnetonlinetransfer', 'novalnetideal', 'novalnetpaypal', 'novalneteps', 'novalnetgiropay', 'novalnetprzelewy24'];

      /**
     * Novalnet module version
     *
     * @var array
     */
    public $sNovalnetVersion = '11.2.1';


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
        if (!preg_match("/novalnet/i", $this->_sCurrentPayment)) {
            return parent::executePayment($dAmount, $oOrder);
        }

        $this->_oNovalnetUtil    = oxNew(NovalnetUtil::class);
        $sCallbackTid            = $this->_oNovalnetUtil->oSession->getVariable('sCallbackTid' . $this->_sCurrentPayment);

        // verifies payment call type is to handle fraud prevention or redirect payment response or proceed payment
        if ($sCallbackTid) { // if true proceeds second call for Novalnet fraud prevention

            // validates the order amount of the transaction and the current cart amount are differed
            if ($this->_validateNovalnetCallbackAmount($dAmount) === false)
                return false;

            // performs the fraud prevention second call for transaction
            $aPinResponse = $this->doFraudModuleSecondCall($this->_sCurrentPayment);

            // handles the fraud prevention second call response of the transaction
            if ($this->_validateNovalnetPinResponse($aPinResponse) === false)
                return false;

        } elseif ($this->_oNovalnetUtil->oConfig->getRequestParameter('tid') && $this->_oNovalnetUtil->oConfig->getRequestParameter('status')) {

            // checks to validate the redirect response
            if ($this->_validateNovalnetRedirectResponse() === false)
                return false;

        } else {
            // performs the transaction call
            $aNovalnetResponse = $this->doPayment($oOrder);
            if ($aNovalnetResponse['status'] != '100') {
                $this->_sLastError = $this->_oNovalnetUtil->setNovalnetPaygateError($aNovalnetResponse);
                return false;
            }

            $blCallbackEnabled = $this->_oNovalnetUtil->oSession->getVariable('blCallbackEnabled' . $this->_sCurrentPayment);

            // checks callback enabled to set the message for fraud prevention type
            if ($blCallbackEnabled) {
                $sFraudModuleMessage = '';
                $this->_oNovalnetUtil->oSession->setVariable('sCallbackTid' . $this->_sCurrentPayment, $aNovalnetResponse['tid']);
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
     * Performs payment request for all payments and return response for direct payments
     *
     * @param object $oOrder
     *
     * @return array
     */
    public function doPayment($oOrder)
    {
        $aNovalnetURL = ['PAYGATE'        => 'https://payport.novalnet.de/paygate.jsp',
                               'CC3DPCI'        => 'https://payport.novalnet.de/pci_payport',
                               'ONLINETRANSFER' => 'https://payport.novalnet.de/online_transfer_payport',
                               'IDEAL'          => 'https://payport.novalnet.de/online_transfer_payport',
                               'PAYPAL'         => 'https://payport.novalnet.de/paypal_payport',
                               'EPS'            => 'https://payport.novalnet.de/giropay',
                               'GIROPAY'        => 'https://payport.novalnet.de/giropay',
                               'PRZELEWY24'     => 'https://payport.novalnet.de/globalbank_transfer'
                            ];
        $this->sPaymentName    = strtoupper(substr($this->_sCurrentPayment, 8, strlen($this->_sCurrentPayment)));

        // prepares the parameter passed to Novalnet gateway
        $aRequest = $this->_importNovalnetParams();

         // perform the payment call to Novalnet server - if not redirect payments then makes curl request other wise redirect to Novalnet server
        if (!in_array($this->_sCurrentPayment, $this->_aRedirectPayments)) {
            $aResponse = $this->_oNovalnetUtil->doCurlRequest($aRequest, $aNovalnetURL['PAYGATE']);
            $this->_oNovalnetUtil->oSession->setVariable('aNovalnetGatewayResponse', $aResponse);
            return $aResponse;
        } else {
            $sNovalnetURL = $this->_sCurrentPayment != 'novalnetcreditcard' ? $aNovalnetURL[$this->sPaymentName] : $aNovalnetURL['CC3DPCI'];
            $this->_oNovalnetUtil->oSession->setVariable('aNovalnetRedirectRequest', $aRequest);
            $this->_oNovalnetUtil->oSession->setVariable('sNovalnetRedirectURL', $sNovalnetURL);
            $oOrder->delete();
            $sRedirectURL = $this->_oNovalnetUtil->oConfig->getShopCurrentURL() . 'cl=novalnetredirectcontroller';
            \OxidEsales\Eshop\Core\Registry::getUtils()->redirect($sRedirectURL);
        }

    }

    /**
     * Performs payment confirmation while Fraud module is enabled
     *
     * @param string $sCurrentPayment
     *
     * @return array
     */
    public function doFraudModuleSecondCall($sCurrentPayment)
    {
        $aFirstRequest  = $this->_oNovalnetUtil->oSession->getVariable('aNovalnetGatewayRequest');
        $aFirstResponse = $this->_oNovalnetUtil->oSession->getVariable('aNovalnetGatewayResponse');
        $iRequestType   = $this->_oNovalnetUtil->getNovalnetConfigValue('iCallback' . $sCurrentPayment);
        $aDynValue      = array_map('trim', $this->_oNovalnetUtil->oSession->getVariable('dynvalue'));
        $sRemoteIp      = $this->_oNovalnetUtil->getIpAddress();

        // checks the second call request type of fraud prevention payments
        if ($aDynValue['newpin_' . $sCurrentPayment])
            $sRequestType = 'TRANSMIT_PIN_AGAIN';
        elseif (in_array($iRequestType, ['1', '2']))
            $sRequestType = 'PIN_STATUS';

        $sPinXmlRequest = '<?xml version="1.0" encoding="UTF-8"?>
                              <nnxml>
                                  <info_request>
                                      <vendor_id>' . $aFirstRequest['vendor'] . '</vendor_id>
                                      <vendor_authcode>' . $aFirstRequest['auth_code'] . '</vendor_authcode>
                                      <request_type>' . $sRequestType . '</request_type>
                                      <tid>' . $aFirstResponse['tid'] . '</tid>
                                      <remote_ip>' . $sRemoteIp . '</remote_ip>';

        if ($sRequestType == 'PIN_STATUS')
            $sPinXmlRequest .= '<pin>' . trim($aDynValue['pinno_' . $sCurrentPayment]) . '</pin>';

        $sPinXmlRequest .= '</info_request></nnxml>';
        $sPinXmlResponse = $this->_oNovalnetUtil->doCurlRequest($sPinXmlRequest, 'https://payport.novalnet.de/nn_infoport.xml', false);
        preg_match('/status>?([^<]+)/i', $sPinXmlResponse, $aStatus);
        $aResponse['status'] = $aStatus[1];
        preg_match('/status_message>?([^<]+)/i', $sPinXmlResponse, $aMessage);
        $aResponse['status_desc'] = $aMessage[1];
        preg_match('/tid_status>?([^<]+)/i', $sPinXmlResponse, $aTidStatus);
        $aResponse['tid_status'] = $aTidStatus[1];

        if (!empty($aResponse['tid_status'])) {
            $aFirstResponse['tid_status'] = $aTidStatus[1];
            $this->_oNovalnetUtil->oSession->setVariable('aNovalnetGatewayResponse', $aFirstResponse);
        }

        return $aResponse;
    }

    /**
     * Get User details
     *
     * @param null
     * @return oxuser
     */
    public function getUserData()
    {
        $oUser    = oxNew(\OxidEsales\Eshop\Application\Model\User::class);
        $sUserID  = $this->_oNovalnetUtil->oSession->getVariable('usr');
        $oUser->load($sUserID);
       return $oUser;
    }

    /**
     * Imports Novalnet parameters for payment call
     *
     * @return array
     */
    private function _importNovalnetParams()
    {
       $aRequest = array();
       $this->_importNovalnetCredentials($aRequest);
       $oUser = $this->getUserData();
       $this->_oNovalnetUtil->setAffiliateCredentials($aRequest, $oUser->oxuser__oxcustnr->value);
       $this->_importUserDetails($aRequest, $oUser);
       $this->_importReferenceParameters($aRequest);
       $this->_importOrderDetails($aRequest);
       $this->_importPaymentDetails($aRequest);
       $this->_importGuaranteedPaymentParameters($aRequest);
        $this->_oNovalnetUtil->oSession->setVariable('aNovalnetGatewayRequest', $aRequest); // Store novalnet request in session to use at the end of the transaction

        // encodes the params and generates hash for redirect payments
        if (in_array($this->_sCurrentPayment, $this->_aRedirectPayments)) {
            $this->_importRedirectPaymentParameters($aRequest);
            $this->_encodeNovalnetParams($aRequest);
        }
        $aRequest = array_map('trim', $aRequest);

        return $aRequest;
    }

    /**
     * Imports Novalnet parameters for payment call
     *
     * @param array  $aRequest
     * @param string $oUser
     *
     * @return array
     */
    private function _importUserDetails(&$aRequest, $oUser)
    {
         list($sFirstName, $sLastName) = $this->_oNovalnetUtil->retriveName($oUser);

         $aRequest['first_name'] = $this->_oNovalnetUtil->setUTFEncode($sFirstName);
         $aRequest['last_name']  = $this->_oNovalnetUtil->setUTFEncode($sLastName);
         $aRequest['city']       = $this->_oNovalnetUtil->setUTFEncode($oUser->oxuser__oxcity->value);
         $aRequest['zip']        = $oUser->oxuser__oxzip->value;
         $aRequest['email']      = $oUser->oxuser__oxusername->value;

         $aRequest['gender']      = 'u';
         $aRequest['customer_no'] = $oUser->oxuser__oxcustnr->value;
         $aRequest['tel']         = (!empty($oUser->oxuser__oxfon->value)) ? $oUser->oxuser__oxfon->value : $oUser->oxuser__oxprivfon->value;
         $aRequest['street']      = $this->_oNovalnetUtil->setUTFEncode($oUser->oxuser__oxstreet->value);
         $aRequest['house_no']    = trim($oUser->oxuser__oxstreetnr->value);
         $aRequest['session']     = $this->_oNovalnetUtil->oSession->getId();
         $aRequest['system_name'] = 'oxideshop';
         $aRequest['system_version'] = $this->_oNovalnetUtil->oConfig->getVersion() . '-NN' . $this->sNovalnetVersion;
         $aRequest['system_url']  = $this->_oNovalnetUtil->oConfig->getShopMainUrl();
         $aRequest['system_ip']   = $this->_oNovalnetUtil->getIpAddress(true);
         $aRequest['remote_ip']   = $this->_oNovalnetUtil->getIpAddress();
         $aRequest['lang']        = strtoupper($this->_oNovalnetUtil->oLang->getLanguageAbbr());
         $aRequest['country_code']= $this->_oNovalnetUtil->getCountryISO($oUser->oxuser__oxcountryid->value);

        if ($oUser->oxuser__oxbirthdate->value != '0000-00-00')
            $aRequest['birth_date'] = date('Y-m-d', strtotime($oUser->oxuser__oxbirthdate->value));

        $oAddress = $oUser->getSelectedAddress();
        $sCompany = (!empty($oUser->oxuser__oxcompany->value) ? $oUser->oxuser__oxcompany->value : (!empty($oAddress->oxaddress__oxcompany->value) ? $oAddress->oxaddress__oxcompany->value : ''));

        if ($sCompany)
            $aRequest['company'] = $sCompany;

        if (!empty($oUser->oxuser__oxmobfon->value))
            $aRequest['mobile'] = $oUser->oxuser__oxmobfon->value;

        if (!empty($oUser->oxuser__oxfax->value))
            $aRequest['fax'] = $oUser->oxuser__oxfax->value;

    }

    /**
     * Imports reference parameters
     *
     * @param array &$aRequest
     */
    private function _importReferenceParameters(&$aRequest)
    {
        $sReferrerId    = $this->_oNovalnetUtil->getNovalnetConfigValue('sReferrerID');
        $sNotifyURL     = $this->_oNovalnetUtil->getNovalnetConfigValue('sNotifyURL');
        $sReferrenceOne = $this->_oNovalnetUtil->getNovalnetConfigValue('sReferenceOne' . $this->_sCurrentPayment);
        $sReferrenceTwo = $this->_oNovalnetUtil->getNovalnetConfigValue('sReferenceTwo' . $this->_sCurrentPayment);
        if ($sReferrerId) {
            $aRequest['referrer_id'] = $sReferrerId;
        }

        if ($sReferrenceOne) {
            $aRequest['input1']    = 'Reference 1';
            $aRequest['inputval1'] = $sReferrenceOne;
        }

        if ($sReferrenceTwo) {
            $aRequest['input2']    = 'Reference 2';
            $aRequest['inputval2'] =  $sReferrenceTwo;
        }

        $aRequest['notify_url'] = ($sNotifyURL) ? $sNotifyURL : $this->_oNovalnetUtil->oConfig->getShopCurrentURL() . 'cl=novalnetcallback&fnc=handlerequest';
    }

    /**
     * Get Order details
     *
     * @param array  &$aRequest
     */
    private function _importOrderDetails(&$aRequest)
    {
        $oBasket = $this->_oNovalnetUtil->oSession->getBasket();
        $aPaymentType = ['novalnetcreditcard' => 'CREDITCARD', 'novalnetsepa' => 'DIRECT_DEBIT_SEPA', 'novalnetinvoice' => 'INVOICE_START', 'novalnetprepayment' => 'INVOICE_START', 'novalnetonlinetransfer' => 'ONLINE_TRANSFER', 'novalnetideal' => 'IDEAL', 'novalnetpaypal' => 'PAYPAL', 'novalneteps' => 'EPS', 'novalnetgiropay' => 'GIROPAY', 'novalnetprzelewy24' => 'PRZELEWY24' , 'novalnetbarzahlen' => 'CASHPAYMENT'];

        $this->dOrderAmount       = str_replace(',', '', number_format($oBasket->getPrice()->getBruttoPrice(), 2)) * 100;
        $dOnHoldLimit             = $this->_oNovalnetUtil->getNovalnetConfigValue('dOnholdLimit'. $this->_sCurrentPayment);
        $aRequest['amount']       = $this->dOrderAmount;
        $aRequest['currency']     = $oBasket->getBasketCurrency()->name;
        $aRequest['payment_type'] = $aPaymentType[$this->_sCurrentPayment];

        // checks to set the onhold
        if (in_array($this->_sCurrentPayment, ['novalnetcreditcard', 'novalnetsepa', 'novalnetinvoice', 'novalnetpaypal']) && is_numeric($dOnHoldLimit) && $dOnHoldLimit <= $aRequest['amount'])
            $aRequest['on_hold'] = 1;

        // checks the shop type is zero amount booking and sets amount as zero for credit card, sepa and paypal payments
        if (in_array($aRequest['key'], ['6', '37', '34']) && $this->iTariffType == '2' && $this->_oNovalnetUtil->getNovalnetConfigValue('iShopType'.$this->_sCurrentPayment) == '2' &&  $this->_oNovalnetUtil->oSession->getVariable('blGuaranteeEnabled' . $this->_sCurrentPayment) !== 1) {
            $aRequest['amount'] = 0;
            unset($aRequest['on_hold']);
        }
    }

    /**
     * Get payment details
     *
     * @param array &$aRequest
     */
    private function _importPaymentDetails(&$aRequest)
    {
        $aDynValue = array_map('trim', $this->_oNovalnetUtil->oSession->getVariable('dynvalue'));
        $this->sPaymentName    = strtoupper(substr($this->_sCurrentPayment, 8, strlen($this->_sCurrentPayment)));
        if ($this->_sCurrentPayment == 'novalnetcreditcard') {
            // checks the payment is proceed with one click shopping or not - credit card
            if (isset($aDynValue['novalnet_cc_new_details']) && $aDynValue['novalnet_cc_new_details'] == '0') {
                $aRequest['payment_ref'] = $this->_oNovalnetUtil->oSession->getVariable('sPaymentRefnovalnetcreditcard');
                $this->_oNovalnetUtil->oSession->deleteVariable('sPaymentRefnovalnetcreditcard');
            } else {
                $aRequest['nn_it']     = 'iframe';
                $aRequest['unique_id'] = $aDynValue['novalnet_cc_uniqueid'];
                $aRequest['pan_hash']  = $aDynValue['novalnet_cc_hash'];

               if ($this->_oNovalnetUtil->getNovalnetConfigValue('blCC3DActive') == '1' || $this->_oNovalnetUtil->getNovalnetConfigValue('blCC3DFraudActive') == '1') {
                    if ($this->_oNovalnetUtil->getNovalnetConfigValue('blCC3DActive') == '1') {
                        $aRequest['cc_3d'] = 1;
                    }
                    // checks to set credit card payment as redirect
                    array_push($this->_aRedirectPayments, 'novalnetcreditcard');

                    if ($this->_oNovalnetUtil->getNovalnetConfigValue('iShopTypenovalnetcreditcard') == '2')
                        $aRequest['create_payment_ref'] = 1;

                } elseif ($this->_oNovalnetUtil->getNovalnetConfigValue('iShopTypenovalnetcreditcard') != '') {
                    $aRequest['create_payment_ref'] = 1;
                }
            }
        } elseif ($this->_sCurrentPayment == 'novalnetsepa') {
            $aRequest['sepa_due_date']       = $this->getDueDate(); // sets due date for direct debit sepa
            // checks the payment is proceed with one click shopping or not - direct debit sepa
            if (isset($aDynValue['novalnet_sepa_new_details']) && $aDynValue['novalnet_sepa_new_details'] == '0') {
                $aRequest['payment_ref'] = $this->_oNovalnetUtil->oSession->getVariable('sPaymentRefnovalnetsepa');
                $this->_oNovalnetUtil->oSession->deleteVariable('sPaymentRefnovalnetsepa');
            } else {
                $aRequest['bank_account_holder'] = $aDynValue['novalnet_sepa_holder'];
                $aRequest['iban_bic_confirmed']  = 1;
                $aRequest['sepa_unique_id']      = $aDynValue['novalnet_sepa_uniqueid'];
                $aRequest['sepa_hash']           = $aDynValue['novalnet_sepa_hash'];
                if ($this->_oNovalnetUtil->getNovalnetConfigValue('iShopTypenovalnetsepa') != '') {
                    $aRequest['create_payment_ref'] = 1;
                }
            }
        } elseif (in_array($this->_sCurrentPayment, ['novalnetinvoice', 'novalnetprepayment'])) {
            $aRequest['invoice_type'] = $this->sPaymentName;
            if ($this->_sCurrentPayment == 'novalnetinvoice' && $sDueDate = $this->getDueDate()) {
                $aRequest['due_date'] = $sDueDate;
            }
        } elseif ($this->_sCurrentPayment == 'novalnetpaypal') {
            if (isset($aDynValue['novalnet_paypal_new_details']) && $aDynValue['novalnet_paypal_new_details'] == '0') {
                $aRequest['payment_ref'] = $this->_oNovalnetUtil->oSession->getVariable('sPaymentRefnovalnetpaypal');
                $this->_oNovalnetUtil->oSession->deleteVariable('sPaymentRefnovalnetpaypal');
                unset($this->_aRedirectPayments[2]);
            } elseif ($this->_oNovalnetUtil->getNovalnetConfigValue('iShopTypenovalnetpaypal') != '') {
                $aRequest['create_payment_ref'] = 1;
            }
        } elseif ($this->_sCurrentPayment == 'novalnetbarzahlen' && $sSlipDuedate = $this->getDueDate()) {
             $aRequest['cashpayment_due_date'] = $sSlipDuedate;
        }
        $blCallbackEnabledStatus = $this->_oNovalnetUtil->oSession->getVariable('blCallbackEnabled' . $this->_sCurrentPayment);

        // checks to verify the fraud module activated
        if (in_array($this->_sCurrentPayment, ['novalnetsepa', 'novalnetinvoice']) && !empty($blCallbackEnabledStatus)) {
            // checks the fraud prevention type to add the custom parameters of the fraud prevention
            if ($this->_oNovalnetUtil->getNovalnetConfigValue('iCallback' . $this->_sCurrentPayment) == '1') {
                $aRequest['tel']             = $aDynValue['pinbycall_' . $this->_sCurrentPayment];
                $aRequest['pin_by_callback'] = 1;
            } elseif ($this->_oNovalnetUtil->getNovalnetConfigValue('iCallback' . $this->_sCurrentPayment) == '2') {
                $aRequest['mobile']     = $aDynValue['pinbysms_' . $this->_sCurrentPayment];
                $aRequest['pin_by_sms'] = 1;
            }
            $this->_oNovalnetUtil->oSession->setVariable('dCallbackAmount' . $this->_sCurrentPayment, $this->dOrderAmount);
        }

    }

    /**
     * Imports redirection payment parameters
     *
     * @param array &$aRequest
     */
    private function _importRedirectPaymentParameters(&$aRequest)
    {
        $sReturnURL = htmlspecialchars_decode($this->_oNovalnetUtil->oConfig->getShopCurrentURL()) . 'cl=order&fnc=novalnetGatewayReturn';

        // checks credit card 3d and skips parameters
        if ($this->_sCurrentPayment != 'novalnetcreditcard') {
            $aRequest['implementation']  = 'PHP';
            $aRequest['user_variable_0'] = $this->_oNovalnetUtil->oConfig->getShopMainUrl();
        } else {
            $aRequest['implementation'] = 'PHP_PCI';
        }
        $aRequest = array_merge($aRequest, ['input3'              => 'shop_lang',
                                                  'inputval3'     => $this->_oNovalnetUtil->oLang->getBaseLanguage(),
                                                  'input4'        => 'stoken',
                                                  'inputval4'     => $this->_oNovalnetUtil->oConfig->getRequestParameter('stoken'),
                                                  'uniqid'        => uniqid(),
                                                ]);
        $aRequest['return_url']    = $aRequest['error_return_url'] = $sReturnURL;
        $aRequest['return_method'] = $aRequest['error_return_method'] = 'POST';
    }

    /**
     * Imports guaranteed payment details for direct debit sepa and invoice
     *
     * @param array &$aRequest
     */
    private function _importGuaranteedPaymentParameters(&$aRequest)
    {
         if ($this->_oNovalnetUtil->oSession->getVariable('blGuaranteeEnabled' . $this->_sCurrentPayment)) {
            $aDynValue                = array_map('trim', $this->_oNovalnetUtil->oSession->getVariable('anovalnetdynvalue'));
            $aRequest['payment_type'] = $aRequest['key'] == 27 ? 'GUARANTEED_INVOICE' : 'GUARANTEED_DIRECT_DEBIT_SEPA';
            $aRequest['key']          = $aRequest['key'] == 27 ? 41 : 40; // Default - 41 for Guaranteed Invoice and 40 for Guaranteed Sepa
            $aRequest['birth_date']   = date('Y-m-d', strtotime($aDynValue['birthdate' . $this->_sCurrentPayment]));
        }
        if ($this->_oNovalnetUtil->getNovalnetConfigValue(('blGuaranteeEnablednovalnetsepa') == '1') && $this->_oNovalnetUtil->getNovalnetConfigValue('iShopType'.$this->_sCurrentPayment) == '2') {
            unset($aRequest['create_payment_ref']);
        }
    }

    /**
     * Imports Novalnet credentials
     *
     * @param array &$aRequest
     */
    private function _importNovalnetCredentials(&$aRequest)
    {
         $aPayments = ['novalnetcreditcard' => 6, 'novalnetsepa' => 37, 'novalnetinvoice' => 27,'novalnetprepayment' => 27, 'novalnetonlinetransfer' => 33, 'novalnetideal' => 49, 'novalnetpaypal' => 34, 'novalneteps' => 50, 'novalnetgiropay' => 69, 'novalnetprzelewy24' => 78, 'novalnetbarzahlen' => 59 ];

        $aRequest  = ['vendor'    => $this->_oNovalnetUtil->getNovalnetConfigValue('iVendorId'),
                            'auth_code' => $this->_oNovalnetUtil->getNovalnetConfigValue('sAuthCode'),
                            'product'   => $this->_oNovalnetUtil->getNovalnetConfigValue('iProductId'),
                            'key'       => $aPayments[$this->_sCurrentPayment]
                          ];

        $this->_oNovalnetUtil->oSession->setVariable('sNovalnetAccessKey', $this->_oNovalnetUtil->getNovalnetConfigValue('sAccessKey'));
        $aTariffId             = explode('-', $this->_oNovalnetUtil->getNovalnetConfigValue('sTariffId'));
        $this->iTariffType     = $aTariffId[0];
        $aRequest['tariff']    = $aTariffId[1];
        $aRequest['test_mode'] = $this->_oNovalnetUtil->getNovalnetConfigValue('blTestmode' . $this->_sCurrentPayment);
        // checks subscription payments for adding the subscription parameters
        if ($this->iTariffType != 2) {
            if ($sTariffPeriod = $this->_oNovalnetUtil->getNovalnetConfigValue('sTariffPeriod'))
                $aRequest['tariff_period'] = $sTariffPeriod;
            if ($sTariffPeriod2 = $this->_oNovalnetUtil->getNovalnetConfigValue('sTariffPeriod2'))
                $aRequest['tariff_period2'] = $sTariffPeriod2;
            if ($dTariffPeriod2Amount = $this->_oNovalnetUtil->getNovalnetConfigValue('dTariffPeriod2Amount'))
                $aRequest['tariff_period2_amount'] = $dTariffPeriod2Amount;

        }

    }

    /**
     * Gets due date for invoice and direct debit sepa
     *
     * @return string
     */
    public function getDueDate()
    {
        $iDueDate = trim($this->_oNovalnetUtil->getNovalnetConfigValue('iDueDate' . $this->_sCurrentPayment));
        if ($this->_sCurrentPayment == 'novalnetsepa') {
            $iDueDate = (empty($iDueDate) || $iDueDate <= 6) ? 7 : $iDueDate;
        }
        return ($iDueDate) ? date('Y-m-d', strtotime('+' . $iDueDate . ' days')) : false;
    }

    /**
     * Encodes Novalnet parameters and Generates hash value for redirect payments
     *
     * @param array &$aRequest
     */
    private function _encodeNovalnetParams(&$aRequest)
    {
        // encodes the parameters
        $this->_getEncodeData($aRequest, ['auth_code', 'product', 'tariff', 'amount', 'test_mode', 'uniqid']);

        $aRequest['hash'] = $this->_generateHash($aRequest); // generates the hash
    }

    /**
     * Encode the required parameters
     *
     * @param array &$aRequest
     * @param array $aEncodeFields
     *
     * @return boolean
     */
    private function _getEncodeData(&$aRequest, $aEncodeFields)
    {
        $sKey = $this->_oNovalnetUtil->oSession->getVariable('sNovalnetAccessKey');
        foreach ($aEncodeFields as $sValue) {
            $sData = $aRequest[$sValue];
            try {
                $sCrc   = sprintf('%u', crc32($sData));
                $sData = $sCrc . '|' . $sData;
                $sData = bin2hex($sData . $sKey);
                $sData = strrev(base64_encode($sData));
                $aRequest[$sValue] = $sData;
            } catch(Exception $e) {
                $aRequest[$sValue] = 'Error: CKSum not found!';
            }
        }
    }

     /**
     * Generates the hash value
     *
     * @param array $aRequest
     *
     * @return string
     */
    private function _generateHash($aRequest)
    {
        return md5($aRequest['auth_code'] . $aRequest['product'] . $aRequest['tariff'] . $aRequest['amount'] . $aRequest['test_mode'] . $aRequest['uniqid'] . strrev($this->_oNovalnetUtil->oSession->getVariable('sNovalnetAccessKey')));
    }

    /**
     * Checks the hash value for redirection payment
     *
     * @param array &$aResponse
     */
    public function checkHash(&$aResponse)
    {
        // checks hash2 and newly generated hash - returns false if both are differed
        if ($aResponse['hash2'] != $this->_generateHash($aResponse))
            return false;

        $this->_getDecodeData($aResponse, ['auth_code', 'product', 'tariff', 'amount', 'test_mode', 'uniqid']);
    }

      /**
     * Decodes the required parameters in the response from server
     *
     * @param array &$aResponse
     * @param array $aDecodeParams
     *
     * @return boolean
     */
    private function _getDecodeData(&$aResponse, $aDecodeParams)
    {
        $sKey = $this->_oNovalnetUtil->oSession->getVariable('sNovalnetAccessKey');
        foreach ($aDecodeParams as $sValue) {
            $sData = $aResponse[$sValue];
            try {
                $sData = base64_decode(strrev($sData));
                $sData = pack('H' . strlen($sData), $sData);
                $sData = substr($sData, 0, stripos($sData, $sKey));
                $iPos  = strpos($sData, '|');
                if ($iPos === false) {
                    $aResponse[$sValue] = 'Error: CKSum not found!';
                    continue;
                }

                $sCrc          = substr($sData, 0, $iPos);
                $sResultValue = trim(substr($sData, $iPos+1));
                if ($sCrc != sprintf('%u', crc32($sResultValue)))
                    $aResponse[$sValue] = 'Error; CKSum invalid!';
                else
                    $aResponse[$sValue] = $sResultValue;
            } catch(Exception $e) {
                $aResponse[$sValue] = 'Error: CKSum not found!';
            }
        }
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
            if ($this->checkHash($aNovalnetResponse) === false) {
                $this->_sLastError = $this->_oNovalnetUtil->oLang->translateString('NOVALNET_CHECK_HASH_FAILED_ERROR');
                return false;
            }
            $this->_oNovalnetUtil->oSession->setVariable('aNovalnetGatewayResponse', $aNovalnetResponse);
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
        $dNovalnetCallbackAmount = $this->_oNovalnetUtil->oSession->getVariable('dCallbackAmount' . $this->_sCurrentPayment);

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
                $this->_oNovalnetUtil->oSession->setVariable('blNovalnetPaymentLock' . $this->_sCurrentPayment, 1);
                $this->_oNovalnetUtil->oSession->setVariable('sNovalnetPaymentLockTime' . $this->_sCurrentPayment, time() + (30 * 60));
            } elseif ($aPinResponse['status'] == '0529008') {
                $this->_oNovalnetUtil->oSession->deleteVariable('sCallbackTid'. $this->_sCurrentPayment);
            }
            $this->_sLastError = $this->_oNovalnetUtil->setNovalnetPaygateError($aPinResponse);
            return false;
        }
        return true;
    }
}
?>
