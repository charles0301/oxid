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

namespace oe\novalnet\Model;

use oe\novalnet\Classes\NovalnetUtil;

/**
 * Class Order.
 */
class Order extends Order_parent
{
    /*
     * Get Current Payment
     *
     * @var string
    */
    public  $sCurrentPayment;

    /*
     * Get Novalnet Paid Date
     *
     * @var string
    */
    protected $_sNovalnetPaidDate;

    /*
     * Get Novalnet Util Details
     *
     * @var object
    */
    protected $_oNovalnetUtil;

    /*
     * Get Novalnet Date
     *
     * @var array
    */
    protected $_aNovalnetData;

    /*
     * Get Novalnet Reference
     *
     * @var string
    */
    protected $_sInvoiceRef;

    /*
     * Get Novalnet Reference
     *
     * @var string
    */
    protected $oNovalnetSession;

    /**
     * Finalizes the order in shop
     *
     * @param object  $oBasket
     * @param object  $oUser
     * @param boolean $blRecalculatingOrder
     *
     * @return boolean
     */
    public function finalizeOrder(\OxidEsales\Eshop\Application\Model\Basket $oBasket, $oUser, $blRecalculatingOrder = false)
    {
       $this->sCurrentPayment = $oBasket->getPaymentId(); // to get the current payment

        $this->_sNovalnetPaidDate = '0000-00-00 00:00:00';  // set default value for the paid date of the order for novalnet transaction

        // Checks the current payment method is not a Novalnet payment. If yes then skips the execution of this function
        if (!preg_match("/novalnet/i", $this->sCurrentPayment)) {
            return parent::finalizeOrder($oBasket, $oUser, $blRecalculatingOrder);
        }

        $this->oNovalnetSession = \OxidEsales\Eshop\Core\Registry::getSession();

        $sGetChallenge = $this->oNovalnetSession->getVariable('sess_challenge');

        if ($this->_checkOrderExist($sGetChallenge)) {
            \OxidEsales\Eshop\Core\Registry::getUtils()->logger('BLOCKER');
            return self::ORDER_STATE_ORDEREXISTS;
        }

        if (!$blRecalculatingOrder) {
            $this->setId($sGetChallenge);

            if ($iOrderState = $this->validateOrder($oBasket, $oUser)) {

                return $iOrderState;
            }
        }

        $this->_setUser($oUser);

        $this->_loadFromBasket($oBasket);

        $oUserPayment = $this->_setPayment($oBasket->getPaymentId());

        if (!$blRecalculatingOrder) {
            $this->_setFolder();
        }

        $this->_setOrderStatus('NOT_FINISHED');

        $this->save();

        if (!$blRecalculatingOrder) {
            $blRet = $this->_executePayment($oBasket, $oUserPayment);
            if ($blRet !== true) {
                return $blRet;
            }
        }

        $this->oNovalnetSession->deleteVariable('ordrem');
        $this->oNovalnetSession->deleteVariable('stsprotection');

        if (!$this->oxorder__oxordernr->value) {
            $this->_setNumber();
        } else {
            oxNew(\OxidEsales\Eshop\Core\Counter::class)->update($this->_getCounterIdent(), $this->oxorder__oxordernr->value);
        }

        // logs transaction details in novalnet tables
        if (!$blRecalculatingOrder) {
            $this->_oNovalnetUtil = oxNew(NovalnetUtil::class);
            $iOrderNo            = $this->oxorder__oxordernr->value;
            $this->_logNovalnetTransaction($oBasket);
            $this->_updateNovalnetComments();
            $this->_sendNovalnetPostbackCall(); // to send order number in post back call
            $this->_oNovalnetUtil->clearNovalnetSession();
        }

        if (!$blRecalculatingOrder) {
            $this->_updateOrderDate();
        }

        $this->_setOrderStatus('OK');

        $oBasket->setOrderId($this->getId());

        $this->_updateWishlist($oBasket->getContents(), $oUser);

        $this->_updateNoticeList($oBasket->getContents(), $oUser);

        if (!$blRecalculatingOrder) {
            $this->_markVouchers($oBasket, $oUser);
        }

        if (!$blRecalculatingOrder) {
            $iRet = $this->_sendOrderByEmail($oUser, $oBasket, $oUserPayment);
        } else {
            $iRet = self::ORDER_STATE_OK;
        }

        return $iRet;
    }

    /**
     * Logs Novalnet transaction details into Novalnet tables in shop
     *
     * @param object $oBasket
     */
    private function _logNovalnetTransaction()
    {
        $this->oDb              = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $sMaskedDetails = '';
        $sZeroTrxnDetails       = $sZeroTrxnReference = NULL;
        $blZeroAmountBooking    = $blReferenceTransaction = '0';
        $iOrderNo               = $this->oxorder__oxordernr->value;
        $aRequest               = $this->oNovalnetSession->getVariable('aNovalnetGatewayRequest');
        $aResponse              = $this->oNovalnetSession->getVariable('aNovalnetGatewayResponse');

        // Delete the refillSepaiban session variable which was stored in novalnetutil file for failure transaction.
        $this->oNovalnetSession->deleteVariable('refillSepaiban');

        $this->_aNovalnetData   = array_merge($aRequest, $aResponse);

        $this->_aNovalnetData['test_mode'] = $aRequest['test_mode'] == '1' ? $aRequest['test_mode'] : $aResponse['test_mode'];

        // checks the current payment is credit card or direct debit sepa, Guaranteed direct debit sepa, Paypal
        if (in_array($this->_aNovalnetData['key'], ['6', '34', '37', '40'])) {

            // checks the shopping type is zero amount booking - if yes need to save the transaction request
            if ($this->_oNovalnetUtil->getNovalnetConfigValue('iShopType' . $this->sCurrentPayment) == '2' && $this->_aNovalnetData['amount'] == 0) {
                if ($this->_aNovalnetData['key'] == '6') {
                    unset($aRequest['unique_id'], $aRequest['pan_hash'], $aRequest['nn_it'], $aRequest['cc_3d']);
                } elseif (in_array($this->_aNovalnetData['key'], [ '37', '40' ])) {
                    $aRequest['sepa_due_date'] = $this->_oNovalnetUtil->getNovalnetConfigValue('iDueDatenovalnetsepa');
                    unset($aRequest['pin_by_callback'], $aRequest['pin_by_sms']);
                }
                unset($aRequest['on_hold'], $aRequest['create_payment_ref']);
                $sZeroTrxnDetails    = serialize($aRequest);
                $sZeroTrxnReference  = $this->_aNovalnetData['tid'];
                $blZeroAmountBooking = '1';
            }
            if (!empty($this->_aNovalnetData['create_payment_ref'])) {
                if ($this->_aNovalnetData['key'] == '6') {
                    $sMaskedDetails = serialize( [ 'cc_type'      => $this->_aNovalnetData['cc_card_type'],
                                                    'cc_holder'    => $this->_aNovalnetData['cc_holder'],
                                                    'cc_no'        => $this->_aNovalnetData['cc_no'],
                                                    'cc_exp_month' => $this->_aNovalnetData['cc_exp_month'],
                                                    'cc_exp_year'  => $this->_aNovalnetData['cc_exp_year']
                                                  ]);
                } elseif ($this->_aNovalnetData['key'] == '34') {
                    $sMaskedDetails = serialize(['paypal_transaction_id' => $this->_aNovalnetData['paypal_transaction_id'],
                                                  'tid'              => $this->_aNovalnetData['tid']
                                                ]);
                } else {
                    $sMaskedDetails = serialize( ['bankaccount_holder' => html_entity_decode($this->_aNovalnetData['bankaccount_holder']),
                                                  'iban'         => $this->_aNovalnetData['iban'],
                                                  'tid'          => $this->aNovalnetData['tid']
                                                 ]);
                }
            }

            $blReferenceTransaction = (!empty($this->_aNovalnetData['payment_ref'])) ? '1' : '0';
        }
         if ((in_array($this->_aNovalnetData['key'], [ '6', '27', '37', '40', '41','59' ]) && !isset($this->_aNovalnetData['cc_3d']) && $this->_oNovalnetUtil->getNovalnetConfigValue('blCC3DFraudActive') != '1') || ($this->_aNovalnetData['key'] == '34' && isset($aRequest['payment_ref'])) && empty($bCreditcardflag))
                $this->_aNovalnetData['amount'] = $this->_aNovalnetData['amount'] * 100;

          $aVendorData = $aIvoiceBankData = $aBarzahlenData = [];

          $aVendorData = ['vendor' => $this->_aNovalnetData['vendor'],
                            'product' => $this->_aNovalnetData['product'],
                            'auth_code' => $this->_aNovalnetData['auth_code'],
                            'tariff' => $this->_aNovalnetData['tariff'],
                            'test_mode' => $this->_aNovalnetData['test_mode']
                        ];
        // check current payment is invoice or prepayment or guaranteed invoice
        if (in_array($this->_aNovalnetData['key'], ['27', '41' ])) {
            $this->_sInvoiceRef = 'BNR-' . $this->_aNovalnetData['product'] . '-' . $iOrderNo;
            $aIvoiceBankData = [ 'invoice_account_holder'      => $this->_aNovalnetData['invoice_account_holder'],
                                            'invoice_iban'    => $this->_aNovalnetData['invoice_iban'],
                                            'invoice_bic'        => $this->_aNovalnetData['invoice_bic'],
                                            'invoice_bankname' => $this->_aNovalnetData['invoice_bankname'],
                                            'invoice_bankplace'  => $this->_aNovalnetData['invoice_bankplace'],
                                            'due_date'  => $this->_aNovalnetData['due_date'],
                                            'invoice_ref' => $this->_sInvoiceRef,
                                            'tid' => $this->_aNovalnetData['tid'],
                                            'amount' => $this->_aNovalnetData['amount']
                                          ];
        }
        if ($this->_aNovalnetData['key'] == '59') {
            $aStores['nearest_store'] = $this->_oNovalnetUtil->getBarzahlenComments($this->_aNovalnetData,true);
            $aStores['cp_checkout_token'] = $this->_aNovalnetData['cp_checkout_token'] .'|'. $this->_aNovalnetData['test_mode'];
            $aStores['due_date'] = $this->_aNovalnetData['cashpayment_due_date'];
            $aBarzahlenData =  $aStores;
        }
        $aAdditionalData = array_merge($aVendorData, $aIvoiceBankData, $aBarzahlenData );

        // logs the transaction credentials, status and amount details
        $this->oDb->execute('INSERT INTO novalnet_transaction_detail ( TID, ORDER_NO, PAYMENT_ID, PAYMENT_TYPE, AMOUNT, GATEWAY_STATUS, CUSTOMER_ID, ORDER_DATE, TOTAL_AMOUNT, MASKED_DETAILS, REFERENCE_TRANSACTION, ZERO_TRXNDETAILS, ZERO_TRXNREFERENCE, ZERO_TRANSACTION, ADDITIONAL_DATA) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$this->_aNovalnetData['tid'], $iOrderNo, $this->_aNovalnetData['key'], $this->_aNovalnetData['payment_type'], $this->_aNovalnetData['amount'], $this->_aNovalnetData['tid_status'], $this->_aNovalnetData['customer_no'], date('Y-m-d H:i:s'), $this->_aNovalnetData['amount'], $sMaskedDetails, $blReferenceTransaction, $sZeroTrxnDetails, $sZeroTrxnReference, $blZeroAmountBooking, serialize($aAdditionalData)]);

        // logs the transaction details in callback table
        if (!in_array($this->_aNovalnetData['key'], [ '27', '59', '41', '40']) && $this->_aNovalnetData['status'] == 100 && $this->_aNovalnetData['tid_status'] != '86') {
            if ($this->_aNovalnetData['tid_status'] != '85') // verifying paypal onhold status
                $this->_sNovalnetPaidDate = date('Y-m-d H:i:s'); // set the paid date of the order for novalnet paid transaction

            $this->oDb->execute('INSERT INTO novalnet_callback_history ( ORDER_NO, AMOUNT, ORG_TID, CALLBACK_DATE ) VALUES ( ?, ?, ?, ?)', [ $iOrderNo, $this->_aNovalnetData['amount'], $this->_aNovalnetData['tid'], date('Y-m-d H:i:s') ]);
        }

        // logs the affiliate orders in affiliate table
        if ($this->oNovalnetSession->getVariable('nn_aff_id'))
            $this->oDb->execute('INSERT INTO novalnet_aff_user_detail ( AFF_ID, CUSTOMER_ID, AFF_ORDER_NO) VALUES ( ?, ?, ?)', [$this->oNovalnetSession->getVariable('nn_aff_id'), $this->_aNovalnetData['customer_no'], $iOrderNo]);

        $this->_checkNovalnetTestMode($aRequest['test_mode'], $aResponse['test_mode']);
    }

    /**
     * Send test transaction notification mail
     *
     * @param string $sRequestTestMode
     * @param string $sResponseTestMode
     *
     */
    private function _checkNovalnetTestMode($sRequestTestMode, $sResponseTestMode)
    {
        if ($this->_oNovalnetUtil->getNovalnetConfigValue('blTestModeMail') && $sRequestTestMode == '0' && $sResponseTestMode == '1') {
            // send eMail
            $oMail = oxNew(\OxidEsales\Eshop\Core\Email::class);
            $oShop         = $oMail->getShop();
            $sEmailSubject = $this->_oNovalnetUtil->oLang->translateString('NOVALNET_TEST_MODE_NOTIFICATION_SUBJECT');
            $sMessage      = sprintf($this->_oNovalnetUtil->oLang->translateString('NOVALNET_TEST_MODE_NOTIFICATION_MESSAGE'), $this->oxorder__oxordernr->value);
            $sEmailAddress = trim($oShop->oxshops__oxowneremail->value);
            // validates email address
          if (oxNew(\OxidEsales\Eshop\Core\MailValidator::class)->isValidEmail($sEmailAddress)) {
                $oMail->setRecipient($sEmailAddress);
                $oMail->setFrom($sEmailAddress);
                $oMail->setSubject($sEmailSubject);
                $oMail->setBody( $sMessage );
                $oMail->send();
           }
        }
    }

    /**
     * Updates Novalnet comments for the order in shop
     *
     */
    private function _updateNovalnetComments()
    {
        $sNovalnetComments = '';
        if (in_array($this->_aNovalnetData['key'], ['40', '41'])) {
            $sNovalnetComments .= $this->_oNovalnetUtil->oLang->translateString('NOVALNET_PAYMENT_GUARANTEE_COMMENTS').'<br>';
            if ($this->_aNovalnetData['tid_status'] == '100') {
				$this->_sNovalnetPaidDate = date('Y-m-d H:i:s');
			}
        }

        $sNovalnetComments .= $this->_oNovalnetUtil->oLang->translateString('NOVALNET_TRANSACTION_DETAILS');

        $sNovalnetComments .= $this->_oNovalnetUtil->oLang->translateString('NOVALNET_TRANSACTION_ID') . $this->_aNovalnetData['tid'];

        if (!empty($this->_aNovalnetData['test_mode'])) {
            $sNovalnetComments .= $this->_oNovalnetUtil->oLang->translateString('NOVALNET_TEST_ORDER');
        }

        if ($this->_aNovalnetData['key'] == '41' && $this->_aNovalnetData['tid_status'] == 75) {
           $sNovalnetComments .= $this->_oNovalnetUtil->oLang->translateString('NOVALNET_GUARANTEE_TEXT');
        }

        if (in_array($this->_aNovalnetData['key'], ['27', '41'])) {
            $this->_aNovalnetData['invoice_ref'] = $this->_sInvoiceRef;
            $this->_aNovalnetData['order_no']    = $this->oxorder__oxordernr->value;

            if (!in_array($this->_aNovalnetData['tid_status'], array(75, 91))) {
                $sNovalnetComments .= $this->_oNovalnetUtil->getInvoiceComments($this->_aNovalnetData);
            }

        }
        if ($this->_aNovalnetData['key'] =='59') {
            $sNovalnetComments       .= $this->_oNovalnetUtil->getBarzahlenComments($this->_aNovalnetData);
        }

        $sUpdateSQL = 'UPDATE oxorder SET OXPAID = "' . $this->_sNovalnetPaidDate . '", NOVALNETCOMMENTS = "' . $sNovalnetComments . '" WHERE OXORDERNR ="' . $this->oxorder__oxordernr->value . '"';
        $this->oDb->execute($sUpdateSQL);
        $this->oxorder__oxpaid           = new \OxidEsales\Eshop\Core\Field($this->_sNovalnetPaidDate);
        $this->oxorder__novalnetcomments = new \OxidEsales\Eshop\Core\Field($sNovalnetComments);
    }

    /**
     * Sends the postback call to the Novalnet server.
     *
     */
    private function _sendNovalnetPostbackCall()
    {
        $aPostBackParams = ['vendor'    => $this->_aNovalnetData['vendor'],
                            'product'   => $this->_aNovalnetData['product'],
                            'tariff'    => $this->_aNovalnetData['tariff'],
                            'auth_code' => $this->_aNovalnetData['auth_code'],
                            'key'       => $this->_aNovalnetData['key'],
                            'status'    => 100,
                            'tid'       => $this->_aNovalnetData['tid'],
                            'order_no'  => $this->oxorder__oxordernr->value,
                            'remote_ip' => $this->_oNovalnetUtil->getIpAddress()
                           ];

        if (in_array($this->_aNovalnetData['key'], ['27', '41']))
            $aPostBackParams['invoice_ref'] = $this->_sInvoiceRef;

        $this->_oNovalnetUtil->doCurlRequest($aPostBackParams, $this->_oNovalnetUtil->sPaygateUrl);
    }
}
?>
