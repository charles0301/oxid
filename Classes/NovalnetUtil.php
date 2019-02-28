<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU General Public License
 * that is mentioned with this package in the file Installation_Guide.pdf
 *
 * @author Novalnet <technic@novalnet.de>
 * @copyright Novalnet
 * @license GNU General Public License
 *
 */

namespace oe\novalnet\Classes;

/**
 * Class NovalnetUtil.
 */
class NovalnetUtil {

    /*
     * Get Config value
     *
     * @var object
     */
    public $oConfig;

    /*
     * Get Lang value
     *
     * @var object
     */
    public $oLang;

    /*
     * Get Session value
     *
     * @var object
     */
    public $oSession;

    /**
     * Novalnet module version
     *
     * @var string
     */
    public $sNovalnetVersion = '11.3.0';

    /**
     * Novalnet module configuration
     *
     * @var array
     */
    public $aNovalnetConfig;

     /**
     * Current payment
     *
     * @var string
     */
     public $sCurrentPayment;

    /**
     * Novalnet redirection payments
     *
     * @var array
     */
    public $aRedirectPayments =  ['novalnetonlinetransfer', 'novalnetideal', 'novalnetpaypal', 'novalneteps', 'novalnetgiropay', 'novalnetprzelewy24'];

    /**
     * Novalnet redirect payment URL
     *
     * @var string
     */
    public $sPaygateUrl = 'https://payport.novalnet.de/paygate.jsp';

    /**
     * Novalnet port URL
     *
     * @var string
     */
    public $sPaygateInfoPortUrl = 'https://payport.novalnet.de/nn_infoport.xml';

     /**
     * Novalnet payment Keys
     *
     * @var array
     */
    public $aPaymentKey = ['novalnetcreditcard' => 6, 'novalnetsepa' => 37, 'novalnetinvoice' => 27,'novalnetprepayment' => 27, 'novalnetonlinetransfer' => 33, 'novalnetideal' => 49, 'novalnetpaypal' => 34, 'novalneteps' => 50, 'novalnetgiropay' => 69, 'novalnetprzelewy24' => 78, 'novalnetbarzahlen' => 59];

     /**
     * Novalnet payment Types
     *
     * @var array
     */
    public $aPaymentType = ['novalnetcreditcard' => 'CREDITCARD', 'novalnetsepa' => 'DIRECT_DEBIT_SEPA', 'novalnetinvoice' => 'INVOICE_START', 'novalnetprepayment' => 'INVOICE_START', 'novalnetonlinetransfer' => 'ONLINE_TRANSFER', 'novalnetideal' => 'IDEAL', 'novalnetpaypal' => 'PAYPAL', 'novalneteps' => 'EPS', 'novalnetgiropay' => 'GIROPAY', 'novalnetprzelewy24' => 'PRZELEWY24' , 'novalnetbarzahlen' => 'CASHPAYMENT'];

    public function __construct()
    {
        $this->oConfig         = \OxidEsales\Eshop\Core\Registry::getConfig();
        $this->oLang           = \OxidEsales\Eshop\Core\Registry::getLang();
        $this->oSession        = \OxidEsales\Eshop\Core\Registry::getSession();
        $this->aNovalnetConfig = $this->oConfig->getShopConfVar('aNovalnetConfig', '', 'novalnet');
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
        $aNovalnetURL = [      'novalnetcreditcard'     => 'https://payport.novalnet.de/pci_payport',
                               'novalnetonlinetransfer' => 'https://payport.novalnet.de/online_transfer_payport',
                               'novalnetideal'          => 'https://payport.novalnet.de/online_transfer_payport',
                               'novalnetpaypal'         => 'https://payport.novalnet.de/paypal_payport',
                               'novalneteps'            => 'https://payport.novalnet.de/giropay',
                               'novalnetgiropay'        => 'https://payport.novalnet.de/giropay',
                               'novalnetprzelewy24'     => 'https://payport.novalnet.de/globalbank_transfer'
                        ];
        $oBasket = $this->oSession->getBasket();
        $oUser   = $oOrder->getOrderUser();
        $this->sCurrentPayment = $oBasket->getPaymentId();

        // prepares the parameter passed to Novalnet gateway
        $aRequest = $this->_importNovalnetParams($oBasket, $oUser);

         // perform the payment call to Novalnet server - if not redirect payments then makes curl request other wise redirect to Novalnet server
        if (!in_array($this->sCurrentPayment, $this->aRedirectPayments)) {
            $aResponse = $this->doCurlRequest($aRequest, $this->sPaygateUrl);
            $this->oSession->setVariable('aNovalnetGatewayResponse', $aResponse);
            return $aResponse;
        } else {
            $this->oSession->setVariable('aNovalnetRedirectRequest', $aRequest);            
            $this->oSession->setVariable('sNovalnetRedirectURL', $aNovalnetURL[$this->sCurrentPayment]);
            $oOrder->delete();
            $sRedirectURL = $this->oConfig->getShopCurrentURL() . 'cl=novalnetredirectcontroller';
            \OxidEsales\Eshop\Core\Registry::getUtils()->redirect($sRedirectURL);
        }
    }

    /**
     * Performs CURL request
     *
     * @param mixed   $mxRequest
     * @param string  $sUrl
     * @param boolean $blBuildQuery
     * @param boolean $blAutoConfig
     * @return mixed
     */
    public function doCurlRequest($mxRequest, $sUrl, $blBuildQuery = true, $blAutoConfig = true)
    {
        $sPaygateQuery = ($blBuildQuery) ? http_build_query($mxRequest) : $mxRequest;
        $iCurlTimeout  = $this->getNovalnetConfigValue('iGatewayTimeOut');
        $sProxy        = $this->getNovalnetConfigValue('sProxy');
        $oCurl = oxNew(\OxidEsales\Eshop\Core\Curl::class);
        $oCurl->setMethod('POST');
        $oCurl->setUrl($sUrl);
        $oCurl->setQuery($sPaygateQuery);
        $oCurl->setOption('CURLOPT_FOLLOWLOCATION', 0);
        $oCurl->setOption('CURLOPT_SSL_VERIFYHOST', false);
        $oCurl->setOption('CURLOPT_SSL_VERIFYPEER', false);
        $oCurl->setOption('CURLOPT_RETURNTRANSFER', 1);
        $oCurl->setOption('CURLOPT_TIMEOUT', (is_numeric($iCurlTimeout) ? $iCurlTimeout : 240));
        if ($sProxy)
            $oCurl->setOption('CURLOPT_PROXY', $sProxy);

        $mxData = $oCurl->execute();

        if ($blBuildQuery && $blAutoConfig)
            parse_str($mxData, $mxData);
        
        return $mxData;
    }

    /**
     * Imports Novalnet parameters for payment call
     *
     * @param object $oBasket
     * @param object $oUser
     *
     * @return array
     */
    private function _importNovalnetParams($oBasket, $oUser)
    {
       $aRequest = [];
       $this->_importNovalnetCredentials($aRequest);
       $this->setAffiliateCredentials($aRequest, $oUser->oxuser__oxcustnr->value);
       $this->_importUserDetails($aRequest, $oUser);
       $this->_importReferenceParameters($aRequest);
       $this->_importGuaranteedPaymentParameters($aRequest);
       $this->_importOrderDetails($aRequest);
       $this->_importPaymentDetails($aRequest);
       $this->oSession->setVariable('aNovalnetGatewayRequest', $aRequest);

        // encodes the params and generates hash for redirect payments
        if (in_array($this->sCurrentPayment, $this->aRedirectPayments)) {
            $this->_importRedirectPaymentParameters($aRequest);
            $this->_encodeNovalnetParams($aRequest);
        }
        $aRequest = array_map('trim', $aRequest);

        return $aRequest;
    }

    /**
     * Imports Novalnet credentials
     *
     * @param array &$aRequest
     */
    private function _importNovalnetCredentials(&$aRequest)
    {
       $aRequest  = [        'vendor'    => $this->getNovalnetConfigValue('iVendorId'),
                             'auth_code' => $this->getNovalnetConfigValue('sAuthCode'),
                             'product'   => $this->getNovalnetConfigValue('iProductId'),
                             'key'       => $this->aPaymentKey[$this->sCurrentPayment]
                     ];

        $this->oSession->setVariable('sNovalnetAccessKey', $this->getNovalnetConfigValue('sAccessKey'));
        $aTariffId             = explode('-', $this->getNovalnetConfigValue('sTariffId'));
        $this->iTariffType     = $aTariffId[0];
        $aRequest['tariff']    = $aTariffId[1];
        $aRequest['test_mode'] = $this->getNovalnetConfigValue('blTestmode' . $this->sCurrentPayment);
    }

     /**
     * Imports Novalnet parameters for payment call
     *
     * @param array  &$aRequest
     * @param object $oUser
     *
     * @return array
     */
    private function _importUserDetails(&$aRequest, $oUser)
    {
         list($sFirstName, $sLastName) = $this->retriveName($oUser);
         $aRequest['first_name']  = $this->setUTFEncode($sFirstName);
         $aRequest['last_name']   = $this->setUTFEncode($sLastName);
         $aRequest['city']        = $this->setUTFEncode($oUser->oxuser__oxcity->value);
         $aRequest['zip']         = $oUser->oxuser__oxzip->value;
         $aRequest['email']       = $oUser->oxuser__oxusername->value;
         $aRequest['gender']      = 'u';
         $aRequest['customer_no'] = $oUser->oxuser__oxcustnr->value;
         $aRequest['tel']         = (!empty($oUser->oxuser__oxfon->value)) ? $oUser->oxuser__oxfon->value : $oUser->oxuser__oxprivfon->value;
         $aRequest['street']      = $this->setUTFEncode($oUser->oxuser__oxstreet->value);
         $aRequest['house_no']    = trim($oUser->oxuser__oxstreetnr->value);
         $aRequest['session']     = $this->oSession->getId();
         $aRequest['system_name'] = 'oxideshop';
         $aRequest['system_version'] = $this->oConfig->getVersion() . '-NN-' . $this->sNovalnetVersion;
         $aRequest['system_url']  = $this->oConfig->getShopMainUrl();
         $aRequest['system_ip']   = $this->getIpAddress(true);
         $aRequest['remote_ip']   = $this->getIpAddress();
         $aRequest['lang']        = strtoupper($this->oLang->getLanguageAbbr());
         $aRequest['country_code']= $this->getCountryISO($oUser->oxuser__oxcountryid->value);

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
        if ($sReferrerId = $this->getNovalnetConfigValue('sReferrerID'))
            $aRequest['referrer_id'] = $sReferrerId;

        $sNotifyURL = $this->getNovalnetConfigValue('sNotifyURL');
        $aRequest['notify_url'] = ($sNotifyURL) ? $sNotifyURL : $this->oConfig->getShopCurrentURL() . 'cl=novalnetcallback&fnc=handlerequest';
    }

    /**
     * Get Order details
     *
     * @param array  &$aRequest
     */
    private function _importOrderDetails(&$aRequest)
    {
        $oBasket = $this->oSession->getBasket();
        $this->dOrderAmount       = str_replace(',', '', number_format($oBasket->getPrice()->getBruttoPrice(), 2)) * 100;
        $dOnHoldLimit             = $this->getNovalnetConfigValue('dOnholdLimit'. $this->sCurrentPayment);
        $aRequest['amount']       = $this->dOrderAmount;
        $aRequest['currency']     = $oBasket->getBasketCurrency()->name;
        $aRequest['payment_type'] = $this->aPaymentType[$this->sCurrentPayment];

        // checks to set the onhold
        if (in_array($this->sCurrentPayment, ['novalnetcreditcard', 'novalnetsepa', 'novalnetinvoice', 'novalnetpaypal']) && is_numeric($dOnHoldLimit) && $dOnHoldLimit <= $aRequest['amount']) {
           $aRequest['on_hold'] = 1;
        }

        // checks the shop type is zero amount booking and sets amount as zero for credit card, sepa and paypal payments
        if (in_array($aRequest['key'], ['6', '37', '34']) && $this->iTariffType == '2' && $this->getNovalnetConfigValue('iShopType'.$this->sCurrentPayment) == '2') {
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
        $aDynValue = array_map('trim', $this->oSession->getVariable('dynvalue'));

        if ($this->sCurrentPayment == 'novalnetcreditcard') {
            // checks the payment is proceed with one click shopping or not - credit card
            if (isset($aDynValue['novalnet_cc_new_details']) && $aDynValue['novalnet_cc_new_details'] != '1') {
                $aRequest['payment_ref'] = $this->oSession->getVariable('sPaymentRefnovalnetcreditcard');
                $this->oSession->deleteVariable('sPaymentRefnovalnetcreditcard');
            } else {
                $aRequest['nn_it']     = 'iframe';
                $aRequest['unique_id'] = $aDynValue['novalnet_cc_uniqueid'];
                $aRequest['pan_hash']  = $aDynValue['novalnet_cc_hash'];

               if ($this->getNovalnetConfigValue('blCC3DActive') == '1' || $this->getNovalnetConfigValue('blCC3DFraudActive') == '1') {
                    if ($this->getNovalnetConfigValue('blCC3DActive') == '1') {
                        $aRequest['cc_3d'] = 1;
                    }
                    // checks to set credit card payment as redirect
                    array_push($this->aRedirectPayments, 'novalnetcreditcard');
                } elseif (($this->getNovalnetConfigValue('iShopTypenovalnetcreditcard') == '1' && (isset($aDynValue['novalnet_cc_save_card']) && $aDynValue['novalnet_cc_save_card'] == '1')) || ($this->getNovalnetConfigValue('iShopTypenovalnetcreditcard') == '2')) {
                    $aRequest['create_payment_ref'] = 1;
            }
        }
        } elseif ($this->sCurrentPayment == 'novalnetsepa') {
            $aRequest['sepa_due_date']       = $this->getDueDate(); // sets due date for direct debit sepa
            // checks the payment is proceed with one click shopping or not - direct debit sepa
            if (isset($aDynValue['novalnet_sepa_new_details']) && $aDynValue['novalnet_sepa_new_details'] == '0') {
                $aRequest['payment_ref'] = $this->oSession->getVariable('sPaymentRefnovalnetsepa');
                $this->oSession->deleteVariable('sPaymentRefnovalnetsepa');
            } else {
                $aRequest['bank_account_holder'] = $aDynValue['novalnet_sepa_holder'];
                $aRequest['iban']                = $aDynValue['novalnet_sepa_iban'];

                //store sepa iban value in session to refill when transaction failed.
                $this->oSession->setVariable('refillSepaiban', $aDynValue['novalnet_sepa_iban']);
                if (($this->getNovalnetConfigValue('iShopTypenovalnetsepa') == '1' && (isset($aDynValue['novalnet_sepa_save_card']) && $aDynValue['novalnet_sepa_save_card'] == '1')) || ($this->getNovalnetConfigValue('iShopTypenovalnetsepa') == '2'))
                    $aRequest['create_payment_ref'] = 1;
            }
        } elseif (in_array($this->sCurrentPayment, ['novalnetinvoice', 'novalnetprepayment'])) {
            $aRequest['invoice_type'] = 'PREPAYMENT';
            if ($this->sCurrentPayment == 'novalnetinvoice') {
                $aRequest['invoice_type'] = 'INVOICE';
                $sDueDate = $this->getDueDate();  // set invoice due date
                if($sDueDate)
                $aRequest['due_date'] = $sDueDate;
            }
        } elseif ($this->sCurrentPayment == 'novalnetpaypal') {
            if (isset($aDynValue['novalnet_paypal_new_details']) && $aDynValue['novalnet_paypal_new_details'] == '0') {
                $aRequest['payment_ref'] = $this->oSession->getVariable('sPaymentRefnovalnetpaypal');
                $this->oSession->deleteVariable('sPaymentRefnovalnetpaypal');
                unset($this->aRedirectPayments[2]);
            } elseif (($this->getNovalnetConfigValue('iShopTypenovalnetpaypal') == '1' && (isset($aDynValue['novalnet_paypal_save_card']) && $aDynValue['novalnet_paypal_save_card'] == '1')) || ($this->getNovalnetConfigValue('iShopTypenovalnetpaypal') == '2'))
                $aRequest['create_payment_ref'] = 1;

        } elseif ($this->sCurrentPayment == 'novalnetbarzahlen' && $sSlipDuedate = $this->getDueDate())
             $aRequest['cashpayment_due_date'] = $sSlipDuedate;

        $blCallbackEnabledStatus = $this->oSession->getVariable('blCallbackEnabled' . $this->sCurrentPayment);

        // checks to verify the fraud module activated
        if (in_array($this->sCurrentPayment, ['novalnetsepa', 'novalnetinvoice']) && !empty($blCallbackEnabledStatus)) {

            // checks the fraud prevention type to add the custom parameters of the fraud prevention
            if ($this->getNovalnetConfigValue('iCallback' . $this->sCurrentPayment) == '1') {
                $aRequest['tel']             = $aDynValue['pinbycall_' . $this->sCurrentPayment];
                $aRequest['pin_by_callback'] = 1;
            } elseif ($this->getNovalnetConfigValue('iCallback' . $this->sCurrentPayment) == '2') {
                $aRequest['mobile']     = $aDynValue['pinbysms_' . $this->sCurrentPayment];
                $aRequest['pin_by_sms'] = 1;
            }
            $this->oSession->setVariable('dCallbackAmount' . $this->sCurrentPayment, $this->dOrderAmount);
        }
    }

     /**
     * Imports guaranteed payment details for direct debit sepa and invoice
     *
     * @param array &$aRequest
     */
    private function _importGuaranteedPaymentParameters(&$aRequest)
    {
         if ($this->oSession->getVariable('blGuaranteeEnabled' . $this->sCurrentPayment)) {
            $aDynValue                = array_map('trim', $this->oSession->getVariable('anovalnetdynvalue'));
            $aRequest['payment_type'] = $aRequest['key'] == 27 ? 'GUARANTEED_INVOICE' : 'GUARANTEED_DIRECT_DEBIT_SEPA';
            $aRequest['key']          = $aRequest['key'] == 27 ? 41 : 40;
            $aRequest['birth_date']   = date('Y-m-d', strtotime($aDynValue['birthdate' . $this->sCurrentPayment]));
        }
        if ($this->getNovalnetConfigValue(('blGuaranteeEnablednovalnetsepa') == '1') && $this->getNovalnetConfigValue('iShopType'.$this->sCurrentPayment) == '2')
            unset($aRequest['create_payment_ref']);
    }

     /**
     * Imports redirection payment parameters
     *
     * @param array &$aRequest
     */
    private function _importRedirectPaymentParameters(&$aRequest)
    {
        $sReturnURL = htmlspecialchars_decode($this->oConfig->getShopCurrentURL()) . 'cl=order&fnc=novalnetGatewayReturn';

        // checks credit card 3d and skips parameters
        if ($this->sCurrentPayment != 'novalnetcreditcard')
            $aRequest['user_variable_0'] = $this->oConfig->getShopMainUrl();

        $aRequest = array_merge($aRequest, [      'implementation' => 'ENC',
                                                  'input3'         => 'shop_lang',
                                                  'inputval3'      => $this->oLang->getBaseLanguage(),
                                                  'input4'         => 'stoken',
                                                  'inputval4'      => $this->oConfig->getRequestParameter('stoken'),
                                                  'uniqid'         => $this->getUniqueid(),
                                            ]);
        $aRequest['return_url']    = $aRequest['error_return_url']    = $sReturnURL;
        $aRequest['return_method'] = $aRequest['error_return_method'] = 'POST';
    }

    /**
     * Encodes Novalnet parameters and Generates hash value for redirect payments
     *
     * @param array &$aRequest
     */
    private function _encodeNovalnetParams(&$aRequest)
    {
        $sKey = $this->oSession->getVariable('sNovalnetAccessKey');
        foreach (['auth_code', 'product', 'tariff', 'amount', 'test_mode','tariff_period'] as $key) {
            if (isset($aRequest[$key]))
                $aRequest[$key] = htmlentities(base64_encode(openssl_encrypt($aRequest[$key], "aes-256-cbc",  $sKey, true, $aRequest['uniqid'])));
        }
         $aRequest['hash'] = $this->_generateHash($aRequest);
    }

     /**
     * Gets the Unique Id
     *
     * @return string
     */
    public function getUniqueid()
    {
        $aKeys = explode(',', '8,7,6,5,4,3,2,1,9,0,9,7,6,1,2,3,4,5,6,7,8,9,0');
        shuffle($aKeys);

        return substr(implode($aKeys, ''), 0, 16);
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
          // Generation hash using sha256 and encoded merchant details
        return hash('sha256', ($aRequest['auth_code'].$aRequest['product'].$aRequest['tariff'].$aRequest['amount'].$aRequest['test_mode'].$aRequest['uniqid'].strrev($this->oSession->getVariable('sNovalnetAccessKey'))));
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

        $this->_getDecodeData($aResponse);
    }

    /**
     * Decodes the required parameters in the response from server
     *
     * @param array &$aResponse
     *
     */
    private function _getDecodeData(&$aResponse)
    {
       $sKey = $this->oSession->getVariable('sNovalnetAccessKey');
        foreach (['auth_code','product','tariff','amount','test_mode'] as $key) {
            $aResponse[$key] = openssl_decrypt(base64_decode($aResponse[$key]),"aes-256-cbc", $sKey,true, $aResponse['uniqid']);
        }
     }

    /**
     * Gets Novalnet configuration value
     *
     * @param string $sConfig
     *
     * @return string
     */
    public function getNovalnetConfigValue($sConfig)
    {
        return $this->aNovalnetConfig[$sConfig];
    }

     /**
     * Gets due date for invoice and Barzahlen(Slip Expiry Date)
     *
     * @return string
     */
    public function getDueDate()
    {
        $iDueDate = trim($this->getNovalnetConfigValue('iDueDate' . $this->sCurrentPayment));

        if ($this->sCurrentPayment == 'novalnetsepa') {
            $iDueDate = (empty($iDueDate) || $iDueDate <= 6) ? 7 : $iDueDate;
        }

        return ($iDueDate) ? date('Y-m-d', strtotime('+' . $iDueDate . ' days')) : false;
    }

    /**
     * Sets affiliate credentials for the payment call
     *
     * @param array &$aRequest
     * @param integer $iCustomerNo
     *
     */
    public function setAffiliateCredentials(&$aRequest, $iCustomerNo)
    {
        $oDb     = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(\OxidEsales\Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC);
        $aResult = $oDb->getRow('SELECT AFF_ID FROM novalnet_aff_user_detail WHERE CUSTOMER_ID = "' . $iCustomerNo . '"');
        if (!empty($aResult['AFF_ID']))
            $this->oSession->setVariable('nn_aff_id', $aResult['AFF_ID']);

        // checks Novalnet affiliate id in session
        if ($this->oSession->getVariable('nn_aff_id')) {
            $aResult = $oDb->getRow('SELECT AFF_AUTHCODE, AFF_ACCESSKEY FROM novalnet_aff_account_detail WHERE AFF_ID = "' . $this->oSession->getVariable('nn_aff_id') . '"');
            if (!empty($aResult['AFF_AUTHCODE']) && !empty($aResult['AFF_ACCESSKEY'])) {
                $aRequest['vendor']    = $this->oSession->getVariable('nn_aff_id');
                $aRequest['auth_code'] = $aResult['AFF_AUTHCODE'];
                $this->oSession->setVariable('sNovalnetAccessKey', $aResult['AFF_ACCESSKEY']);
            }
        }
    }

    /**
     * Imports user  first name & last name
     *
     * @param object $oUser
     * @return array
     */
    public function retriveName($oUser)
    {
        $sFirstName = $oUser->oxuser__oxfname->value;
        $sLastName  = $oUser->oxuser__oxlname->value;
        if(empty($sFirstName) || empty($sLastName)) {
            $sName = $sFirstName . $sLastName;
            list($sFirstName, $sLastName) = preg_match('/\s/',$sName) ? explode(' ', $sName, 2) : [$sName, $sName];
        }
        $sFirstName = empty($sFirstName) ? $sLastName : $sFirstName;
        $sLastName = empty($sLastName) ? $sFirstName : $sLastName;

        return [$sFirstName, $sLastName];
    }

    /**
     * Get country ISO code
     *
     * @param string $sCountryId
     *
     * @return string
     */
    public function getCountryISO($sCountryId)
    {
        $oCountry = oxNew(\OxidEsales\Eshop\Application\Model\Country::class);
        $oCountry->load($sCountryId);

        return $oCountry->oxcountry__oxisoalpha2->value;
    }

    /**
     * Set the UTF8 encoding
     *
     * @param string $sStr
     *
     * @return string
     */
    public function setUTFEncode($sStr)
    {
        return (mb_detect_encoding($sStr, 'UTF-8', true) === false) ? utf8_encode($sStr) : $sStr;
    }

    /**
     * Get Server / Remote IP address
     *
     * @param boolean $blServer
     *
     * @return string
     */
    public function getIpAddress($blServer = false)
    {
         if (empty($blServer)) {
            $oUtilsServer = oxNew(\OxidEsales\Eshop\Core\UtilsServer::class);
            return $oUtilsServer->getRemoteAddress();
        } else {
             if (empty($_SERVER['SERVER_ADDR'])) {
                // Handled for IIS server
                return gethostbyname($_SERVER['SERVER_NAME']);
            } else {
                return $_SERVER['SERVER_ADDR'];
            }
        }
    }

    /**
     * Sets error message from the failure response of novalnet
     *
     * @param array $aResponse
     *
     * @return string
     */
    public function setNovalnetPaygateError($aResponse)
    {
        return !empty($aResponse['status_desc']) ? $aResponse['status_desc'] : (!empty($aResponse['status_text']) ? $aResponse['status_text'] : (!empty($aResponse['status_message']) ? $aResponse['status_message'] : $this->oLang->translateString('NOVALNET_DEFAULT_ERROR_MESSAGE')));
    }

    /**
     * Forms invoice comments for invoice and prepayment orders
     *
     * @param array $aInvoiceDetails
     *
     * @return string
     */
    public function getInvoiceComments($aInvoiceDetails)
    {
        $sFormattedAmount = $this->oLang->formatCurrency($aInvoiceDetails['amount']/100, $this->oConfig->getCurrencyObject($aInvoiceDetails['currency'])) . ' ' . $aInvoiceDetails['currency'];
        $sInvoiceComments = '';
        if (!empty($aInvoiceDetails['due_date'])) {
            $sInvoiceComments .= $this->oLang->translateString('NOVALNET_DUE_DATE') . date('d.m.Y', strtotime($aInvoiceDetails['due_date']));
        }

        $sInvoiceComments .= $this->oLang->translateString('NOVALNET_ACCOUNT') . $aInvoiceDetails['invoice_account_holder'];
        $sInvoiceComments .= $this->oLang->translateString('NOVALNET_IBAN') . $aInvoiceDetails['invoice_iban'];
        $sInvoiceComments .= $this->oLang->translateString('NOVALNET_BIC')  . $aInvoiceDetails['invoice_bic'];
        $sInvoiceComments .= $this->oLang->translateString('NOVALNET_BANK') . $aInvoiceDetails['invoice_bankname'] . ' ' . $aInvoiceDetails['invoice_bankplace'];
        $sInvoiceComments .= $this->oLang->translateString('NOVALNET_AMOUNT') . $sFormattedAmount;
        $sInvoiceComments .= $this->oLang->translateString('NOVALNET_INVOICE_MULTI_REF_DESCRIPTION');
        $sInvoiceComments .= $this->oLang->translateString('NOVALNET_PAYMENT_REFERENCE_1') . $aInvoiceDetails['tid'];
        $sInvoiceComments .= $this->oLang->translateString('NOVALNET_PAYMENT_REFERENCE_2') . $aInvoiceDetails['invoice_ref'];

        return $sInvoiceComments;
    }

    /**
     * Sets redirection URL while any invalid conceptual during payment process
     *
     * @param string $sMessage
     *
     * @return string
     */
    public function setRedirectURL($sMessage)
    {
        return $this->oConfig->getSslShopUrl() . 'index.php?cl=payment&payerror=-1&payerrortext=' . urlencode($this->setUTFEncode($sMessage));
    }

    /**
     * Send payment notification mail
     *
     * @param string $sLang
     * @param string $sComments
     * @param integer $iOrderNo
     *
     */
    public function sendPaymentNotificationMail($sLang, $sComments, $iOrderNo)
    {
        $oMail   = oxNew(\OxidEsales\Eshop\Core\Email::class);
        $oLang   = oxNew(\OxidEsales\Eshop\Core\Language::class);
        $oLang->setBaseLanguage($sLang);
        $oDb     = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(\OxidEsales\Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC);
        $sShopName = $this->oConfig->getActiveShop()->oxshops__oxname->rawValue;
        $sQuery = "SELECT OXUSERID from oxorder where OXORDERNR = '".$iOrderNo."'";
        $sRow = $oDb->getRow($sQuery);
        $sSelectQuery = "SELECT OXFNAME, OXLNAME, OXUSERNAME from oxuser where OXID = '".$sRow['OXUSERID']."'";
        $aCustomerData = $oDb->getRow($sSelectQuery);

        if (!empty($aCustomerData['OXUSERNAME'])) {
            $sSubject = $oLang->translateString('NOVALNET_ORDER_CONFIRMATION') . $iOrderNo. $oLang->translateString('NOVALNET_ORDER_CONFIRMATION1')  .$sShopName. $oLang->translateString('NOVALNET_ORDER_CONFIRMATION2') ;
            $email_content    = '<body style="background:#F6F6F6; font-family:Verdana, Arial, Helvetica, sans-serif; font-size:14px; margin:0; padding:0;">
                                    <div style="width:55%;height:auto;margin: 0 auto;background:rgb(247, 247, 247);border: 2px solid rgb(223, 216, 216);border-radius: 5px;box-shadow: 1px 7px 10px -2px #ccc;">
                                        <div style="min-height: 300px;padding:20px;">
                                            <table cellspacing="0" cellpadding="0" border="0" width="100%">

                                                <tr><b>Dear Mr./Ms./Mrs.</b> '.$aCustomerData['OXFNAME'].' '.$aCustomerData['OXLNAME'].' </tr></br></br>

                                                <tr>'.$oLang->translateString('NOVALNET_ORDER_CONFIRMATION3').'</tr></br></br>
                                                <tr>'. $oLang->translateString('NOVALNET_PAYMENT_INFORMATION') .'</br>
                                                '.$sComments.'
                                                </tr></br>

                                            </table>
                                        </div>
                                        <div style="width:100%;height:20px;background:#00669D;"></div>
                                    </div>
                                </body>';
            $oShop = $oMail->getShop();
            $oMail->setFrom($oShop->oxshops__oxorderemail->value);
            $oMail->setRecipient($aCustomerData['OXUSERNAME']);
            $oMail->setSubject( $sSubject );
            $oMail->setBody( $email_content );
            $oMail->send();
        } else {
            return 'Mail not sent<br>';
        }
    }


    /**
     * Clears Novalnet session
     */
    public function clearNovalnetSession()
    {
        $aNovalnetSessions = [      'sNovalnetAccessKey','aNovalnetGatewayRequest', 'aNovalnetGatewayResponse',
                                    'anovalnetdynvalue', 'nn_aff_id', 'dynvalue', 'blOneClicknovalnetsepa', 'blGuaranteeEnablednovalnetsepa',   'blGuaranteeEnablednovalnetinvoice', 'blGuaranteeForceDisablednovalnetsepa', 'blGuaranteeForceDisablednovalnetinvoice',
                                    'blCallbackEnablednovalnetsepa', 'sCallbackTidnovalnetsepa', 'dCallbackAmountnovalnetsepa',
                                    'sCallbackTidnovalnetinvoice','dCallbackAmountnovalnetinvoice'
                              ];
        foreach ($aNovalnetSessions as $sSession) {
            $this->oSession->deleteVariable($sSession);
        }
    }

    /**
     * Clears Novalnet fraud modules session
     */
    public function clearNovalnetFraudModulesSession()
    {
        $aPinPayments = ['novalnetsepa', 'novalnetinvoice'];
        foreach ($aPinPayments as $sPayment) {
            $this->oSession->deleteVariable('sCallbackTid' . $sPayment);
            $this->oSession->deleteVariable('dCallbackAmount' . $sPayment);
        }
    }

    /**
     * Clears Novalnet payment lock
     */
    public function clearNovalnetPaymentLock()
    {
        $aPinPayments = ['novalnetsepa', 'novalnetinvoice'];
        foreach ($aPinPayments as $sPayment) {
            $this->oSession->deleteVariable('blNovalnetPaymentLock' . $sPayment);
            $this->oSession->deleteVariable('sNovalnetPaymentLockTime' . $sPayment);
        }
    }

    /**
     * Forms comments for barzhalan nearest store details
     *
     * @param array   $aBarzahlenDetails
     * @param boolean $blValue
     *
     * @return string
     */
    public function getBarzahlenComments($aBarzahlenDetails , $blValue = false)
    {
        $iStoreCounts = 1;
        if($blValue) {
            $aBarzalan = [];
            foreach ($aBarzahlenDetails as $sKey => $sValue){
                if(stripos($sKey,'nearest_store')!==false)
                    $aBarzalan[$sKey] = $sValue;
            }
            return $aBarzalan;
        }

        foreach ($aBarzahlenDetails as $sKey => $sValue)
        {
            if (strpos($sKey, 'nearest_store_street') !== false)
               $iStoreCounts++;
        }
        $oCountry = oxNew(\OxidEsales\Eshop\Application\Model\Country::class);
        $iDuedate = !empty($aBarzahlenDetails['cashpayment_due_date']) ? $aBarzahlenDetails['cashpayment_due_date']: $aBarzahlenDetails['due_date'];
        $sBarzahlenComments = $this->oLang->translateString('NOVALNET_BARZAHLEN_DUE_DATE') . date('d.m.Y', strtotime($iDuedate));
        if($iStoreCounts !=1)
            $sBarzahlenComments .= $this->oLang->translateString('NOVALNET_BARZAHLEN_PAYMENT_STORE');

        for ($i = 1; $i < $iStoreCounts; $i++)
        {
            $sBarzahlenComments .= $aBarzahlenDetails['nearest_store_title_' . $i] . '<br>';
            $sBarzahlenComments .= $aBarzahlenDetails['nearest_store_street_' . $i ] . '<br>';
            $sBarzahlenComments .= $aBarzahlenDetails['nearest_store_city_' . $i ] . '<br>';
            $sBarzahlenComments .= $aBarzahlenDetails['nearest_store_zipcode_' . $i ] . '<br>';
            $oCountry->loadInLang($this->oLang->getObjectTplLanguage(), $oCountry->getIdByCode($aBarzahlenDetails['nearest_store_country_' . $i ]));
            $sBreak = '<br><br>';
            if ( ($iStoreCounts -2) < $i )
            $sBreak ='';

            $sBarzahlenComments .= $oCountry->oxcountry__oxtitle->value . $sBreak;
        }

        return $sBarzahlenComments;
    }
}
