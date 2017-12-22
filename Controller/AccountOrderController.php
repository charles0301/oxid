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
 * Script : AccountOrderController.php
 *
 */

namespace oe\novalnet\Controller;

use oe\novalnet\Classes\NovalnetUtil;

class AccountOrderController extends AccountOrderController_parent {

    /**
     * Gets Novalnet payment name for given order
     *
     * @param string $sPaymentType
     *
     * @return string
     */
    public function getNovalnetPaymentName($sPaymentType)
    {
        $oPayment = oxNew(\OxidEsales\Eshop\Application\Model\Payment::class);
        $oPayment->load($sPaymentType);
        return $oPayment->oxpayments__oxdesc->rawValue;
    }

    /**
     * Gets Novalnet subscription status for given order
     *
     * @param integer $iOrderNo
     *
     * @return boolean
     */
    public function getNovalnetSubscriptionStatus($iOrderNo)
    {
        $aSubsDetails = $this->_getNovalnetTransDetails($iOrderNo);
        return ($aSubsDetails['GATEWAY_STATUS'] != '103' && !empty($aSubsDetails['SUBS_ID']) && empty($aSubsDetails['TERMINATION_REASON']));
    }

    /**
     * Gets Novalnet transaction details for given order
     *
     * @param integer $iOrderNo
     * @param boolean $blSubsStatus
     *
     * @return array
     */
    private function _getNovalnetTransDetails($iOrderNo, $blSubsStatus = true)
    {
        $this->oDb =  \OxidEsales\Eshop\Core\DatabaseProvider::getDb(\OxidEsales\Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC);

        $sSQL = $blSubsStatus ? 'SELECT trans.GATEWAY_STATUS, subs.SUBS_ID, subs.TERMINATION_REASON FROM novalnet_transaction_detail trans LEFT JOIN novalnet_subscription_detail subs ON trans.ORDER_NO = subs.ORDER_NO WHERE trans.ORDER_NO = "' . $iOrderNo . '"' : 'SELECT VENDOR_ID, PRODUCT_ID, TARIFF_ID, AUTH_CODE, PAYMENT_ID, TID FROM novalnet_transaction_detail WHERE ORDER_NO = "' . $iOrderNo . '"';

        return $this->oDb->getRow($sSQL);
    }

    /**
     * Gets Novalnet subscription cancellation reason
     *
     * @return array
     */
    public function getNovalnetSubsReasons()
    {
        $oLang = \OxidEsales\Eshop\Core\Registry::getLang();
        return array( $oLang->translateString('NOVALNET_SUBSCRIPTION_CANCEL_REASON_1'),
                      $oLang->translateString('NOVALNET_SUBSCRIPTION_CANCEL_REASON_2'),
                      $oLang->translateString('NOVALNET_SUBSCRIPTION_CANCEL_REASON_3'),
                      $oLang->translateString('NOVALNET_SUBSCRIPTION_CANCEL_REASON_4'),
                      $oLang->translateString('NOVALNET_SUBSCRIPTION_CANCEL_REASON_5'),
                      $oLang->translateString('NOVALNET_SUBSCRIPTION_CANCEL_REASON_6'),
                      $oLang->translateString('NOVALNET_SUBSCRIPTION_CANCEL_REASON_7'),
                      $oLang->translateString('NOVALNET_SUBSCRIPTION_CANCEL_REASON_8'),
                      $oLang->translateString('NOVALNET_SUBSCRIPTION_CANCEL_REASON_9'),
                      $oLang->translateString('NOVALNET_SUBSCRIPTION_CANCEL_REASON_10'),
                      $oLang->translateString('NOVALNET_SUBSCRIPTION_CANCEL_REASON_11')
                    );
    }

    /**
     * Cancels Novalnet subscription
     *
     */
    public function cancelNovalnetSuscription()
    {
        $aData = \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestParameter('novalnet');
        $oLang = \OxidEsales\Eshop\Core\Registry::getLang();
        $oNovalnetUtil = oxNew(NovalnetUtil::class);
        $aTransDetails = $this->_getNovalnetTransDetails($aData['iOrderNo'], false);
        if (!empty($aData['sCancelReason'])) {
            $aRequest['vendor']        = $aTransDetails['VENDOR_ID'];
            $aRequest['product']       = $aTransDetails['PRODUCT_ID'];
            $aRequest['tariff']        = $aTransDetails['TARIFF_ID'];
            $aRequest['auth_code']     = $aTransDetails['AUTH_CODE'];
            $aRequest['key']           = $aTransDetails['PAYMENT_ID'];
            $aRequest['tid']           = $aTransDetails['TID'];
            $aRequest['cancel_sub']    = 1;
            $aRequest['remote_ip']     = $oNovalnetUtil->getIpAddress();
            $aRequest['cancel_reason'] = $aData['sCancelReason'];
            $aResponse = $oNovalnetUtil->doCurlRequest($aRequest, 'https://payport.novalnet.de/paygate.jsp');

            if ($aResponse['status'] == '100') {
                $sMessage = $oLang->translateString('NOVALNET_SUBSCRIPTION_CANCELED_MESSAGE') . $aData['sCancelReason'];

                $sSQL = 'UPDATE novalnet_subscription_detail SET TERMINATION_REASON = "' . $aData['sCancelReason'] . '", TERMINATION_AT = "' . date('Y-m-d H:i:s') . '" WHERE ORDER_NO = "' . $aData['iOrderNo'] . '"';
                $this->oDb->execute($sSQL);

                $sSQL = 'UPDATE oxorder SET NOVALNETCOMMENTS = CONCAT(IF(NOVALNETCOMMENTS IS NULL, "", NOVALNETCOMMENTS), "' . $sMessage . '") WHERE OXORDERNR = "' . $aData['iOrderNo'] . '"';
                $this->oDb->execute($sSQL);
            } else {
                $sMessage = $oNovalnetUtil->setNovalnetPaygateError($aResponse);
                echo '<script type="text/javascript">alert("' . $sMessage . '");</script>';
            }
        }
        $oNovalnetUtil->oUtils->redirect($oNovalnetUtil->oConfig->getShopCurrentURL().'cl=account_order', false);
    }

    /**
     * Return shop edition (EE|CE|PE)
     *
     * @return string
     */
    public function getEdition()
    {
        return $this->getConfig()->getEdition();
    }
}
