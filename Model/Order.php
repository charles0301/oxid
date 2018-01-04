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
 * Script : Order.php
 *
 */

namespace oe\novalnet\Model;

use oe\novalnet\Classes\NovalnetUtil;

/**
 * Class Order.
 */
class Order extends Order_parent
{
    public $sCurrentPayment;
    protected $_sNovalnetPaidDate;

    /**
     * Session object
     *
     * @var object
     */
    protected $_oNovalnetSession;
    protected $_oNovalnetUtil;
    protected $_aNovalnetData;
    protected $_sInvoiceRef;

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

        $this->_oNovalnetSession = \OxidEsales\Eshop\Core\Registry::getSession();

        $sGetChallenge = $this->_oNovalnetSession->getVariable('sess_challenge');

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

        $this->_oNovalnetSession->deleteVariable('ordrem');
        $this->_oNovalnetSession->deleteVariable('stsprotection');

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
     */
    private function _logNovalnetTransaction($oBasket)
    {
        $this->oDb              = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $sProcessKey = $sMaskedDetails = '';
        $sZeroTrxnDetails = $sZeroTrxnReference = NULL;
        $blZeroAmountBooking = $blReferenceTransaction = '0';
        $iOrderNo               = $this->oxorder__oxordernr->value;
        $oBasket = serialize($oBasket);
        $aRequest  = $this->_oNovalnetSession->getVariable('aNovalnetGatewayRequest');
        $aResponse = $this->_oNovalnetSession->getVariable('aNovalnetGatewayResponse');
        $this->_aNovalnetData = array_merge($aRequest, $aResponse);

        $this->_aNovalnetData['test_mode'] = $aRequest['test_mode'] == '1' ? $aRequest['test_mode'] : $aResponse['test_mode'];
        $sSubsId = !empty($this->_aNovalnetData['subs_id']) ? $this->_aNovalnetData['subs_id'] : '';

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
                                                      ]
                                               );
                } elseif ($this->_aNovalnetData['key'] == '34') {
                    $sMaskedDetails = serialize(['paypal_transaction_id' => $this->_aNovalnetData['paypal_transaction_id']]);
                } else {
                    $sMaskedDetails = serialize( [ 'bankaccount_holder' => html_entity_decode($this->_aNovalnetData['bankaccount_holder']),
                                                        'iban'               => $this->_aNovalnetData['iban'],
                                                        'bic'                => $this->_aNovalnetData['bic'],
                                                      ]
                                               );
                }
            }
            if (in_array($this->_aNovalnetData['key'], ['37', '40' ])) {
                $sProcessKey = !empty($this->_aNovalnetData['sepa_hash']) ? $this->_aNovalnetData['sepa_hash'] : '';
            }

            $blReferenceTransaction = (!empty($this->_aNovalnetData['payment_ref'])) ? '1' : '0';
        }

        if ((in_array($this->_aNovalnetData['key'], [ '6', '27', '37', '40', '41','59' ]) && !isset($this->_aNovalnetData['cc_3d'])) || ($this->_aNovalnetData['key'] == '34' && isset($aRequest['payment_ref']))) {
            $this->_aNovalnetData['amount'] = $this->_aNovalnetData['amount'] * 100;
        }
        // logs the transaction credentials, status and amount details
        $this->oDb->execute('INSERT INTO novalnet_transaction_detail ( VENDOR_ID, PRODUCT_ID, AUTH_CODE, TARIFF_ID, TID, ORDER_NO, SUBS_ID, PAYMENT_ID, PAYMENT_TYPE, AMOUNT, CURRENCY, STATUS, GATEWAY_STATUS, TEST_MODE, CUSTOMER_ID, ORDER_DATE, TOTAL_AMOUNT, PROCESS_KEY, MASKED_DETAILS, REFERENCE_TRANSACTION, ZERO_TRXNDETAILS, ZERO_TRXNREFERENCE, ZERO_TRANSACTION, NNBASKET ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )', [$this->_aNovalnetData['vendor'], $this->_aNovalnetData['product'], $this->_aNovalnetData['auth_code'], $this->_aNovalnetData['tariff'], $this->_aNovalnetData['tid'], $iOrderNo, $sSubsId, $this->_aNovalnetData['key'], $this->_aNovalnetData['payment_type'], $this->_aNovalnetData['amount'], $this->_aNovalnetData['currency'], $this->_aNovalnetData['status'], $this->_aNovalnetData['tid_status'], $this->_aNovalnetData['test_mode'], $this->_aNovalnetData['customer_no'], date('Y-m-d H:i:s'), $this->_aNovalnetData['amount'], $sProcessKey, $sMaskedDetails, $blReferenceTransaction, $sZeroTrxnDetails, $sZeroTrxnReference, $blZeroAmountBooking, $oBasket ]);

        // check current payment is invoice or prepayment or guaranteed invoice
        if (in_array($this->_aNovalnetData['key'], ['27', '41' ])) {
            $this->_sInvoiceRef = 'BNR-' . $this->_aNovalnetData['product'] . '-' . $iOrderNo;
            $aInvPreReference  = [
                                        'payment_ref1' => $this->_oNovalnetUtil->getNovalnetConfigValue('blRefOne' . $this->sCurrentPayment),
                                        'payment_ref2' => $this->_oNovalnetUtil->getNovalnetConfigValue('blRefTwo' . $this->sCurrentPayment),
                                        'payment_ref3' => $this->_oNovalnetUtil->getNovalnetConfigValue('blRefThree' . $this->sCurrentPayment)
                                      ];
            $this->_aNovalnetData = array_merge($this->_aNovalnetData, $aInvPreReference);
            $sInvPreReference    = serialize($aInvPreReference);


            $this->oDb->execute('INSERT INTO novalnet_preinvoice_transaction_detail ( ORDER_NO, TID, TEST_MODE, ACCOUNT_HOLDER, BANK_IBAN, BANK_BIC, BANK_NAME, BANK_CITY, AMOUNT, CURRENCY, INVOICE_REF, DUE_DATE, PAYMENT_REF, ORDER_DATE ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )', [ $iOrderNo, $this->_aNovalnetData['tid'], $this->_aNovalnetData['test_mode'], $this->_aNovalnetData['invoice_account_holder'], $this->_aNovalnetData['invoice_iban'], $this->_aNovalnetData['invoice_bic'], $this->_aNovalnetData['invoice_bankname'], $this->_aNovalnetData['invoice_bankplace'], $this->_aNovalnetData['amount'], $this->_aNovalnetData['currency'], $this->_sInvoiceRef, $this->_aNovalnetData['due_date'], $sInvPreReference, date('Y-m-d H:i:s') ]);
        }
        if ($this->_aNovalnetData['key'] == '59') {
            $aStores = $this->_oNovalnetUtil->getBarzahlenComments($this->_aNovalnetData,true);
            $this->oDb->execute('INSERT INTO novalnet_preinvoice_transaction_detail ( ORDER_NO, TID, TEST_MODE, AMOUNT, CURRENCY, DUE_DATE,PAYMENT_REF,ORDER_DATE ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ? )', [ $iOrderNo, $this->_aNovalnetData['tid'], $this->_aNovalnetData['test_mode'], $this->_aNovalnetData['amount'], $this->_aNovalnetData['currency'], $this->_aNovalnetData['cashpayment_due_date'], serialize($aStores), date('Y-m-d H:i:s') ]);
        }

        // logs the subscription details for subscription orders
        if (!empty($sSubsId)) {
            $this->oDb->execute('INSERT INTO novalnet_subscription_detail ( ORDER_NO, SUBS_ID, TID, SIGNUP_DATE ) VALUES ( ?, ?, ?, ?)', [$iOrderNo, $sSubsId, $this->_aNovalnetData['tid'], date('Y-m-d H:i:s')]);
        }

        // logs the transaction details in callback table
        if (!in_array($this->_aNovalnetData['key'], [ '27', '59' ]) && $this->_aNovalnetData['status'] == 100 && $this->_aNovalnetData['tid_status'] != '86') {
            if ($this->_aNovalnetData['tid_status'] != '85') // verifying paypal onhold status
                $this->_sNovalnetPaidDate = date('Y-m-d H:i:s'); // set the paid date of the order for novalnet paid transaction

            $this->oDb->execute('INSERT INTO novalnet_callback_history ( PAYMENT_TYPE, STATUS, ORDER_NO, AMOUNT, CURRENCY, ORG_TID, PRODUCT_ID, CALLBACK_DATE ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ? )', [ $this->_aNovalnetData['payment_type'], $this->_aNovalnetData['status'], $iOrderNo, $this->_aNovalnetData['amount'], $this->_aNovalnetData['currency'], $this->_aNovalnetData['tid'], $this->_aNovalnetData['product'], date('Y-m-d H:i:s') ]);
        }

        // logs the affiliate orders in affiliate table
        if ($this->_oNovalnetSession->getVariable('nn_aff_id')) {
            $this->oDb->execute('INSERT INTO novalnet_aff_user_detail ( AFF_ID, CUSTOMER_ID, AFF_ORDER_NO) VALUES ( ?, ?, ?)', [$this->_oNovalnetSession->getVariable('nn_aff_id'), $this->_aNovalnetData['customer_no'], $iOrderNo]);
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
        if ($this->_oNovalnetUtil->getNovalnetConfigValue('blTestModeMail') && $sRequestTestMode == '0' && $sResponseTestMode == '1') {
            // send eMail
            $oMail = oxNew(\OxidEsales\Eshop\Core\Email::class);
            $oShop         = $oMail->getShop();
            $sEmailSubject = $this->_oNovalnetUtil->oLang->translateString('NOVALNET_TEST_MODE_NOTIFICATION_SUBJECT');
            $sMessage      = sprintf($this->_oNovalnetUtil->oLang->translateString('NOVALNET_TEST_MODE_NOTIFICATION_MESSAGE'), $this->oxorder__oxordernr->value);
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
        $sNovalnetComments = $this->_oNovalnetUtil->oLang->translateString('NOVALNET_TRANSACTION_DETAILS');
        if (in_array($this->_aNovalnetData['key'], ['40', '41'])) {
            $sNovalnetComments .= $this->_oNovalnetUtil->oLang->translateString('NOVALNET_PAYMENT_GURANTEE_COMMENTS');
        }
        $sNovalnetComments .= $this->_oNovalnetUtil->oLang->translateString('NOVALNET_TRANSACTION_ID') . $this->_aNovalnetData['tid'];
        if (!empty($this->_aNovalnetData['test_mode'])) {
            $sNovalnetComments .= $this->_oNovalnetUtil->oLang->translateString('NOVALNET_TEST_ORDER');
        }

        if (in_array($this->_aNovalnetData['key'], ['27', '41'])) {
            $this->_aNovalnetData['invoice_ref'] = $this->_sInvoiceRef;
            $this->_aNovalnetData['order_no']    = $this->oxorder__oxordernr->value;
            $sNovalnetInvoiceComments = $this->_oNovalnetUtil->getInvoiceComments($this->_aNovalnetData);
            $sNovalnetComments       .= $sNovalnetInvoiceComments;
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

        $this->_oNovalnetUtil->doCurlRequest($aPostBackParams, 'https://payport.novalnet.de/paygate.jsp');
    }
}
?>
