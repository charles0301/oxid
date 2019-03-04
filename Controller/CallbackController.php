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

namespace oe\novalnet\Controller;

use OxidEsales\Eshop\Application\Controller\FrontendController;
use oe\novalnet\Classes\NovalnetUtil;

/**
 * Class CallbackController.
 */
class CallbackController extends FrontendController
{
    protected $_sThisTemplate    = 'novalnetcallback.tpl';

    protected $_aCaptureParams; // Get REQUEST param

    protected $_oNovalnetUtil; // Get Util class object

    protected $_blProcessTestMode; // To performing the manual execution

    protected $_displayMessage; // Display message

    protected $_oDb; // Get oDb class object

    protected $_oLang; // Get oLang class object

    protected $_aViewData; // View data array

    protected $sTechnicNotifyMail = 'technic@novalnet.de';

    /** @Array Type of payment available - Level : 0 */
    protected $aPayments         = ['CREDITCARD', 'INVOICE_START', 'DIRECT_DEBIT_SEPA', 'GUARANTEED_INVOICE', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'PAYPAL', 'ONLINE_TRANSFER', 'IDEAL', 'EPS', 'GIROPAY', 'PRZELEWY24','CASHPAYMENT'];

    /** @Array Type of Chargebacks available - Level : 1 */
    protected $aChargebacks      = ['RETURN_DEBIT_SEPA', 'CREDITCARD_BOOKBACK', 'CREDITCARD_CHARGEBACK', 'PAYPAL_BOOKBACK', 'PRZELEWY24_REFUND', 'REFUND_BY_BANK_TRANSFER_EU', 'REVERSAL','CASHPAYMENT_REFUND', 'GUARANTEED_INVOICE_BOOKBACK', 'GUARANTEED_SEPA_BOOKBACK'];

    /** @Array Type of CreditEntry payment and Collections available - Level : 2 */
    protected $aCollections      = ['INVOICE_CREDIT', 'CREDIT_ENTRY_CREDITCARD', 'CREDIT_ENTRY_SEPA', 'DEBT_COLLECTION_SEPA', 'DEBT_COLLECTION_CREDITCARD', 'ONLINE_TRANSFER_CREDIT','CASHPAYMENT_CREDIT', 'DEBT_COLLECTION_DE', 'CREDIT_ENTRY_DE'];

    protected $aPaymentGroups    = [   'novalnetcreditcard'      => [ 'CREDITCARD', 'CREDITCARD_BOOKBACK', 'CREDITCARD_CHARGEBACK',                                    'CREDIT_ENTRY_CREDITCARD', 'DEBT_COLLECTION_CREDITCARD', 'SUBSCRIPTION_STOP', 'SUBSCRIPTION_REACTIVATE'],
                                        'novalnetsepa'           => [ 'DIRECT_DEBIT_SEPA', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'RETURN_DEBIT_SEPA', 'DEBT_COLLECTION_SEPA', 'CREDIT_ENTRY_SEPA', 'SUBSCRIPTION_STOP', 'SUBSCRIPTION_REACTIVATE', 'REFUND_BY_BANK_TRANSFER_EU', 'TRANSACTION_CANCELLATION', 'GUARANTEED_SEPA_BOOKBACK'],
                                        'novalnetideal'          => [ 'IDEAL', 'REFUND_BY_BANK_TRANSFER_EU', 'ONLINE_TRANSFER_CREDIT', 'REVERSAL', 'DEBT_COLLECTION_DE', 'CREDIT_ENTRY_DE'],
                                        'novalnetonlinetransfer' => [ 'ONLINE_TRANSFER', 'REFUND_BY_BANK_TRANSFER_EU', 'ONLINE_TRANSFER_CREDIT', 'REVERSAL', 'DEBT_COLLECTION_DE', 'CREDIT_ENTRY_DE'],
                                        'novalnetpaypal'         => [ 'PAYPAL', 'PAYPAL_BOOKBACK', 'SUBSCRIPTION_STOP', 'SUBSCRIPTION_REACTIVATE'],
                                        'novalnetprepayment'     => [ 'INVOICE_START', 'INVOICE_CREDIT', 'SUBSCRIPTION_STOP','SUBSCRIPTION_REACTIVATE', 'REFUND_BY_BANK_TRANSFER_EU'],
                                        'novalnetinvoice'        => [ 'INVOICE_START', 'INVOICE_CREDIT', 'GUARANTEED_INVOICE', 'SUBSCRIPTION_STOP', 'SUBSCRIPTION_REACTIVATE', 'GUARANTEED_INVOICE_BOOKBACK', 'REFUND_BY_BANK_TRANSFER_EU', 'TRANSACTION_CANCELLATION', 'DEBT_COLLECTION_DE', 'CREDIT_ENTRY_DE'],
                                        'novalneteps'            => [ 'EPS', 'REFUND_BY_BANK_TRANSFER_EU', 'ONLINE_TRANSFER_CREDIT', 'REVERSAL', 'DEBT_COLLECTION_DE', 'CREDIT_ENTRY_DE'],
                                        'novalnetgiropay'        => [ 'GIROPAY', 'REFUND_BY_BANK_TRANSFER_EU', 'ONLINE_TRANSFER_CREDIT', 'REVERSAL', 'DEBT_COLLECTION_DE', 'CREDIT_ENTRY_DE'],
                                        'novalnetprzelewy24'     => [ 'PRZELEWY24', 'PRZELEWY24_REFUND'],
                                        'novalnetbarzahlen'      => [ 'CASHPAYMENT', 'CASHPAYMENT_CREDIT', 'CASHPAYMENT_REFUND']
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
        $this->_oNovalnetUtil      = oxNew(NovalnetUtil::class);
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

        if (($sIpAddress != $sIpAllowed) && empty($this->_blProcessTestMode)) {
            $this->_displayMessage('Novalnet callback received. Unauthorized access from the IP [' . $sIpAddress . ']');
            return false;
        }

        $aParamsRequired = (!empty($this->_aCaptureParams['vendor_activation'])) ? $this->aAffParamsRequired : $this->aParamsRequired;
        $this->_aCaptureParams['shop_tid'] = $this->_aCaptureParams['tid'];

        if (in_array($this->_aCaptureParams['payment_type'], array_merge($this->aChargebacks, $this->aCollections))) {
            array_push($aParamsRequired, 'tid_payment');
            $this->_aCaptureParams['shop_tid'] = $this->_aCaptureParams['tid_payment'];
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
        $dAmount           = $this->aOrderDetails['TOTAL_AMOUNT'] - $this->aOrderDetails['REFUND_AMOUNT'];
        $dFormattedAmount  = sprintf('%0.2f', ($this->_aCaptureParams['amount']/100)) . ' ' . $this->_aCaptureParams['currency']; // Formatted callback amount

        $sLineBreak        = '<br><br>';
        $iPaymentTypeLevel = $this->_getPaymentTypeLevel();
        $sPaymentSuccess   = $this->_aCaptureParams['status'] == 100 && $this->_aCaptureParams['tid_status'] == 100;

        if ($iPaymentTypeLevel === 0) {
            if (in_array($this->_aCaptureParams['payment_type'], ['PAYPAL', 'PRZELEWY24']) && $sPaymentSuccess) {
                if (!isset($dPaidAmount)) {
                    $sNovalnetCallbackSql     = 'INSERT INTO novalnet_callback_history (ORDER_NO, AMOUNT, CALLBACK_TID, ORG_TID, CALLBACK_DATE) VALUES ( ?, ?, ?, ?, ? )';
                    $aNovalnetCallbackDetails = [ $this->aOrderDetails['ORDER_NO'], $this->_aCaptureParams['amount'], $this->_aCaptureParams['tid'], $this->_aCaptureParams['tid'], date('Y-m-d H:i:s') ];
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
            } elseif($this->_aCaptureParams['payment_type']=='PRZELEWY24' && !in_array($this->_aCaptureParams['tid_status'], ['86', '100'])) {
                    $sNovalnetComments = 'The transaction has been canceled due to: ' . $this->_oNovalnetUtil->setNovalnetPaygateError($this->_aCaptureParams);
                    $sComments = $sLineBreak . $sNovalnetComments;
                    $this->_oDb->execute('UPDATE oxorder SET NOVALNETCOMMENTS = CONCAT(IF(NOVALNETCOMMENTS IS NULL, "", NOVALNETCOMMENTS), "' . $sComments . '") WHERE OXORDERNR ="' . $this->aOrderDetails['ORDER_NO'] . '"');
                    $this->_oDb->execute('UPDATE novalnet_transaction_detail SET GATEWAY_STATUS = "' . $this->_aCaptureParams['tid_status'] . '" WHERE ORDER_NO ="' . $this->aOrderDetails['ORDER_NO'] . '"');
                    $sNovalnetComments = $this->_sendMail($sNovalnetComments) . $sNovalnetComments;
                    $this->_displayMessage($sNovalnetComments);

            }  elseif (in_array($this->_aCaptureParams['payment_type'], ['INVOICE_START', 'GUARANTEED_INVOICE', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'DIRECT_DEBIT_SEPA']) &&  in_array($this->_aCaptureParams['tid_status'], ['91', '99', '100']) && in_array($this->aOrderDetails['GATEWAY_STATUS'], ['75','91','99'])) {
                     $sNovalnetComments = '';
                     $sMessage = '';
                    if (in_array($this->aOrderDetails['GATEWAY_STATUS'], ['75', '91', '99']) && $this->_aCaptureParams['tid_status'] == '100') {
                        $sNovalnetComments .= '<br>Novalnet callback received. The transaction has been confirmed on '. date('Y-m-d H:i:s');
                    } elseif ($this->aOrderDetails['GATEWAY_STATUS'] == '75' && in_array($this->_aCaptureParams['tid_status'], ['91', '99'])) {
                       $sNovalnetComments .= '<br>Novalnet callback received. The transaction status has been changed from pending to on hold for the TID:'. $this->_aCaptureParams['shop_tid']. ' on '. date('Y-m-d H:i:s').'<br>';
                    }
                    if ($this->_aCaptureParams['tid_status'] == 100) {
                        if ($this->aOrderDetails['OXPAYMENTTYPE'] == 'novalnetinvoice') {
                            $aInvoiceDetails = '<br>' . $this->_getTransactionComments($this->aOrderDetails['OXLANG']);
                            $aInvoiceDetails .= $this->_getReferenceTransaction($this->aOrderDetails['OXLANG'], $this->aOrderDetails['ORDER_NO']);
                            $sMessage .= $aInvoiceDetails;
                            $sSQL = 'UPDATE oxorder SET NOVALNETCOMMENTS = CONCAT(IF(NOVALNETCOMMENTS IS NULL, "", NOVALNETCOMMENTS), "' . $sMessage . '") WHERE OXORDERNR = "' . $this->aOrderDetails['ORDER_NO'] . '"';
                            $this->_oDb->execute($sSQL);

                            $this->_oNovalnetUtil->sendPaymentNotificationMail($this->aOrderDetails['OXLANG'], $sMessage, $this->aOrderDetails['ORDER_NO']);
                        }

                        if ($this->aOrderDetails['OXPAYMENTTYPE'] == 'novalnetsepa' && $this->_aCaptureParams['payment_type'] == 'GUARANTEED_DIRECT_DEBIT_SEPA') {
                            $sMessage = '<br>' . $this->_getTransactionComments($this->aOrderDetails['OXLANG']);
                            $this->_oDb->execute('UPDATE oxorder SET NOVALNETCOMMENTS = CONCAT(IF(NOVALNETCOMMENTS IS NULL, "", NOVALNETCOMMENTS), "' . $sMessage . '") WHERE OXORDERNR ="' . $this->aOrderDetails['ORDER_NO'] . '"');
                        }
                        $dDate = ($this->_aCaptureParams['payment_type'] == 'INVOICE_START') ? '' : date('Y-m-d H:i:s');
                        $this->_oDb->execute('UPDATE oxorder SET OXPAID = "' . $dDate . '" WHERE OXORDERNR ="' . $this->aOrderDetails['ORDER_NO'] . '"');
                    }
                    $this->_oDb->execute('UPDATE novalnet_transaction_detail SET GATEWAY_STATUS =  "' . $this->_aCaptureParams['tid_status'] . '" WHERE ORDER_NO ="' . $this->aOrderDetails['ORDER_NO'] . '"');
                    $sComments = '<br>'. $sNovalnetComments;
                    $this->_oDb->execute('UPDATE oxorder SET NOVALNETCOMMENTS = CONCAT(IF(NOVALNETCOMMENTS IS NULL, "", NOVALNETCOMMENTS), "' . $sComments . '") WHERE OXORDERNR ="' . $this->aOrderDetails['ORDER_NO'] . '"');

                    $sNovalnetComments = $this->_sendMail($sNovalnetComments).$sNovalnetComments;
                    $this->_displayMessage($sNovalnetComments);
            }
            elseif ($this->_aCaptureParams['status'] != '100' || !in_array($this->_aCaptureParams['tid_status'], ['100', '85', '86', '90', '91', '98', '99'])) {
                $this->_displayMessage('Novalnet callback received. Status is not valid');
            } else {
                $this->_displayMessage('Novalnet Callback script received. Payment type ( ' . $this->_aCaptureParams['payment_type'] . ' ) is not applicable for this process!');
            }
        } elseif ($iPaymentTypeLevel == 1 && $sPaymentSuccess) {
            $sNovalnetComments = 'Novalnet callback received. Chargeback executed successfully for the TID: ' . $this->_aCaptureParams['tid_payment'] . ' amount ' . $dFormattedAmount . ' on ' . date('Y-m-d H:i:s') . '. The subsequent TID: ' . $this->_aCaptureParams['tid'];

            if (in_array($this->_aCaptureParams['payment_type'], ['CREDITCARD_BOOKBACK', 'PAYPAL_BOOKBACK', 'PRZELEWY24_REFUND', 'REFUND_BY_BANK_TRANSFER_EU', 'CASHPAYMENT_REFUND', 'GUARANTEED_INVOICE_BOOKBACK', 'GUARANTEED_SEPA_BOOKBACK'])) {
                $sNovalnetComments = 'Novalnet callback received. Refund/Bookback executed successfully for the TID: ' . $this->_aCaptureParams['tid_payment'] . ' amount ' . $dFormattedAmount . ' on ' . date('Y-m-d H:i:s') . '. The subsequent TID: ' . $this->_aCaptureParams['tid'];
            }
            $sComments = $sLineBreak . $sNovalnetComments;
            $sUpdateSql = 'UPDATE oxorder SET NOVALNETCOMMENTS = CONCAT(IF(NOVALNETCOMMENTS IS NULL, "", NOVALNETCOMMENTS), "' . $sComments . '") WHERE OXORDERNR ="' . $this->aOrderDetails['ORDER_NO'] . '"';
            $this->_oDb->execute($sUpdateSql);
            $sNovalnetComments = $this->_sendMail($sNovalnetComments) . $sNovalnetComments;
            $this->_displayMessage($sNovalnetComments);

        } elseif ($iPaymentTypeLevel == 2 && $sPaymentSuccess) {
			if (in_array($this->_aCaptureParams['payment_type'], array('INVOICE_CREDIT','CASHPAYMENT_CREDIT','ONLINE_TRANSFER_CREDIT'))) {
				if (!isset($dPaidAmount) || $dPaidAmount < $dAmount) {
					$dTotalAmount             = $dPaidAmount + $this->_aCaptureParams['amount'];
					$sNovalnetCallbackSql     = 'INSERT INTO novalnet_callback_history (ORDER_NO, AMOUNT, CALLBACK_TID, ORG_TID, CALLBACK_DATE) VALUES ( ?, ?, ?, ?, ? )';
					$aNovalnetCallbackDetails = [ $this->aOrderDetails['ORDER_NO'], $this->_aCaptureParams['amount'], $this->_aCaptureParams['tid'], $this->_aCaptureParams['tid_payment'], date('Y-m-d H:i:s') ];
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
            } else {
				$sNovalnetComments = 'Novalnet Callback Script executed successfully for the TID: ' . $this->_aCaptureParams['tid_payment'] . ' with amount ' . $dFormattedAmount . ' on ' . date('Y-m-d H:i:s') . '. Please refer PAID transaction in our Novalnet Merchant Administration with the TID: ' . $this->_aCaptureParams['tid'];
                $sComments = $sLineBreak . $sNovalnetComments;
                $sUpdateSql = 'UPDATE oxorder SET NOVALNETCOMMENTS = CONCAT(IF(NOVALNETCOMMENTS IS NULL, "", NOVALNETCOMMENTS), "' . $sComments . '") WHERE OXORDERNR ="' . $this->aOrderDetails['ORDER_NO'] . '"';
                $this->_oDb->execute($sUpdateSql);
                $sNovalnetComments = $this->_sendMail($sNovalnetComments) . $sNovalnetComments;
                $this->_displayMessage($sNovalnetComments);
			}           
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
         if(!empty($this->_aCaptureParams['order_no']) && !empty($this->_aCaptureParams['status']) && in_array($this->_aCaptureParams['status'], ['100', '90'])) {
            $sSql = 'SELECT OXPAYMENTTYPE FROM oxorder where OXORDERNR = "'. $this->_aCaptureParams['order_no'] . '"';
            $aResult = $this->_oDb->getRow($sSql);
            if (empty($aResult['OXPAYMENTTYPE']) || strpos($aResult['OXPAYMENTTYPE'], 'novalnet')) {
                list($sSubject, $sMessage) = $this->_NotificationMessage();
                $this->_sendNotifyMail($sSubject, $sMessage);
                $this->_displayMessage($sMessage);
                return false;
            }
        }
        $iOrderNo = !empty($this->_aCaptureParams['order_no']) ? $this->_aCaptureParams['order_no'] : (!empty($this->_aCaptureParams['order_id']) ? $this->_aCaptureParams['order_id'] : '');

        $sSql = 'SELECT OXPAYMENTTYPE, novalnetcomments FROM oxorder where OXORDERNR="'.$iOrderNo.'"';
        $aResult = $this->_oDb->getRow($sSql);
        if (strpos($aPaymentDetails['OXPAYMENTTYPE'], 'novalnet') !== false && empty($aResult['novalnetcomments'])  ) {
           if (! in_array($this->_aCaptureParams['payment_type'], $this->aPaymentGroups[$aPaymentDetails['OXPAYMENTTYPE']])) {
             $this->_displayMessage('Novalnet callback received. Payment Type ['.$this->_aCaptureParams['payment_type'].'] is not valid.');
            }
        }

       $sSql     = 'SELECT trans.ORDER_NO, trans.TOTAL_AMOUNT, trans.REFUND_AMOUNT, trans.PAYMENT_ID, trans.GATEWAY_STATUS, o.OXLANG, o.OXPAYMENTTYPE FROM novalnet_transaction_detail trans JOIN oxorder o ON o.OXORDERNR = trans.ORDER_NO where trans.tid = "' . $this->_aCaptureParams['shop_tid'] . '"';

        $this->aOrderDetails = $this->_oDb->getRow($sSql);

        // Handle transaction cancellation
        if ($this->_transactionCancellation())
            return false;

        // checks the payment type of callback and order
        if (empty($this->aOrderDetails['OXPAYMENTTYPE']) || !in_array($this->_aCaptureParams['payment_type'], $this->aPaymentGroups[$this->aOrderDetails['OXPAYMENTTYPE']])) {
            $this->_displayMessage('Novalnet callback received. Payment Type [' . $this->_aCaptureParams['payment_type'] . '] is not valid');
            return false;
        }

        // checks the order number in shop
        if (empty($this->aOrderDetails['ORDER_NO'])) {
            list($sSubject, $sMessage) = $this->_NotificationMessage();

            // Send E-mail, if transaction not found
            $this->_sendNotifyMail($sSubject, $sMessage);
            $this->_displayMessage($sMessage);

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
     * Build the Notification Message
     *
     * @return array
     */
     private function _NotificationMessage()
     {
        $oConfig   = \OxidEsales\Eshop\Core\Registry::getConfig();
        $this->_oLang   = \OxidEsales\Eshop\Core\Registry::getLang();

        $sShopName = $oConfig->getActiveShop()->oxshops__oxname->rawValue;
        $sSubject  = $this->_oLang->translateString('NOVALNET_CRITICAL_ERROR_MESSAGE1').$sShopName.$this->_oLang->translateString('NOVALNET_CRITICAL_ERROR_MESSAGE2') . $this->_aCaptureParams['shop_tid'];
        $sMessage  =  $this->_oLang->translateString('NOVALNET_CRITICAL_MESSAGE_SUBJECT');
        $sMessage .= $this->_oLang->translateString('NOVALNET_MERCHANT_ID') . $this->_aCaptureParams['vendor_id'] . '<br/>';
        $sMessage .= $this->_oLang->translateString('NOVALNET_PROJECT_ID') . $this->_aCaptureParams['product_id'] . '<br/>';
        $sMessage .= $this->_oLang->translateString('NOVALNET_TID') . $this->_aCaptureParams['shop_tid'] . '<br/>';
        $sMessage .= $this->_oLang->translateString('NOVALNET_TID_STATUS') . $this->_aCaptureParams['tid_status'] . '<br/>';
        $sMessage .= $this->_oLang->translateString('NOVALNET_ORDER_NO') . $this->_aCaptureParams['order_no'] . '<br/>';
        $sMessage .= $this->_oLang->translateString('NOVALNET_PAYMENT_TYPE') . $this->_aCaptureParams['payment_type'] . '<br/>';
        $sMessage .= $this->_oLang->translateString('NOVALNET_EMAIL') . $this->_aCaptureParams['email'] . '<br/>';
        $sMessage .= $this->_oLang->translateString('NOVALNET_REGARDS');

        return [$sSubject, $sMessage];
     }

     /**
     * Build to send notification mail
     *
     * @param string $sEmailSubject
     * @param string $sMessage
     * @return string
     */
     private function _sendNotifyMail($sEmailSubject, $sMessage)
     {
        $oMail = oxNew(\OxidEsales\Eshop\Core\Email::class);
        $oShop = $oMail->getShop();
        $oMail->setFrom($oShop->oxshops__oxorderemail->value);
        $oMail->setSubject($sEmailSubject);
        $oMail->setRecipient($this->sTechnicNotifyMail);
        $oMail->setBody($sMessage);

         if ($oMail->send()){
              return 'Mail sent successfully<br>';
          }
              else{
                  return 'Mail not sent<br>';
              }
    }

    /**
     * Handle TRANSACTION_CANCELLATION payment
     *
     * @return boolean
     *
     */
    private function _transactionCancellation()
    {
         if ($this->_aCaptureParams['payment_type'] == 'TRANSACTION_CANCELLATION' && in_array($this->aOrderDetails['GATEWAY_STATUS'], ['75','85','91','98','99'])) {
           $sNovalnetComments = 'Novalnet callback received. The transaction has been canceled on '.date('Y-m-d H:i:s');

            $sUpdateSql = 'UPDATE novalnet_transaction_detail SET GATEWAY_STATUS =  "' . $this->_aCaptureParams['tid_status'] . '" WHERE ORDER_NO ="' . $this->aOrderDetails['ORDER_NO'] . '"';
            $this->_oDb->execute($sUpdateSql);

            $sComments = '<br><br>'.$sNovalnetComments;

            $sUpdateSql = 'UPDATE oxorder SET NOVALNETCOMMENTS = CONCAT(IF(NOVALNETCOMMENTS IS NULL, "", NOVALNETCOMMENTS), "' . $sComments . '") WHERE OXORDERNR ="' . $this->aOrderDetails['ORDER_NO'] . '"';
            $this->_oDb->execute($sUpdateSql);

            $this->_sendMail($sNovalnetComments);
            $this->_displayMessage($sNovalnetComments);

            return true;
        }

        return false;
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
     * Get Transaction details
     *
     * @param string $sLang
     *
     * @return array
     */
    private function _getTransactionComments($sLang)
    {
        $oConfig = \OxidEsales\Eshop\Core\Registry::getConfig();
        $this->_oLang->setBaseLanguage($sLang);
        $sTransactionComments = '';
        if (in_array($this->_aCaptureParams['payment_type'], ['INVOICE_START', 'GUARANTEED_INVOICE'])) {
            $sTransactionComments .= $this->_oLang->translateString('NOVALNET_TRANSACTION_ID') . $this->_aCaptureParams['shop_tid'];
            $sTransactionComments .= !empty($this->_aCaptureParams['test_mode']) ? $this->_oLang->translateString('NOVALNET_TEST_ORDER') : '';
        }
        return $sTransactionComments;
    }

    /**
     * Get Invoice Reference transaction details
     *
     * @param string  $sLang
     * @param integer $iOrderNo
     *
     * @return array
     */
    private function _getReferenceTransaction($sLang, $iOrderNo)
    {
        $oConfig = \OxidEsales\Eshop\Core\Registry::getConfig();
        $this->_oLang->setBaseLanguage($sLang);
        $sSQL    = 'SELECT ADDITIONAL_DATA FROM novalnet_transaction_detail WHERE ORDER_NO = "' . $iOrderNo . '"';
        $aInvoiceDetails = $this->_oDb->getRow($sSQL);
        $sData = unserialize($aInvoiceDetails['ADDITIONAL_DATA']);
        $sFormattedAmount = $this->_oLang->formatCurrency($this->_aCaptureParams['amount']/100, $oConfig->getCurrencyObject($this->_aCaptureParams['currency'])) . ' ' . $this->_aCaptureParams['currency'];

        $sInvoiceComments = $this->_oLang->translateString('NOVALNET_INVOICE_COMMENTS_TITLE');
        if (!empty($this->_aCaptureParams['due_date'])) {
            $sInvoiceComments .= $this->_oLang->translateString('NOVALNET_DUE_DATE') . $this->_aCaptureParams['due_date'];
        }

        $sInvoiceComments .= $this->_oLang->translateString('NOVALNET_ACCOUNT') . $sData['invoice_account_holder'];
        $sInvoiceComments .= $this->_oLang->translateString('NOVALNET_IBAN') . $sData['invoice_iban'];
        $sInvoiceComments .= $this->_oLang->translateString('NOVALNET_BIC')  . $sData['invoice_bic'];
        $sInvoiceComments .= $this->_oLang->translateString('NOVALNET_BANK') . $sData['invoice_bankname'] . ' ' . $sData['invoice_bankplace'];
        $sInvoiceComments .= $this->_oLang->translateString('NOVALNET_AMOUNT') . $sFormattedAmount;
        $sInvoiceComments .= $this->_oLang->translateString('NOVALNET_INVOICE_MULTI_REF_DESCRIPTION');
        $sInvoiceComments .= $this->_oLang->translateString('NOVALNET_PAYMENT_REFERENCE_1') . $sData['tid'];
        $sInvoiceComments .= $this->_oLang->translateString('NOVALNET_PAYMENT_REFERENCE_2') . $sData['invoice_ref'];

        return $sInvoiceComments;
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
