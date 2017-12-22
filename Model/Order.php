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
 * Script : Order.php
 *
 */
namespace oe\novalnet\Model;

use oe\novalnet\Classes\NovalnetUtil;

class Order extends Order_parent
{
    /**
     * Novalnet payments
     *
     * @var array
     */
    protected $_aNovalnetPayments = array( 'novalnetcreditcard', 'novalnetsepa', 'novalnetinvoice', 'novalnetprepayment', 'novalnetonlinetransfer', 'novalnetpaypal', 'novalnetideal', 'novalneteps', 'novalnetgiropay', 'novalnetprzelewy24','novalnetbarzahlen' );

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
        $this->sNovalnetPaidDate = '0000-00-00 00:00:00';  // set default value for the paid date of the order for novalnet transaction
        // Checks the current payment method is not a Novalnet payment. If yes then skips the execution of this function
        if (!in_array($this->sCurrentPayment, $this->_aNovalnetPayments)) {
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
            oxNew('oxCounter')->update($this->_getCounterIdent(), $this->oxorder__oxordernr->value);
        }

        // logs transaction details in novalnet tables
        if (!$blRecalculatingOrder) {
            $this->oNovalnetUtil = oxNew(NovalnetUtil::class);
            $iOrderNo            = $this->oxorder__oxordernr->value;
            $this->_logNovalnetTransaction($oBasket);
            $this->_updateNovalnetComments();
            $this->_sendNovalnetPostbackCall(); // to send order number in post back call
            $this->oNovalnetUtil->clearNovalnetSession();
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
     */
    private function _logNovalnetTransaction($oBasket)
    {
        $this->oDb              = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $sProcessKey            = '';
        $sMaskedDetails         = '';
        $sZeroTrxnDetails       = NULL;
        $sZeroTrxnReference     = NULL;
        $blZeroAmountBooking    = '0';
        $blReferenceTransaction = '0';
        $iOrderNo               = $this->oxorder__oxordernr->value;
        $oBasket = serialize($oBasket);
        $aRequest  = $this->oNovalnetSession->getVariable('aNovalnetGatewayRequest');
        $aResponse = $this->oNovalnetSession->getVariable('aNovalnetGatewayResponse');
        $this->aNovalnetData = array_merge($aRequest, $aResponse);

        $this->aNovalnetData['test_mode'] = $aRequest['test_mode'] == '1' ? $aRequest['test_mode'] : $aResponse['test_mode'];
        $sSubsId = !empty($this->aNovalnetData['subs_id']) ? $this->aNovalnetData['subs_id'] : '';

        // checks the current payment is credit card or direct debit sepa, Guaranteed direct debit sepa, Paypal
        if (in_array($this->aNovalnetData['key'], array( '6', '34', '37', '40' ))) {

            // checks the shopping type is zero amount booking - if yes need to save the transaction request
            if ($this->oNovalnetUtil->getNovalnetConfigValue('iShopType' . $this->sCurrentPayment) == '2' && $this->aNovalnetData['amount'] == 0) {
                if ($this->aNovalnetData['key'] == '6') {
                    unset($aRequest['unique_id'], $aRequest['pan_hash'], $aRequest['nn_it'], $aRequest['cc_3d']);
                } elseif (in_array($this->aNovalnetData['key'], array( '37', '40' ))) {
                    $aRequest['sepa_due_date'] = $this->oNovalnetUtil->getNovalnetConfigValue('iDueDatenovalnetsepa');
                    unset($aRequest['pin_by_callback'], $aRequest['pin_by_sms']);
                }
                unset($aRequest['on_hold'], $aRequest['create_payment_ref']);
                $sZeroTrxnDetails    = serialize($aRequest);
                $sZeroTrxnReference  = $this->aNovalnetData['tid'];
                $blZeroAmountBooking = '1';
            }
            if (!empty($this->aNovalnetData['create_payment_ref'])) {
                if ($this->aNovalnetData['key'] == '6') {
                    $sMaskedDetails = serialize( array( 'cc_type'      => $this->aNovalnetData['cc_card_type'],
                                                        'cc_holder'    => $this->aNovalnetData['cc_holder'],
                                                        'cc_no'        => $this->aNovalnetData['cc_no'],
                                                        'cc_exp_month' => $this->aNovalnetData['cc_exp_month'],
                                                        'cc_exp_year'  => $this->aNovalnetData['cc_exp_year']
                                                      )
                                               );
                } elseif ($this->aNovalnetData['key'] == '34') {
                    $sMaskedDetails = serialize(array( 'paypal_transaction_id' => $this->aNovalnetData['paypal_transaction_id']));
                } else {
                    $sMaskedDetails = serialize( array( 'bankaccount_holder' => html_entity_decode($this->aNovalnetData['bankaccount_holder']),
                                                        'iban'               => $this->aNovalnetData['iban'],
                                                        'bic'                => $this->aNovalnetData['bic'],
                                                      )
                                               );
                }
            }
            if (in_array($this->aNovalnetData['key'], array( '37', '40' ))) {
                $sProcessKey = !empty($this->aNovalnetData['sepa_hash']) ? $this->aNovalnetData['sepa_hash'] : '';
            }

            $blReferenceTransaction = (!empty($this->aNovalnetData['payment_ref'])) ? '1' : '0';
        }

        if ((in_array($this->aNovalnetData['key'], array( '6', '27', '37', '40', '41','59' )) && !isset($this->aNovalnetData['cc_3d'])) || ($this->aNovalnetData['key'] == '34' && isset($aRequest['payment_ref']))) {
            $this->aNovalnetData['amount'] = $this->aNovalnetData['amount'] * 100;
        }
        // logs the transaction credentials, status and amount details
        $this->oDb->execute('INSERT INTO novalnet_transaction_detail ( VENDOR_ID, PRODUCT_ID, AUTH_CODE, TARIFF_ID, TID, ORDER_NO, SUBS_ID, PAYMENT_ID, PAYMENT_TYPE, AMOUNT, CURRENCY, STATUS, GATEWAY_STATUS, TEST_MODE, CUSTOMER_ID, ORDER_DATE, TOTAL_AMOUNT, PROCESS_KEY, MASKED_DETAILS, REFERENCE_TRANSACTION, ZERO_TRXNDETAILS, ZERO_TRXNREFERENCE, ZERO_TRANSACTION, NNBASKET ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )', array( $this->aNovalnetData['vendor'], $this->aNovalnetData['product'], $this->aNovalnetData['auth_code'], $this->aNovalnetData['tariff'], $this->aNovalnetData['tid'], $iOrderNo, $sSubsId, $this->aNovalnetData['key'], $this->aNovalnetData['payment_type'], $this->aNovalnetData['amount'], $this->aNovalnetData['currency'], $this->aNovalnetData['status'], $this->aNovalnetData['tid_status'], $this->aNovalnetData['test_mode'], $this->aNovalnetData['customer_no'], date('Y-m-d H:i:s'), $this->aNovalnetData['amount'], $sProcessKey, $sMaskedDetails, $blReferenceTransaction, $sZeroTrxnDetails, $sZeroTrxnReference, $blZeroAmountBooking, $oBasket ));

        // check current payment is invoice or prepayment or guaranteed invoice
        if (in_array($this->aNovalnetData['key'], array( '27', '41' ))) {
            $this->sInvoiceRef = 'BNR-' . $this->aNovalnetData['product'] . '-' . $iOrderNo;
            $aInvPreReference  = array(
                                        'payment_ref1' => $this->oNovalnetUtil->getNovalnetConfigValue('blRefOne' . $this->sCurrentPayment),
                                        'payment_ref2' => $this->oNovalnetUtil->getNovalnetConfigValue('blRefTwo' . $this->sCurrentPayment),
                                        'payment_ref3' => $this->oNovalnetUtil->getNovalnetConfigValue('blRefThree' . $this->sCurrentPayment)
                                      );
            $this->aNovalnetData = array_merge($this->aNovalnetData, $aInvPreReference);
            $sInvPreReference    = serialize($aInvPreReference);


            $this->oDb->execute('INSERT INTO novalnet_preinvoice_transaction_detail ( ORDER_NO, TID, TEST_MODE, ACCOUNT_HOLDER, BANK_IBAN, BANK_BIC, BANK_NAME, BANK_CITY, AMOUNT, CURRENCY, INVOICE_REF, DUE_DATE, PAYMENT_REF, ORDER_DATE ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )', array( $iOrderNo, $this->aNovalnetData['tid'], $this->aNovalnetData['test_mode'], $this->aNovalnetData['invoice_account_holder'], $this->aNovalnetData['invoice_iban'], $this->aNovalnetData['invoice_bic'], $this->aNovalnetData['invoice_bankname'], $this->aNovalnetData['invoice_bankplace'], $this->aNovalnetData['amount'], $this->aNovalnetData['currency'], $this->sInvoiceRef, $this->aNovalnetData['due_date'], $sInvPreReference, date('Y-m-d H:i:s') ));
        }
        if ($this->aNovalnetData['key'] == '59') {
            $aStores = $this->oNovalnetUtil->getBarzahlenComments($this->aNovalnetData,true);
            $this->oDb->execute('INSERT INTO novalnet_preinvoice_transaction_detail ( ORDER_NO, TID, TEST_MODE, AMOUNT, CURRENCY, DUE_DATE,PAYMENT_REF,ORDER_DATE ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ? )', array( $iOrderNo, $this->aNovalnetData['tid'], $this->aNovalnetData['test_mode'], $this->aNovalnetData['amount'], $this->aNovalnetData['currency'], $this->aNovalnetData['cashpayment_due_date'], serialize($aStores), date('Y-m-d H:i:s') ));
        }

        // logs the subscription details for subscription orders
        if (!empty($sSubsId)) {
            $this->oDb->execute('INSERT INTO novalnet_subscription_detail ( ORDER_NO, SUBS_ID, TID, SIGNUP_DATE ) VALUES ( ?, ?, ?, ?)', array($iOrderNo, $sSubsId, $this->aNovalnetData['tid'], date('Y-m-d H:i:s')));
        }

        // logs the transaction details in callback table
        if (!in_array($this->aNovalnetData['key'], array( '27', '59' )) && $this->aNovalnetData['status'] == 100 && $this->aNovalnetData['tid_status'] != '86') {
            if ($this->aNovalnetData['tid_status'] != '85') // verifying paypal onhold status
                $this->sNovalnetPaidDate = date('Y-m-d H:i:s'); // set the paid date of the order for novalnet paid transaction

            $this->oDb->execute('INSERT INTO novalnet_callback_history ( PAYMENT_TYPE, STATUS, ORDER_NO, AMOUNT, CURRENCY, ORG_TID, PRODUCT_ID, CALLBACK_DATE ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ? )', array( $this->aNovalnetData['payment_type'], $this->aNovalnetData['status'], $iOrderNo, $this->aNovalnetData['amount'], $this->aNovalnetData['currency'], $this->aNovalnetData['tid'], $this->aNovalnetData['product'], date('Y-m-d H:i:s') ));
        }

        // logs the affiliate orders in affiliate table
        if ($this->oNovalnetSession->getVariable('nn_aff_id')) {
            $this->oDb->execute('INSERT INTO novalnet_aff_user_detail ( AFF_ID, CUSTOMER_ID, AFF_ORDER_NO) VALUES ( ?, ?, ?)', array($this->oNovalnetSession->getVariable('nn_aff_id'), $this->aNovalnetData['customer_no'], $iOrderNo));
        }

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
        if ($this->oNovalnetUtil->getNovalnetConfigValue('blTestModeMail') && $sRequestTestMode == '0' && $sResponseTestMode == '1') {
            // send eMail
            $oMail = oxNew(\OxidEsales\Eshop\Core\Email::class);
            $oShop         = $oMail->getShop();
            $sEmailSubject = $this->oNovalnetUtil->oLang->translateString('NOVALNET_TEST_MODE_NOTIFICATION_SUBJECT');
            $sMessage      = sprintf($this->oNovalnetUtil->oLang->translateString('NOVALNET_TEST_MODE_NOTIFICATION_MESSAGE'), $this->oxorder__oxordernr->value);
            $sEmailAddress   = trim($oShop->oxshops__oxowneremail->value);
            // validates 'to' address
          if (oxNew(\OxidEsales\Eshop\Core\MailValidator::class)->isValidEmail($sEmailAddress)) {
                $oMail->setRecipient($sEmailAddress);
                $oMail->setFrom($sEmailAddress);
            }

            $oMail->setSubject($sEmailSubject);
            $oMail->setBody( $sMessage );
            $oMail->send();
        }
    }

    /**
     * Updates Novalnet comments for the order in shop
     *
     */
    private function _updateNovalnetComments()
    {
        $sNovalnetComments = '';
        if (in_array($this->aNovalnetData['key'], array('40', '41'))) {
            $sNovalnetComments .= $this->oNovalnetUtil->oLang->translateString('NOVALNET_PAYMENT_GURANTEE_COMMENTS');
        }

        $sNovalnetComments  .= $this->oNovalnetUtil->oLang->translateString('NOVALNET_TRANSACTION_DETAILS');
        $sNovalnetComments .= $this->oNovalnetUtil->oLang->translateString('NOVALNET_TRANSACTION_ID') . $this->aNovalnetData['tid'];
        if (!empty($this->aNovalnetData['test_mode'])) {
            $sNovalnetComments .= $this->oNovalnetUtil->oLang->translateString('NOVALNET_TEST_ORDER');
        }

        if (in_array($this->aNovalnetData['key'], array('27', '41'))) {
            $this->aNovalnetData['invoice_ref'] = $this->sInvoiceRef;
            $this->aNovalnetData['order_no']    = $this->oxorder__oxordernr->value;
            $sNovalnetInvoiceComments = $this->oNovalnetUtil->getInvoiceComments($this->aNovalnetData);
            $sNovalnetComments       .= $sNovalnetInvoiceComments;
        }
        if ($this->aNovalnetData['key'] =='59') {
            $sNovalnetComments       .= $this->oNovalnetUtil->getBarzahlenComments($this->aNovalnetData);
        }

        $sUpdateSQL = 'UPDATE oxorder SET OXPAID = "' . $this->sNovalnetPaidDate . '", NOVALNETCOMMENTS = "' . $sNovalnetComments . '" WHERE OXORDERNR ="' . $this->oxorder__oxordernr->value . '"';

        $this->oDb->execute($sUpdateSQL);
        $this->oxorder__oxpaid           = new \OxidEsales\Eshop\Core\Field($sNovalnetPaidDate);
        $this->oxorder__novalnetcomments = new \OxidEsales\Eshop\Core\Field($sNovalnetComments);
    }

    /**
     * Sends the postback call to the Novalnet server.
     *
     */
    private function _sendNovalnetPostbackCall()
    {
        $aPostBackParams = array( 'vendor'    => $this->aNovalnetData['vendor'],
                                  'product'   => $this->aNovalnetData['product'],
                                  'tariff'    => $this->aNovalnetData['tariff'],
                                  'auth_code' => $this->aNovalnetData['auth_code'],
                                  'key'       => $this->aNovalnetData['key'],
                                  'status'    => 100,
                                  'tid'       => $this->aNovalnetData['tid'],
                                  'order_no'  => $this->oxorder__oxordernr->value,
                                  'remote_ip' => $this->oNovalnetUtil->getIpAddress()
                                );

        if (in_array($this->aNovalnetData['key'], array('27', '41')))
            $aPostBackParams['invoice_ref'] = $this->sInvoiceRef;

        $this->oNovalnetUtil->doCurlRequest($aPostBackParams, 'https://payport.novalnet.de/paygate.jsp');
    }
}
?>
