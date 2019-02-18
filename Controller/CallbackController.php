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

use OxidEsales\Eshop\Application\Controller\FrontendController;
use oe\novalnet\Classes\NovalnetUtil;

/**
 * Class CallbackController.
 */
class CallbackController extends FrontendController
{
    protected $_sThisTemplate    = 'novalnetcallback.tpl';
    protected $_aCaptureParams;
    protected $_oNovalnetUtil;
    protected $_blProcessTestMode;
    protected $_displayMessage;
    protected $_oDb;
    protected $_oLang;
    protected $_aViewData;
    /** @Array Type of payment available - Level : 0 */
    protected $aPayments         = ['CREDITCARD', 'INVOICE_START', 'DIRECT_DEBIT_SEPA', 'GUARANTEED_INVOICE', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'PAYPAL', 'ONLINE_TRANSFER', 'IDEAL', 'EPS', 'GIROPAY', 'PRZELEWY24','CASHPAYMENT'];

    /** @Array Type of Chargebacks available - Level : 1 */
    protected $aChargebacks      = ['RETURN_DEBIT_SEPA', 'CREDITCARD_BOOKBACK', 'CREDITCARD_CHARGEBACK', 'PAYPAL_BOOKBACK', 'PRZELEWY24_REFUND', 'REFUND_BY_BANK_TRANSFER_EU', 'REVERSAL','CASHPAYMENT_REFUND'];

    /** @Array Type of CreditEntry payment and Collections available - Level : 2 */
    protected $aCollections      = ['INVOICE_CREDIT', 'CREDIT_ENTRY_CREDITCARD', 'CREDIT_ENTRY_SEPA', 'DEBT_COLLECTION_SEPA', 'DEBT_COLLECTION_CREDITCARD', 'ONLINE_TRANSFER_CREDIT','CASHPAYMENT_CREDIT'];

    protected $aPaymentGroups    = [
                                    'novalnetcreditcard'     => ['CREDITCARD', 'CREDITCARD_BOOKBACK', 'CREDITCARD_CHARGEBACK', 'CREDIT_ENTRY_CREDITCARD', 'DEBT_COLLECTION_CREDITCARD', 'SUBSCRIPTION_STOP'],
                                    'novalnetsepa'           => ['DIRECT_DEBIT_SEPA', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'RETURN_DEBIT_SEPA', 'DEBT_COLLECTION_SEPA', 'CREDIT_ENTRY_SEPA', 'SUBSCRIPTION_STOP' ],
                                    'novalnetideal'          => ['IDEAL', 'REFUND_BY_BANK_TRANSFER_EU', 'ONLINE_TRANSFER_CREDIT', 'REVERSAL'],
                                    'novalnetonlinetransfer' => ['ONLINE_TRANSFER', 'REFUND_BY_BANK_TRANSFER_EU', 'ONLINE_TRANSFER_CREDIT', 'REVERSAL'],
                                    'novalnetpaypal'         => ['PAYPAL', 'PAYPAL_BOOKBACK', 'SUBSCRIPTION_STOP', 'REFUND_BY_BANK_TRANSFER_EU'],
                                    'novalnetprepayment'     => ['INVOICE_START', 'INVOICE_CREDIT', 'SUBSCRIPTION_STOP'],
                                    'novalnetinvoice'        => ['INVOICE_START', 'INVOICE_CREDIT', 'GUARANTEED_INVOICE', 'SUBSCRIPTION_STOP'],
                                    'novalneteps'            => ['EPS', 'REFUND_BY_BANK_TRANSFER_EU'],
                                    'novalnetgiropay'        => ['GIROPAY', 'REFUND_BY_BANK_TRANSFER_EU'],
                                    'novalnetprzelewy24'     => ['PRZELEWY24', 'PRZELEWY24_REFUND', 'REFUND_BY_BANK_TRANSFER_EU'],
                                    'novalnetbarzahlen'     => ['CASHPAYMENT', 'CASHPAYMENT_CREDIT', 'CASHPAYMENT_REFUND']
                                        ];

    protected $aParamsRequired    = ['vendor_id', 'tid', 'payment_type', 'status', 'tid_status'];

    protected $aAffParamsRequired = ['vendor_id', 'vendor_authcode', 'product_id', 'vendor_activation', 'aff_id', 'aff_authcode', 'aff_accesskey'];

    /**
     * Returns name of template to render
     *
     * @return string
     */
    public function render()
    {
        return $this->_sThisTemplate;
    }

    /**
     * Handles the callback request
     *
     * @return boolean
     */
    public function handleRequest()
    {
        $this->_aCaptureParams     = array_map('trim', $_REQUEST);
        $this->_oNovalnetUtil = oxNew(NovalnetUtil::class);
        $this->_blProcessTestMode  = $this->_oNovalnetUtil->getNovalnetConfigValue('blCallbackTestMode');
        $this->_oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(\OxidEsales\Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC);
        $this->_oLang = oxNew(\OxidEsales\Eshop\Core\Language::class);
        $this->_aViewData['sNovalnetMessage'] = '';
        if ($this->_validateCaptureParams())
        {
            if (!empty($this->_aCaptureParams['vendor_activation']))
            {
                $this->_updateAffiliateActivationDetails();
            } else {
                $this->_processNovalnetCallback();
            }
        }
        return false;
    }

    /**
     * Adds affiliate account
     *
     */
    private function _updateAffiliateActivationDetails()
    {
        $sNovalnetAffSql     = 'INSERT INTO novalnet_aff_account_detail (VENDOR_ID, VENDOR_AUTHCODE, PRODUCT_ID, PRODUCT_URL, ACTIVATION_DATE, AFF_ID, AFF_AUTHCODE, AFF_ACCESSKEY) VALUES ( ?, ?, ?, ?, ?, ?, ?, ? )';
        $aNovalnetAffDetails = [$this->_aCaptureParams['vendor_id'], $this->_aCaptureParams['vendor_authcode'], (!empty($this->_aCaptureParams['product_id']) ? $this->_aCaptureParams['product_id'] : ''), (!empty($this->_aCaptureParams['product_url']) ? $this->_aCaptureParams['product_url'] : ''), (!empty($this->_aCaptureParams['activation_date']) ? date('Y-m-d H:i:s', strtotime($this->_aCaptureParams['activation_date'])) : ''), $this->_aCaptureParams['aff_id'], $this->_aCaptureParams['aff_authcode'], $this->_aCaptureParams['aff_accesskey']];
        $this->_oDb->execute( $sNovalnetAffSql, $aNovalnetAffDetails );

        $sMessage = 'Novalnet callback script executed successfully with Novalnet account activation information';
        $sMessage = $this->_sendMail($sMessage) . $sMessage;
        $this->_displayMessage($sMessage);
    }

    /**
     * Validates the callback request
     *
     * @return boolean
     */
    private function _validateCaptureParams()
    {
        $sIpAllowed = gethostbyname('pay-nn.de');

        if (empty($sIpAllowed)) {
            $this->_displayMessage('Novalnet HOST IP missing');
            return false;
        }
        $sIpAddress = $this->_oNovalnetUtil->getIpAddress();
        $sMessage   = '';
        if (($sIpAddress != $sIpAllowed) && empty($this->_blProcessTestMode)) {
            $this->_displayMessage('Novalnet callback received. Unauthorized access from the IP [' . $sIpAddress . ']');
            return false;
        }

        $aParamsRequired = (!empty($this->_aCaptureParams['vendor_activation'])) ? $this->aAffParamsRequired : $this->aParamsRequired;

        $this->_aCaptureParams['shop_tid'] = $this->_aCaptureParams['tid'];

        if (in_array($this->_aCaptureParams['payment_type'], array_merge($this->aChargebacks, $this->aCollections))) {
            array_push($aParamsRequired, 'tid_payment');
            $this->_aCaptureParams['shop_tid'] = $this->_aCaptureParams['tid_payment'];
        } elseif (!empty($this->_aCaptureParams['subs_billing']) || $this->_aCaptureParams['payment_type'] == 'SUBSCRIPTION_STOP') {
            array_push($aParamsRequired, 'signup_tid');
            $this->_aCaptureParams['shop_tid'] = $this->_aCaptureParams['signup_tid'];
        }
        foreach ($aParamsRequired as $sValue) {
            if (empty($this->_aCaptureParams[$sValue])) {
                $this->_displayMessage('Required param ( ' . $sValue . ' ) missing!<br>');
                return false;
            }
        }

        if (!empty($this->_aCaptureParams['vendor_activation']))
            return true;

        if (!is_numeric($this->_aCaptureParams['status']) || $this->_aCaptureParams['status'] <= 0) {
            $this->_displayMessage('Novalnet callback received. Status (' . $this->_aCaptureParams['status'] . ') is not valid');
            return false;
        }

        foreach (['signup_tid', 'tid_payment', 'tid'] as $sTid) {
            if (!empty($this->_aCaptureParams[$sTid]) && !preg_match('/^\d{17}$/', $this->_aCaptureParams[$sTid])) {
                $this->_displayMessage('Novalnet callback received. Invalid TID [' . $this->_aCaptureParams[$sTid] . '] for Order');
                return false;
            }
        }
        return true;
    }

    /**
     * Process the callback request
     *
     * @return void
     */
    private function _processNovalnetCallback()
    {
        if (!$this->_getOrderDetails())
            return;

        $sSql              = 'SELECT SUM(amount) AS paid_amount FROM novalnet_callback_history where ORDER_NO = "' . $this->aOrderDetails['ORDER_NO'] . '"';
        $aResult           = $this->_oDb->getRow($sSql);
        $dPaidAmount       = $aResult['paid_amount'];
        $dAmount = $this->aOrderDetails['TOTAL_AMOUNT'] - $this->aOrderDetails['REFUND_AMOUNT'];
        $dFormattedAmount  = sprintf('%0.2f', ($this->_aCaptureParams['amount']/100)) . ' ' . $this->_aCaptureParams['currency']; // Formatted callback amount

        $sLineBreak = '<br><br>';
        $iPaymentTypeLevel = $this->_getPaymentTypeLevel();

        if ($iPaymentTypeLevel === 0) {
            if ($this->_aCaptureParams['subs_billing'] == 1 ) {
                // checks status of callback. if 100, then recurring processed or subscription canceled
                if (in_array($this->_aCaptureParams['status'], ['100', '90']) && in_array($this->_aCaptureParams['tid_status'], ['100',  '90', '91', '98', '99', '85'])) {
                    $sNovalnetComments = 'Novalnet Callback Script executed successfully for the subscription TID:' . $this->_aCaptureParams['signup_tid'] . ' with amount: ' . $dFormattedAmount . ' on ' . date('Y-m-d H:i:s') . '. Please refer PAID transaction in our Novalnet Merchant Administration with the TID: ' . $this->_aCaptureParams['tid'];

                    $this->_createFollowupOrder();
                    $sNovalnetComments = $this->_sendMail($sNovalnetComments) . $sNovalnetComments;
                    $this->_displayMessage($sNovalnetComments);
                } else {
                    $this->_subscriptionCancel();
                }
            } elseif (in_array($this->_aCaptureParams['payment_type'], ['PAYPAL', 'PRZELEWY24']) && $this->_aCaptureParams['status'] == '100' && $this->_aCaptureParams['tid_status'] == '100') {
                if (!isset($dPaidAmount)) {
                    $sNovalnetCallbackSql     = 'INSERT INTO novalnet_callback_history (PAYMENT_TYPE, STATUS, ORDER_NO, AMOUNT, CURRENCY, CALLBACK_TID, ORG_TID, PRODUCT_ID, CALLBACK_DATE) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ? )';
                    $aNovalnetCallbackDetails = [ $this->_aCaptureParams['payment_type'], $this->_aCaptureParams['status'], $this->aOrderDetails['ORDER_NO'], $this->_aCaptureParams['amount'], $this->_aCaptureParams['currency'], $this->_aCaptureParams['tid'], $this->_aCaptureParams['tid'], $this->_aCaptureParams['product_id'], date('Y-m-d H:i:s') ];
                    $this->_oDb->execute($sNovalnetCallbackSql, $aNovalnetCallbackDetails);

                    $sNovalnetComments = 'Novalnet Callback Script executed successfully for the TID: ' . $this->_aCaptureParams['tid'] . ' with amount ' . $dFormattedAmount . ' on ' . date('Y-m-d H:i:s');
                    $sComments = $sLineBreak . $sNovalnetComments;
                    $this->_oDb->execute('UPDATE oxorder SET OXPAID = "' . date('Y-m-d H:i:s') . '", NOVALNETCOMMENTS = CONCAT(IF(NOVALNETCOMMENTS IS NULL, "", NOVALNETCOMMENTS), "' . $sComments . '") WHERE OXORDERNR ="' . $this->aOrderDetails['ORDER_NO'] . '"');
                    $this->_oDb->execute('UPDATE novalnet_transaction_detail SET GATEWAY_STATUS = "' . $this->_aCaptureParams['tid_status'] . '" WHERE ORDER_NO ="' . $this->aOrderDetails['ORDER_NO'] . '"');
                    $sNovalnetComments = $this->_sendMail($sNovalnetComments) . $sNovalnetComments;
                    $this->_displayMessage($sNovalnetComments);
                } else {
                    $this->_displayMessage('Novalnet Callback script received. Order already Paid');
                }
            } elseif($this->_aCaptureParams['payment_type']=='PRZELEWY24' && $this->_aCaptureParams['status'] != '100' && $this->_aCaptureParams['tid_status'] != '86') {
                    $sNovalnetComments = 'The transaction has been canceled due to: ' . $this->_oNovalnetUtil->setNovalnetPaygateError($this->_aCaptureParams);
                    $sComments = $sLineBreak . $sNovalnetComments;
                    $this->_oDb->execute('UPDATE oxorder SET NOVALNETCOMMENTS = CONCAT(IF(NOVALNETCOMMENTS IS NULL, "", NOVALNETCOMMENTS), "' . $sComments . '") WHERE OXORDERNR ="' . $this->aOrderDetails['ORDER_NO'] . '"');
                    $this->_oDb->execute('UPDATE novalnet_transaction_detail SET GATEWAY_STATUS = "' . $this->_aCaptureParams['tid_status'] . '" WHERE ORDER_NO ="' . $this->aOrderDetails['ORDER_NO'] . '"');
                    $sNovalnetComments = $this->_sendMail($sNovalnetComments) . $sNovalnetComments;
                    $this->_displayMessage($sNovalnetComments);
            }  elseif (in_array($this->_aCaptureParams['payment_type'], ['INVOICE_START', 'GUARANTEED_INVOICE']) &&  $this->_aCaptureParams['tid_status'] == '100' && $this->_aCaptureParams['status'] == '100' && $this->aOrderDetails['GATEWAY_STATUS'] == '91') {
                $sUpdateSql = 'UPDATE novalnet_preinvoice_transaction_detail SET DUE_DATE =  "' . $this->_aCaptureParams['due_date'] . '" WHERE ORDER_NO ="' . $this->aOrderDetails['ORDER_NO'] . '"';
                $this->_oDb->execute($sUpdateSql);

                $sUpdateSql = 'UPDATE novalnet_transaction_detail SET GATEWAY_STATUS =  "' . $this->_aCaptureParams['tid_status'] . '" WHERE ORDER_NO ="' . $this->aOrderDetails['ORDER_NO'] . '"';
                $this->_oDb->execute($sUpdateSql);

                $sNovalnetComments = 'Novalnet Callbackscript received. The transaction has been confirmed successfully for the TID: '.$this->_aCaptureParams['shop_tid'].' and the due date updated as '.$this->_aCaptureParams['due_date'].'';
                $sComments = $sLineBreak . $sNovalnetComments;
                $sUpdateSql = 'UPDATE oxorder SET NOVALNETCOMMENTS = CONCAT(IF(NOVALNETCOMMENTS IS NULL, "", NOVALNETCOMMENTS), "' . $sComments . '") WHERE OXORDERNR ="' . $this->aOrderDetails['ORDER_NO'] . '"';
                $this->_oDb->execute($sUpdateSql);

                $sNovalnetComments = $this->_sendMail($sNovalnetComments) . $sNovalnetComments;
                $this->_displayMessage($sNovalnetComments);
            }
            elseif ($this->_aCaptureParams['status'] != '100' || !in_array($this->_aCaptureParams['tid_status'], ['100', '85', '86', '90', '91', '98', '99'])) {
                $this->_displayMessage('Novalnet callback received. Status is not valid');
            } else {
                $this->_displayMessage('Novalnet Callback script received. Payment type ( ' . $this->_aCaptureParams['payment_type'] . ' ) is not applicable for this process!');
            }
        } elseif ($iPaymentTypeLevel == 1 && $this->_aCaptureParams['status'] == '100' && $this->_aCaptureParams['tid_status'] == '100') {
            $sNovalnetComments = 'Novalnet callback received. Chargeback executed successfully for the TID: ' . $this->_aCaptureParams['tid_payment'] . ' amount ' . $dFormattedAmount . ' on ' . date('Y-m-d H:i:s') . '. The subsequent TID: ' . $this->_aCaptureParams['tid'];

            if (in_array($this->_aCaptureParams['payment_type'], ['CREDITCARD_BOOKBACK', 'PAYPAL_BOOKBACK', 'PRZELEWY24_REFUND', 'REFUND_BY_BANK_TRANSFER_EU'])) {
                $sNovalnetComments = 'Novalnet callback received. Refund/Bookback executed successfully for the TID: ' . $this->_aCaptureParams['tid_payment'] . ' amount ' . $dFormattedAmount . ' on ' . date('Y-m-d H:i:s') . '. The subsequent TID: ' . $this->_aCaptureParams['tid'];
            }
            $sComments = $sLineBreak . $sNovalnetComments;
            $sUpdateSql = 'UPDATE oxorder SET NOVALNETCOMMENTS = CONCAT(IF(NOVALNETCOMMENTS IS NULL, "", NOVALNETCOMMENTS), "' . $sComments . '") WHERE OXORDERNR ="' . $this->aOrderDetails['ORDER_NO'] . '"';
            $this->_oDb->execute($sUpdateSql);
            $sNovalnetComments = $this->_sendMail($sNovalnetComments) . $sNovalnetComments;
            $this->_displayMessage($sNovalnetComments);
        } elseif ($iPaymentTypeLevel == 2 && $this->_aCaptureParams['status'] == '100' && $this->_aCaptureParams['tid_status'] == '100') {
            if (!isset($dPaidAmount) || $dPaidAmount < $dAmount) {
                $dTotalAmount             = $dPaidAmount + $this->_aCaptureParams['amount'];
                $sNovalnetCallbackSql     = 'INSERT INTO novalnet_callback_history (PAYMENT_TYPE, STATUS, ORDER_NO, AMOUNT, CURRENCY, CALLBACK_TID, ORG_TID, PRODUCT_ID, CALLBACK_DATE) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ? )';
                $aNovalnetCallbackDetails = [ $this->_aCaptureParams['payment_type'], $this->_aCaptureParams['status'], $this->aOrderDetails['ORDER_NO'], $this->_aCaptureParams['amount'], $this->_aCaptureParams['currency'], $this->_aCaptureParams['tid'], $this->_aCaptureParams['tid_payment'], $this->_aCaptureParams['product_id'], date('Y-m-d H:i:s') ];
                $this->_oDb->execute($sNovalnetCallbackSql, $aNovalnetCallbackDetails);

                $sNovalnetComments = 'Novalnet Callback Script executed successfully for the TID: ' . $this->_aCaptureParams['tid_payment'] . ' with amount ' . $dFormattedAmount . ' on ' . date('Y-m-d H:i:s') . '. Please refer PAID transaction in our Novalnet Merchant Administration with the TID: ' . $this->_aCaptureParams['tid'];
                $sComments = $sLineBreak . $sNovalnetComments;
                $sUpdateSql = 'UPDATE oxorder SET NOVALNETCOMMENTS = CONCAT(IF(NOVALNETCOMMENTS IS NULL, "", NOVALNETCOMMENTS), "' . $sComments . '") WHERE OXORDERNR ="' . $this->aOrderDetails['ORDER_NO'] . '"';

                if ($dAmount <= $dTotalAmount)
                    $sUpdateSql = 'UPDATE oxorder SET OXPAID = "' . date('Y-m-d H:i:s') . '", NOVALNETCOMMENTS = CONCAT(IF(NOVALNETCOMMENTS IS NULL, "", NOVALNETCOMMENTS), "' . $sComments . '") WHERE OXORDERNR ="' . $this->aOrderDetails['ORDER_NO'] . '"';

                $this->_oDb->execute($sUpdateSql);

                $sNovalnetComments = $this->_sendMail($sNovalnetComments) . $sNovalnetComments;

                $this->_displayMessage($sNovalnetComments);

            } else {
                $this->_displayMessage('Novalnet Callback script received. Order already Paid');
            }
        } elseif ($this->_aCaptureParams['payment_type'] == 'SUBSCRIPTION_STOP') {
            $this->_subscriptionCancel();
        } elseif ($this->_aCaptureParams['status'] != '100' || $this->_aCaptureParams['tid_status'] != '100') {
            $this->_displayMessage('Novalnet callback received. Status is not valid');
        } else {
            $this->_displayMessage('Novalnet callback script executed already');
        }
    }

    /**
     * Gets payment level of the callback request
     *
     * @return integer
     */
    private function _getPaymentTypeLevel()
    {
        if (in_array($this->_aCaptureParams['payment_type'], $this->aPayments))
            return 0;
        elseif (in_array($this->_aCaptureParams['payment_type'], $this->aChargebacks))
            return 1;
        elseif (in_array($this->_aCaptureParams['payment_type'], $this->aCollections))
            return 2;
    }

    /**
     * Gets order details from the shop for the callback request
     *
     * @return boolean
     */
    private function _getOrderDetails()
    {
        $iOrderNo = !empty($this->_aCaptureParams['order_no']) ? $this->_aCaptureParams['order_no'] : (!empty($this->_aCaptureParams['order_id']) ? $this->_aCaptureParams['order_id'] : '');
        $sSql     = 'SELECT trans.ORDER_NO, trans.TOTAL_AMOUNT,trans.NNBASKET, trans.REFUND_AMOUNT, trans.PAYMENT_ID,  trans.GATEWAY_STATUS, o.OXPAYMENTTYPE FROM novalnet_transaction_detail trans JOIN oxorder o ON o.OXORDERNR = trans.ORDER_NO where trans.tid = "' . $this->_aCaptureParams['shop_tid'] . '"';

        $this->aOrderDetails = $this->_oDb->getRow($sSql);

        // checks the payment type of callback and order
        if (empty($this->aOrderDetails['OXPAYMENTTYPE']) || !in_array($this->_aCaptureParams['payment_type'], $this->aPaymentGroups[$this->aOrderDetails['OXPAYMENTTYPE']])) {
            $this->_displayMessage('Novalnet callback received. Payment Type [' . $this->_aCaptureParams['payment_type'] . '] is not valid');
            return false;
        }

        // checks the order number in shop
        if (empty($this->aOrderDetails['ORDER_NO'])) {
            $this->_displayMessage('Transaction mapping failed');
            return false;
        }

        // checks order number of callback and shop only when the callback having the order number
        if (!empty($iOrderNo) && $iOrderNo != $this->aOrderDetails['ORDER_NO']) {
            $this->_displayMessage('Novalnet callback received. Order Number is not valid');
            return false;
        }
        return true;
    }

    /**
     * Displays the message
     *
     * @param string  $sMessage
     *
     */
    private function _displayMessage($sMessage)
    {
        $this->_aViewData['sNovalnetMessage'] = $sMessage;
    }

    /**
     * Cancel Novalnet Subscription
     *
     * @param string  $sMessage
     *
     */
    private function _subscriptionCancel()
    {
         $sTerminationReason = !empty($this->_aCaptureParams['termination_reason']) ? $this->_aCaptureParams['termination_reason'] : $this->_aCaptureParams['status_message'];
         $sUpdateSql = 'UPDATE novalnet_subscription_detail SET TERMINATION_REASON = "' . $sTerminationReason . '", TERMINATION_AT = "' . date('Y-m-d H:i:s') . '" WHERE ORDER_NO = "' . $this->aOrderDetails['ORDER_NO'] . '"';
         $this->_oDb->execute($sUpdateSql);

         $sNovalnetComments = '<br>Novalnet callback script received. Subscription has been stopped for the TID: ' . $this->_aCaptureParams['shop_tid'] . ' on ' . date('Y-m-d H:i:s') . '.<br>Reason for Cancellation: ' . $sTerminationReason;
         $sComments = $sLineBreak . $sNovalnetComments;

         $sSQL = 'SELECT SUBS_ID FROM novalnet_subscription_detail WHERE TID='.$this->_aCaptureParams['shop_tid'];
         $aSubId = $this->_oDb->getRow($sSQL);

         $sSQL = 'SELECT ORDER_NO from novalnet_subscription_detail WHERE SUBS_ID  = "'.$aSubId['SUBS_ID'].'"';

         $aOrderNr = $this->_oDb->getAll($sSQL);
         foreach($aOrderNr as $skey => $dValue) {
              $sUpdateSql = 'UPDATE oxorder SET NOVALNETCOMMENTS = CONCAT(IF(NOVALNETCOMMENTS IS NULL, "", NOVALNETCOMMENTS), "' . $sComments . '") WHERE OXORDERNR ="' . $dValue['ORDER_NO'] . '"';
              $this->_oDb->execute($sUpdateSql);
         }
         $sNovalnetComments = $this->_sendMail($sNovalnetComments) . $sNovalnetComments;
         $this->_displayMessage($sNovalnetComments);
    }

    /**
     * Sends messages as mail
     *
     * @param string $sMessage
     *
     * @return string
     */
    private function _sendMail($sMessage)
    {
        $blCallbackMail = $this->_oNovalnetUtil->getNovalnetConfigValue('blCallbackMail');
        if ($blCallbackMail) {
            $oMail = oxNew(\OxidEsales\Eshop\Core\Email::class);
            $sToAddress    = $this->_oNovalnetUtil->getNovalnetConfigValue('sCallbackMailToAddr');
            $sBccAddress   = $this->_oNovalnetUtil->getNovalnetConfigValue('sCallbackMailBccAddr');
            $sEmailSubject = 'Novalnet Callback Script Access Report';
            $blValidTo     = false;
            // validates 'to' addresses
            if ($sToAddress) {
                $aToAddress = explode( ',', $sToAddress );
                foreach ($aToAddress as $sMailAddress) {
                    $sMailAddress = trim($sMailAddress);
                    if (oxNew(\OxidEsales\Eshop\Core\MailValidator::class)->isValidEmail($sMailAddress)) {
                        $oMail->setRecipient($sMailAddress);
                        $blValidTo = true;
                    }
                }
            }
            if (!$blValidTo)
                return 'Mail not sent<br>';

            // validates 'bcc' addresses
            if ($sBccAddress) {
                $aBccAddress = explode( ',', $sBccAddress );
                foreach ($aBccAddress as $sMailAddress) {
                    $sMailAddress = trim($sMailAddress);
                    if (oxNew(\OxidEsales\Eshop\Core\MailValidator::class)->isValidEmail($sMailAddress))
                        $oMail->AddBCC($sMailAddress);
                }
            }

            $oShop = $oMail->getShop();
            $oMail->setFrom($oShop->oxshops__oxorderemail->value);
            $oMail->setSubject( $sEmailSubject );
            $oMail->setBody( $sMessage );

            if ($oMail->send())
                return 'Mail sent successfully<br>';

        }
        return 'Mail not sent<br>';
    }

    /**
     * Create the new subscription order
     *
     */
    private function _createFollowupOrder()
    {
        $oOrderNr = $this->aOrderDetails['ORDER_NO'];
        $aTxndetails = $this->aOrderDetails;

        // Get oxorder details
        $aOrderDetails = $this->_oDb->getRow('SELECT * FROM oxorder where OXORDERNR = "' . $oOrderNr. '"');

        // Get oxorderarticles details
        $aOxorderarticles = $this->_oDb->getAll('SELECT * FROM oxorderarticles where OXORDERID = "' . $aOrderDetails['OXID']. '"');

        // Load Order number
        $iCnt = oxNew(\OxidEsales\Eshop\Core\Counter::class)->getNext( 'oxOrder' );

        $iNextSubsCycle = !empty($this->_aCaptureParams['next_subs_cycle']) ? $this->_aCaptureParams['next_subs_cycle'] : (!empty($this->_aCaptureParams['paid_until']) ? $this->_aCaptureParams['paid_until'] : '');

        $this->_oLang->setBaseLanguage($aOrderDetails['OXLANG']);

         // Novalnet transaction comments
         $sOrderComments =  $this->_oLang->translateString('NOVALNET_TRANSACTION_DETAILS');
         if (in_array($this->aOrderDetails['PAYMENT_ID'], ['41','40'])) {
             $sOrderComments .= $this->_oLang->translateString('NOVALNET_PAYMENT_GURANTEE_COMMENTS');
         }
         $sOrderComments .= $this->_oLang->translateString('NOVALNET_TRANSACTION_ID') . $this->_aCaptureParams['tid'];
         $sOrderComments .= !empty($this->_aCaptureParams['test_mode']) ? $this->_oLang->translateString('NOVALNET_TEST_ORDER') : '';

        $sOrderComments .= (in_array($aTxndetails['OXPAYMENTTYPE'], ['novalnetinvoice','novalnetprepayment'])) ? $this->_getBankdetails($aOrderDetails['OXLANG']).'<br>' : '';

         $sOrderComments .= $this->_oLang->translateString('NOVALNET_REFERENCE_ORDER_NUMBER'). $oOrderNr.'<br>';
         $sOrderComments .= $this->_oLang->translateString('NOVALNET_NEXT_CHARGING_DATE'). $iNextSubsCycle;

        $this->_insertOxorderTable($aOrderDetails, $sOrderComments, $iCnt);

        foreach($aOxorderarticles as $key => $aOxorderArticle) {
           $sUId = $this->_oNovalnetUtil->oSession->getVariable( 'sOxid');
           $this->_insertOxorderArticlesTable($sUId, $aOxorderArticle);
           $this->getOxAmount($aOrderDetails['OXID']);
        }

        $this->_insertNovalnetTranTable($oOrderNr, $iCnt);
        $this->_insertNovalnetSubDetailsTable($oOrderNr, $iCnt);
        $this->_insertNovalnetCallbackTable($oOrderNr, $iCnt);
        if (in_array($aTxndetails['OXPAYMENTTYPE'], ['novalnetinvoice','novalnetprepayment'])) {
            $this->_insertNovalnetPreInvTable($oOrderNr, $iCnt);
        }

        $this->_sendOrderByEmail($sUId, $aTxndetails['NNBASKET']);

        $this->_oNovalnetUtil->oSession->deleteVariable( 'sOxid' );
    }

    /**
     * Insert the new order details on Oxorder table
     *
     * @param array $aOrderDetails
     * @param string $sOrderComments
     * @param double $iCnt
     *
     */
     protected function _insertOxorderTable($aOrderDetails, $sOrderComments, $iCnt)
     {

         $aOrder['OXID'] = \OxidEsales\Eshop\Core\UtilsObject::getInstance()->generateUID();
         $this->_oNovalnetUtil->oSession->setVariable( 'sOxid', $aOrder['OXID']);
         $oOrder = oxNew(\OxidEsales\Eshop\Application\Model\Order::class);
         $oOrder->setId($aOrder['OXID']);
         $iInsertTime = time();
         $now = date('Y-m-d H:i:s', $iInsertTime);
         if ($this->_aCaptureParams['payment_type'] == 'PAYPAL') {
              $sNovalnetPaidDate = $this->_aCaptureParams['status'] == '100' && in_array($this->_aCaptureParams['tid_status'], ['100','90']) ? $now : '0000-00-00 00:00:00';
          } else {
              $sNovalnetPaidDate = ($this->_aCaptureParams['payment_type'] == 'INVOICE_START' && $this->_aCaptureParams['status'] == '100' && in_array($this->_aCaptureParams['tid_status'], ['100','91'])) ? '0000-00-00 00:00:00' : $now;
          }
         $oOrder->oxorder__oxshopid          = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXSHOPID']);
         $oOrder->oxorder__oxuserid          = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXUSERID']);
         $oOrder->oxorder__oxorderdate       = new \OxidEsales\Eshop\Core\Field($now);
         $oOrder->oxorder__oxordernr         = new \OxidEsales\Eshop\Core\Field($iCnt);
         $oOrder->oxorder__oxbillcompany     = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXBILLCOMPANY']);
         $oOrder->oxorder__oxbillemail       = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXBILLEMAIL']);
         $oOrder->oxorder__oxbillfname       = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXBILLFNAME']);
         $oOrder->oxorder__oxbilllname       = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXBILLLNAME']);
         $oOrder->oxorder__oxbillstreet      = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXBILLSTREET']);
         $oOrder->oxorder__oxbillstreetnr    = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXBILLSTREETNR']);
         $oOrder->oxorder__oxbilladdinfo     = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXBILLADDINFO']);
         $oOrder->oxorder__oxbillustid       = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXBILLUSTID']);
         $oOrder->oxorder__oxbillcity        = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXBILLCITY']);
         $oOrder->oxorder__oxbillcountryid   = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXBILLCOUNTRYID']);
         $oOrder->oxorder__oxbillstateid     = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXBILLSTATEID']);
         $oOrder->oxorder__oxbillzip         = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXBILLZIP']);
         $oOrder->oxorder__oxbillfon         = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXBILLFON']);
         $oOrder->oxorder__oxbillfax         = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXBILLFAX']);
         $oOrder->oxorder__oxbillsal         = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXBILLSAL']);
         $oOrder->oxorder__oxdelcompany      = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXDELCOMPANY']);
         $oOrder->oxorder__oxdelfname        = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXDELLNAME']);
         $oOrder->oxorder__oxdellname        = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXDELCOMPANY']);
         $oOrder->oxorder__oxdelstreet       = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXDELCOMPANY']);
         $oOrder->oxorder__oxdelstreetnr     = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXDELSTREETNR']);
         $oOrder->oxorder__oxdeladdinfo      = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXDELADDINFO']);
         $oOrder->oxorder__oxdelcity         = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXDELCITY']);
         $oOrder->oxorder__oxdelcountryid    = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXDELCOUNTRYID']);
         $oOrder->oxorder__oxdelstateid      = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXDELSTATEID']);
         $oOrder->oxorder__oxdelzip          = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXDELZIP']);
         $oOrder->oxorder__oxdelfon          = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXDELFON']);
         $oOrder->oxorder__oxdelfax          = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXDELFAX']);
         $oOrder->oxorder__oxdelsal          = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXDELSAL']);
         $oOrder->oxorder__oxpaymentid       = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXPAYMENTID']);
         $oOrder->oxorder__oxpaymenttype     = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXPAYMENTTYPE']);
         $oOrder->oxorder__oxtotalnetsum     = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXTOTALNETSUM']);
         $oOrder->oxorder__oxtotalbrutsum    = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXTOTALBRUTSUM']);
         $oOrder->oxorder__oxtotalordersum   = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXTOTALORDERSUM']);
         $oOrder->oxorder__oxartvat1         = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXARTVAT1']);
         $oOrder->oxorder__oxartvatprice1    = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXARTVATPRICE1']);
         $oOrder->oxorder__oxartvat2         = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXARTVAT2']);
         $oOrder->oxorder__oxartvatprice2    = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXARTVATPRICE2']);
         $oOrder->oxorder__oxdelcost         = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXDELCOST']);
         $oOrder->oxorder__oxdelvat          = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXDELVAT']);
         $oOrder->oxorder__oxpaycost         = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXPAYCOST']);
         $oOrder->oxorder__oxpayvat          = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXPAYVAT']);
         $oOrder->oxorder__oxwrapcost        = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXWRAPCOST']);
         $oOrder->oxorder__oxwrapvat         = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXWRAPVAT']);
         $oOrder->oxorder__oxgiftcardcost    = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXGIFTCARDCOST']);
         $oOrder->oxorder__oxgiftcardvat     = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXGIFTCARDVAT']);
         $oOrder->oxorder__oxcardid          = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXCARDID']);
         $oOrder->oxorder__oxcardtext        = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXCARDTEXT']);
         $oOrder->oxorder__oxdiscount        = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXDISCOUNT']);
         $oOrder->oxorder__oxexport          = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXEXPORT']);
         $oOrder->oxorder__oxbillnr          = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXBILLNR']);
         $oOrder->oxorder__oxbilldate        = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXBILLDATE']);
         $oOrder->oxorder__oxtrackcode       = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXTRACKCODE']);
         $oOrder->oxorder__oxsenddate        = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXSENDDATE']);
         $oOrder->oxorder__oxremark          = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXREMARK']);
         $oOrder->oxorder__oxvoucherdiscount = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXVOUCHERDISCOUNT']);
         $oOrder->oxorder__oxcurrency        = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXCURRENCY']);
         $oOrder->oxorder__oxcurrate         = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXCURRATE']);
         $oOrder->oxorder__oxfolder          = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXFOLDER']);
         $oOrder->oxorder__oxtransid         = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXTRANSID']);
         $oOrder->oxorder__oxpayid           = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXPAYID']);
         $oOrder->oxorder__oxxid             = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXXID']);
         $oOrder->oxorder__oxpaid            = new \OxidEsales\Eshop\Core\Field($sNovalnetPaidDate);
         $oOrder->oxorder__oxstorno          = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXSTORNO']);
         $oOrder->oxorder__oxip              = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXIP']);
         $oOrder->oxorder__oxtransstatus     = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXTRANSSTATUS']);
         $oOrder->oxorder__oxlang            = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXLANG']);
         $oOrder->oxorder__oxinvoicenr       = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXINVOICENR']);
         $oOrder->oxorder__oxdeltype         = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXDELTYPE']);
         $oOrder->oxorder__oxtsprotectid     = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXTSPROTECTID']);
         $oOrder->oxorder__oxtsprotectcosts  = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXTSPROTECTCOSTS']);
         $oOrder->oxorder__oxtimestamp       = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXTIMESTAMP']);
         $oOrder->oxorder__oxisnettomode     = new \OxidEsales\Eshop\Core\Field($aOrderDetails['OXISNETTOMODE']);
         $oOrder->oxorder__novalnetcomments  = new \OxidEsales\Eshop\Core\Field($sOrderComments);
         $oOrder->save();

    }

    /**
     * Insert the new order articles details on OxorderArticles table
     *
     * @param array $aOxorderArticle
     *
     */
     protected function _insertOxorderArticlesTable($sUId, $aOxorderArticle)
     {
        $sUniqueid = \OxidEsales\Eshop\Core\UtilsObject::getInstance()->generateUID();
        $oOrderArticle = oxNew(\OxidEsales\Eshop\Application\Model\OrderArticle::class);
        $oOrderArticle->oxorderarticles__oxoxid    = new \OxidEsales\Eshop\Core\Field($sUniqueid);
        $oOrderArticle->oxorderarticles__oxorderid = new \OxidEsales\Eshop\Core\Field($sUId);
        $oOrderArticle->oxorderarticles__oxamount  = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXAMOUNT']);
        $oOrderArticle->oxorderarticles__oxartid   = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXARTID']);
        $oOrderArticle->oxorderarticles__oxartnum  = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXARTNUM']);
        $oOrderArticle->oxorderarticles__oxtitle   = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXTITLE']);
        $oOrderArticle->oxorderarticles__oxshortdesc  = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXSHORTDESC']);
        $oOrderArticle->oxorderarticles__oxselvariant = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXSELVARIANT']);
        $oOrderArticle->oxorderarticles__oxnetprice   = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXNETPRICE']);
        $oOrderArticle->oxorderarticles__oxbrutprice  = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXBRUTPRICE']);
        $oOrderArticle->oxorderarticles__oxvatprice   = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXVATPRICE']);
        $oOrderArticle->oxorderarticles__oxvat        = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXVAT']);
        $oOrderArticle->oxorderarticles__oxpersparam  = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXPERSPARAM']);
        $oOrderArticle->oxorderarticles__oxprice      = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXPRICE']);
        $oOrderArticle->oxorderarticles__oxbprice     = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXBPRICE']);
        $oOrderArticle->oxorderarticles__oxnprice     = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXNPRICE']);
        $oOrderArticle->oxorderarticles__oxwrapid     = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXWRAPID']);
        $oOrderArticle->oxorderarticles__oxexturl     = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXEXTURL']);
        $oOrderArticle->oxorderarticles__oxurldesc    = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXURLDESC']);
        $oOrderArticle->oxorderarticles__oxurlimg     = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXURLIMG']);
        $oOrderArticle->oxarticles__oxthumb           = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXTHUMB']);
        $oOrderArticle->oxarticles__oxpic1            = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXPIC1']);
        $oOrderArticle->oxarticles__oxpic2            = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXPIC2']);
        $oOrderArticle->oxarticles__oxpic3            = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXPIC3']);
        $oOrderArticle->oxarticles__oxpic4            = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXPIC4']);
        $oOrderArticle->oxarticles__oxpic5            = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXPIC5']);
        $oOrderArticle->oxarticles__oxweight          = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXWEIGHT']);
        $oOrderArticle->oxarticles__oxstock           = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXSTOCK']);
        $oOrderArticle->oxarticles__oxdelivery        = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXDELIVERY']);
        $oOrderArticle->oxarticles__oxinsert          = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXINSERT']);
        $iInsertTime = time();
        $now = date('Y-m-d H:i:s', $iInsertTime);
        $oOrderArticle->oxorderarticles__oxtimestamp  = new \OxidEsales\Eshop\Core\Field( $now );
        $oOrderArticle->oxarticles__oxlength          = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXLENGTH']);
        $oOrderArticle->oxarticles__oxwidth           = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXWIDTH']);
        $oOrderArticle->oxarticles__oxheight          = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXHEIGHT']);
        $oOrderArticle->oxarticles__oxfile            = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXFILE']);
        $oOrderArticle->oxarticles__oxsearchkeys      = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXSEARCHKEYS']);
        $oOrderArticle->oxarticles__oxtemplate        = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXTEMPLATE']);
        $oOrderArticle->oxarticles__oxquestionemail   = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXQUESTIONEMAIL']);
        $oOrderArticle->oxarticles__oxissearch        = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXISSEARCH']);
        $oOrderArticle->oxarticles__oxfolder          = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXFOLDER']);
        $oOrderArticle->oxarticles__oxsubclass        = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXSUBCLASS']);
        $oOrderArticle->oxorderarticles__oxstorno     = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXSTORNO']);
        $oOrderArticle->oxorderarticles__oxordershopid = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXORDERSHOPID']);
        $oOrderArticle->oxorderarticles__oxisbundle    = new \OxidEsales\Eshop\Core\Field($aOxorderArticle['OXISBUNDLE']);
        $oOrderArticle->save();

    }

    /**
     * Get the Product Quantity and update the quantity in oxarticles table
     *
     * @param integer $iOrderID
     *
     */
    public function getOxAmount($iOrderID)
    {

        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(\OxidEsales\Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC);

        $sSql = 'SELECT OXARTID, OXAMOUNT FROM oxorderarticles where OXORDERID = "' .  $iOrderID . '"';
        $dgetOxAmount           = $oDb->getRow($sSql);

        $sArtSql = 'SELECT OXSTOCK FROM oxarticles where OXID = "' .  $dgetOxAmount['OXARTID']. '"';
        $dgetArtCount = $oDb->getRow($sArtSql);
        $dProductId = $dgetArtCount['OXSTOCK'] - $dgetOxAmount['OXAMOUNT'];
        if ( $dProductId < 0) {
            $dProductId = 0;
        }
        // Stock updated in oxarticles table
        $sUpdateSql = 'UPDATE oxarticles SET OXSTOCK = "' . $dProductId . '" WHERE OXID ="' . $dgetOxAmount['OXARTID'] . '"';

        $oDb->execute($sUpdateSql);

    }

    /**
     * Insert new order details on Novalnet transaction table
     *
     * @param integer $oOrderNr
     * @param integer $iCnt
     */
    private function _insertNovalnetTranTable($oOrderNr, $iCnt)
    {

        $oDb  = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(\OxidEsales\Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC);
        $aNNTransDetails = $oDb->getRow('SELECT * from novalnet_transaction_detail where ORDER_NO ='.$oOrderNr);

        // Insert new order details in Novalnet transaction details table
        $sInsertSql = 'INSERT INTO novalnet_transaction_detail (VENDOR_ID, PRODUCT_ID, AUTH_CODE, TARIFF_ID, TID, ORDER_NO, SUBS_ID, PAYMENT_ID, PAYMENT_TYPE, AMOUNT, CURRENCY, STATUS, GATEWAY_STATUS, TEST_MODE, CUSTOMER_ID, ORDER_DATE, REFUND_AMOUNT, TOTAL_AMOUNT, PROCESS_KEY, MASKED_DETAILS, ZERO_TRXNDETAILS, ZERO_TRXNREFERENCE, ZERO_TRANSACTION, REFERENCE_TRANSACTION, NNBASKET) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $aInsertValues  = [$aNNTransDetails['VENDOR_ID'], $aNNTransDetails['PRODUCT_ID'], $aNNTransDetails['AUTH_CODE'], $aNNTransDetails['TARIFF_ID'], $this->_aCaptureParams['tid'], $iCnt, $aNNTransDetails['SUBS_ID'], $aNNTransDetails['PAYMENT_ID'], $aNNTransDetails['PAYMENT_TYPE'], $this->_aCaptureParams['amount'], $aNNTransDetails['CURRENCY'], $this->_aCaptureParams['status'], $this->_aCaptureParams['tid_status'], $aNNTransDetails['TEST_MODE'], $aNNTransDetails['CUSTOMER_ID'], date('Y-m-d H:i:s'), '0', $this->_aCaptureParams['amount'], $aNNTransDetails['PROCESS_KEY'], '', '', '', '', $aNNTransDetails['REFERENCE_TRANSACTION'], $aNNTransDetails['NNBASKET']];

        $oDb->execute( $sInsertSql, $aInsertValues );

    }

    /**
     * Insert new order details on Novalnet subscription table
     *
     * @param integer $oOrderNr
     * @param integer $iCnt
     */
    private function _insertNovalnetSubDetailsTable($oOrderNr, $iCnt)
    {
        $oDb  = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(\OxidEsales\Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC);
        $aNNSubsDetails = $oDb->getRow('SELECT * from novalnet_subscription_detail where ORDER_NO ='.$oOrderNr);

        // Insert new order details in Novalnet subscription details table
        $sInsertSql = 'INSERT INTO novalnet_subscription_detail (ORDER_NO, SUBS_ID, TID, SIGNUP_DATE, TERMINATION_REASON, TERMINATION_AT) VALUES (?, ?, ?, ?, ?, ?)';

        $aInsertValues = [$iCnt, $aNNSubsDetails['SUBS_ID'], $aNNSubsDetails['TID'], date('Y-m-d H:i:s'), $aNNSubsDetails['TERMINATION_REASON'], $aNNSubsDetails['TERMINATION_AT']];

        $oDb->execute( $sInsertSql, $aInsertValues );
    }

    /**
     * Insert new order details in Novalnet Callback table
     *
     * @param integer $oOrderNr
     * @param integer $iCnt
     */
    private function _insertNovalnetCallbackTable($oOrderNr, $iCnt)
    {
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(\OxidEsales\Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC);
        $aNNCallbackDetails = $oDb->getRow('SELECT * from novalnet_callback_history where ORDER_NO ='.$oOrderNr);

        // Insert new order details in Novalnet subscription details table
        $sInsertSql = 'INSERT INTO novalnet_callback_history (PAYMENT_TYPE, STATUS, ORDER_NO, AMOUNT, CURRENCY, CALLBACK_TID, ORG_TID, PRODUCT_ID, CALLBACK_DATE) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $aInsertValues = [$aNNCallbackDetails['PAYMENT_TYPE'], $aNNCallbackDetails['STATUS'], $iCnt, $aNNCallbackDetails['AMOUNT'], $aNNCallbackDetails['CURRENCY'], $aNNCallbackDetails['CALLBACK_TID'], $aNNCallbackDetails['ORG_TID'], $aNNCallbackDetails['PRODUCT_ID'], $aNNCallbackDetails['CALLBACK_DATE']];

        $oDb->execute( $sInsertSql, $aInsertValues );
    }

    /**
     * Insert new order details in Novalnet Preinvoice table
     *
     * @param integer $oOrderNr
     * @param integer $iCnt
     */
    public function _insertNovalnetPreInvTable($oOrderNr, $iCnt)
    {
        $oDb  = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(\OxidEsales\Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC);
        $aNNPreInvDetails = $oDb->getRow('SELECT * from novalnet_preinvoice_transaction_detail where ORDER_NO ='.$oOrderNr);

         // Insert new order details in Novalnet Preinvoice transaction details table
        $sInsertSql = 'INSERT INTO novalnet_preinvoice_transaction_detail (ORDER_NO, TID, TEST_MODE, ACCOUNT_HOLDER, BANK_IBAN, BANK_BIC, BANK_NAME, BANK_CITY, AMOUNT, CURRENCY, INVOICE_REF, DUE_DATE, ORDER_DATE, PAYMENT_REF) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $aNNPreInvDetails['DUE_DATE'] = $this->_aCaptureParams['due_date'];
        $aInsertValues = [$iCnt, $this->_aCaptureParams['tid'], $aNNPreInvDetails['TEST_MODE'], $this->_aCaptureParams['invoice_account_holder'], $this->_aCaptureParams['invoice_iban'], $this->_aCaptureParams['invoice_bic'], $this->_aCaptureParams['invoice_bankname'], $this->_aCaptureParams['invoice_bankplace'], $this->_aCaptureParams['amount'], $this->_aCaptureParams['CURRENCY'], $aNNPreInvDetails['INVOICE_REF'], $aNNPreInvDetails['DUE_DATE'], date('Y-m-d H:i:s'), $aNNPreInvDetails['PAYMENT_REF']];

        $oDb->execute( $sInsertSql, $aInsertValues );
    }

    /**
     * Get Invoice prepayment details
     *
     */
    protected function _getBankdetails($sLang)
    {
        $oConfig = \OxidEsales\Eshop\Core\Registry::getConfig();
        $sFormattedAmount = $this->_oLang->formatCurrency($this->_aCaptureParams['amount']/100, $oConfig->getCurrencyObject($this->_aCaptureParams['amount'])) . ' ' . $this->_aCaptureParams['currency'];

        $this->_oLang->setBaseLanguage($sLang);

        $sInvoiceComments = $this->_oLang->translateString('NOVALNET_INVOICE_COMMENTS_TITLE');
        if (!empty($this->_aCaptureParams['due_date'])) {
            $sInvoiceComments .= $this->_oLang->translateString('NOVALNET_DUE_DATE') . date('d.m.Y', strtotime($this->_aCaptureParams['due_date']));
        }
        $sInvoiceComments .= $this->_oLang->translateString('NOVALNET_ACCOUNT') . $this->_aCaptureParams['invoice_account_holder'];
        $sInvoiceComments .= '<br>IBAN: ' . $this->_aCaptureParams['invoice_iban'];
        $sInvoiceComments .= '<br>BIC: '  . $this->_aCaptureParams['invoice_bic'];
        $sInvoiceComments .= '<br>Bank: ' . $this->_aCaptureParams['invoice_bankname'] . ' ' . $this->_aCaptureParams['invoice_bankplace'];
        $sInvoiceComments .= $this->_oLang->translateString('NOVALNET_AMOUNT') . $sFormattedAmount;

       return $sInvoiceComments;
    }

    /**
     * Send new order mail for customer & Owner
     *
     * @param string $oOrderId
     * @param object $oBasketValue
     *
     */
    protected function _sendOrderByEmail($oOrderId, $oBasketValue)
    {

        $oOrder = oxNew(\OxidEsales\Eshop\Application\Model\Order::class);
        $oOrder->load($oOrderId);

        $oUser = oxNew(\OxidEsales\Eshop\Application\Model\User::class);
        $oUser->load($oOrder->oxorder__oxuserid->value);

        $oOrder->_oUser = $oUser;

        $oPayment = oxNew(\OxidEsales\Eshop\Application\Model\UserPayment::class);
        $oPayment->load($oOrder->oxorder__oxpaymentid->value);
        $oOrder->_oPayment = $oPayment;

        $oBasket = unserialize($oBasketValue);
        $oOrder->_oBasket = $oBasket;

        $oxEmail = oxNew(\OxidEsales\Eshop\Core\Email::class);

        // send order email to user
        $oxEmail->sendOrderEMailToUser( $oOrder );

        // send order email to shop owner
        $oxEmail->sendOrderEMailToOwner( $oOrder );

    }
}

/*
Level 0 Payments:
-----------------
CREDITCARD
INVOICE_START
DIRECT_DEBIT_SEPA
GUARANTEED_INVOICE
GUARANTEED_DIRECT_DEBIT_SEPA
PAYPAL
ONLINE_TRANSFER
IDEAL
EPS
GIROPAY
PRZELEWY24

Level 1 Payments:
-----------------
RETURN_DEBIT_SEPA
GUARANTEED_RETURN_DEBIT_DE
REVERSAL
CREDITCARD_BOOKBACK
CREDITCARD_CHARGEBACK
REFUND_BY_BANK_TRANSFER_EU
PRZELEWY24_REFUND

Level 2 Payments:
-----------------
INVOICE_CREDIT
CREDIT_ENTRY_CREDITCARD
CREDIT_ENTRY_SEPA
CREDIT_ENTRY_DE
DEBT_COLLECTION_SEPA
DEBT_COLLECTION_CREDITCARD
DEBT_COLLECTION_DE
DEBT_COLLECTION_AT
*/
?>
