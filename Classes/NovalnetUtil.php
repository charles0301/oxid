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

namespace oe\novalnet\Classes;

/**
 * Class NovalnetUtil.
 */
class NovalnetUtil {

    /*
     * Get Config value
     */
    public $oConfig;

    /*
     * Get Lang value
     */
    public $oLang;

    /*
     * Get Session value
     */
    public $oSession;

    /**
     * Novalnet module configuration
     *
     * @var array
     */
    public $aNovalnetConfig;

    public function __construct()
    {
        $this->oConfig         = \OxidEsales\Eshop\Core\Registry::getConfig();
        $this->oLang           = \OxidEsales\Eshop\Core\Registry::getLang();
        $this->oSession        = \OxidEsales\Eshop\Core\Registry::getSession();
        $this->aNovalnetConfig = $this->oConfig->getShopConfVar('aNovalnetConfig', '', 'novalnet');
    }

    /**
     * Performs CURL request
     *
     * @param mixed   $mxRequest
     * @param string  $sUrl
     * @param boolean $blBuildQuery
     *
     * @return mixed
     */
    public function doCurlRequest($mxRequest, $sUrl, $blBuildQuery = true)
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
        $oCurl->setOption('CURLOPT_TIMEOUT', (!empty($iCurlTimeout) ? $iCurlTimeout : 240));
        if ($sProxy) {
            $oCurl->setOption('CURLOPT_PROXY', $sProxy);
        }

        $mxData = $oCurl->execute();

        if ($blBuildQuery)
            parse_str($mxData, $mxData);

        return $mxData;
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
            $sIP = $oUtilsServer->getRemoteAddress();
            return filter_var($sIP, FILTER_VALIDATE_IP);
        } else {
            $sIP = $_SERVER['SERVER_ADDR'];
            return (filter_var($sIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) ? '127.0.0.1' : $sIP;
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

        $sInvoiceComments = $this->oLang->translateString('NOVALNET_INVOICE_COMMENTS_TITLE');
        if (!empty($aInvoiceDetails['due_date']))
            $sInvoiceComments .= $this->oLang->translateString('NOVALNET_DUE_DATE') . date('d.m.Y', strtotime($aInvoiceDetails['due_date']));
        $sInvoiceComments .= $this->oLang->translateString('NOVALNET_ACCOUNT') . $aInvoiceDetails['invoice_account_holder'];
        $sInvoiceComments .= '<br>IBAN: ' . $aInvoiceDetails['invoice_iban'];
        $sInvoiceComments .= '<br>BIC: '  . $aInvoiceDetails['invoice_bic'];
        $sInvoiceComments .= '<br>Bank: ' . $aInvoiceDetails['invoice_bankname'] . ' ' . $aInvoiceDetails['invoice_bankplace'];
        $sInvoiceComments .= $this->oLang->translateString('NOVALNET_AMOUNT') . $sFormattedAmount;

        $iReferences[1] = isset($aInvoiceDetails['payment_ref1']) ? $aInvoiceDetails['payment_ref1'] : '';
        $iReferences[2] = isset($aInvoiceDetails['payment_ref2']) ? $aInvoiceDetails['payment_ref2'] : '';
        $iReferences[3] = isset($aInvoiceDetails['payment_ref3']) ? $aInvoiceDetails['payment_ref3'] : '';
        $i = 1;
        $aCountReferenece = array_count_values($iReferences);

        $sInvoiceComments .= (($aCountReferenece['1'] > 1) ? $this->oLang->translateString('NOVALNET_INVOICE_MULTI_REF_DESCRIPTION') : $this->oLang->translateString('NOVALNET_INVOICE_SINGLE_REF_DESCRIPTION'));
        foreach ($iReferences as $iKey => $blValue) {
            if ($iReferences[$iKey] == 1) {
                $sInvoiceComments .= ($aCountReferenece['1'] == 1) ? $this->oLang->translateString('NOVALNET_INVOICE_SINGLE_REFERENCE') : sprintf($this->oLang->translateString('NOVALNET_INVOICE_MULTI_REFERENCE'), $i++);

                $sInvoiceComments .= ($iKey == 1) ? $aInvoiceDetails['invoice_ref'] : ($iKey == 2 ? 'TID '. $aInvoiceDetails['tid'] : $this->oLang->translateString('NOVALNET_ORDER_NO') . $aInvoiceDetails['order_no']);
            }
        }

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
        return $this->oConfig->getSslShopUrl() . 'index.php?cl=payment&payerror=-1&payerrortext=' . urlencode($this->_setUTFEncode($sMessage));
    }

    /**
     * Clears Novalnet session
     */
    public function clearNovalnetSession()
    {
        $aNovalnetSessions = ['sNovalnetAccessKey','aNovalnetGatewayRequest', 'aNovalnetGatewayResponse',
                                    'anovalnetdynvalue', 'nn_aff_id', 'dynvalue', 'blOneClicknovalnetsepa', 'blGuaranteeEnablednovalnetsepa',                               'blGuaranteeEnablednovalnetinvoice', 'blGuaranteeForceDisablednovalnetsepa', 'blGuaranteeForceDisablednovalnetinvoice',
                                    'blCallbackEnablednovalnetsepa', 'sCallbackTidnovalnetsepa', 'dCallbackAmountnovalnetsepa',
                                    'sCallbackTidnovalnetinvoice','dCallbackAmountnovalnetinvoice'];

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
                if(stripos($sKey,'nearest_store')!==false){
                    $aBarzalan[$sKey] = $sValue;
                }
            }

            return $aBarzalan;
        }

        foreach ($aBarzahlenDetails as $sKey => $sValue)
        {
            if (strpos($sKey, 'nearest_store_street') !== false)
            {
                $iStoreCounts++;
            }
        }
        $oCountry = oxNew(\OxidEsales\Eshop\Application\Model\Country::class);
        $sBarzahlenComments = $this->oLang->translateString('NOVALNET_BARZAHLEN_DUE_DATE') . date('d.m.Y', strtotime($aBarzahlenDetails['cashpayment_due_date']));
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

     /**
     * Gets Novalnet subscription cancellation reason
     *
     * @return array
     */
    public function getNovalnetSubscriptionReason()
    {
        return [$this->oLang->translateString('NOVALNET_SUBSCRIPTION_CANCEL_REASON_1'),
              $this->oLang->translateString('NOVALNET_SUBSCRIPTION_CANCEL_REASON_2'),
              $this->oLang->translateString('NOVALNET_SUBSCRIPTION_CANCEL_REASON_3'),
              $this->oLang->translateString('NOVALNET_SUBSCRIPTION_CANCEL_REASON_4'),
              $this->oLang->translateString('NOVALNET_SUBSCRIPTION_CANCEL_REASON_5'),
              $this->oLang->translateString('NOVALNET_SUBSCRIPTION_CANCEL_REASON_6'),
              $this->oLang->translateString('NOVALNET_SUBSCRIPTION_CANCEL_REASON_7'),
              $this->oLang->translateString('NOVALNET_SUBSCRIPTION_CANCEL_REASON_8'),
              $this->oLang->translateString('NOVALNET_SUBSCRIPTION_CANCEL_REASON_9'),
              $this->oLang->translateString('NOVALNET_SUBSCRIPTION_CANCEL_REASON_10'),
              $this->oLang->translateString('NOVALNET_SUBSCRIPTION_CANCEL_REASON_11')
            ];
    }

}
