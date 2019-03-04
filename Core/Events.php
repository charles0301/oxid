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

namespace oe\novalnet\Core;

/**
 * Class Events.
 */
class Events
{
    /**
     * Executes action on activate event
     *
     */
    public static function onActivate()
    {
        $oDbMetaDataHandler = oxNew(\OxidEsales\Eshop\Core\DbMetaDataHandler::class);
        self::addNovalnetTables(); // creates Novalnet tables if not exists
        self::alterNovalnetColumns($oDbMetaDataHandler); // alters shop table and adds new field to manage the Novalnet comments
        self::addNovalnetPaymentMethods(); // inserts Novalnet payment methods

        // to update the smarty tpl file in tmp folder
        $oUtils = \OxidEsales\Eshop\Core\Registry::getUtils();
        $oUtils->resetTemplateCache(['novalnetredirect.tpl']);
        $aFiles = glob($oUtils->getCacheFilePath(null, true) . '*');
        if (is_array($aFiles)) {
            // delete the cached file with tables field name
            $aFiles = preg_grep('/oxorder_allfields_/', $aFiles);
            foreach ($aFiles as $sFile) {
                if (file_exists($sFile))
                    @unlink($sFile);
            }
        }
    }

    /**
     * Executes action on deactivate event
     *
     */
    public static function onDeactivate()
    {
        $oPayment = oxNew(\OxidEsales\Eshop\Application\Model\Payment::class);
        $aPayments = ['novalnetcreditcard', 'novalnetsepa', 'novalnetinvoice', 'novalnetprepayment', 'novalnetonlinetransfer', 'novalnetideal', 'novalnetpaypal', 'novalneteps', 'novalnetgiropay', 'novalnetprzelewy24','novalnetbarzahlen'];

        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $oDb->execute('DELETE FROM oxconfig where OXVARNAME = "aNovalnetConfig"');

        // deactivates the payment while uninstalling our module
        foreach ($aPayments as $aPayment) {
            if ($oPayment->load($aPayment)) {
                $oPayment->oxpayments__oxactive = new \OxidEsales\Eshop\Core\Field(0);
                $oPayment->save();
            }
        }
    }

    /**
     * Add Novalnet column to shop table for storing Novalnet comments
     *
     */
    public static function alterNovalnetColumns($oDbMetaDataHandler)
    {
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();

        if ($oDbMetaDataHandler->fieldExists('PROCESS_KEY', 'novalnet_transaction_detail'))
             $oDb->execute('ALTER TABLE novalnet_transaction_detail DROP VENDOR_ID, DROP PRODUCT_ID, DROP AUTH_CODE, DROP TARIFF_ID, DROP TEST_MODE, DROP PROCESS_KEY, DROP SUBS_ID, DROP STATUS, DROP CURRENCY');
        
        if (!$oDbMetaDataHandler->fieldExists('ADDITIONAL_DATA', 'novalnet_transaction_detail'))
            $oDb->execute('ALTER TABLE novalnet_transaction_detail ADD ADDITIONAL_DATA TEXT DEFAULT NULL COMMENT "Stored Novalnet bank account details"');     
            
        if (!$oDbMetaDataHandler->fieldExists('NOVALNETCOMMENTS', 'oxorder'))
            $oDb->execute('ALTER TABLE oxorder ADD NOVALNETCOMMENTS TEXT');

        if ($oDbMetaDataHandler->fieldExists('DATE', 'novalnet_callback_history'))
            $oDb->execute('ALTER TABLE novalnet_callback_history CHANGE DATE CALLBACK_DATE datetime');

        if ($oDbMetaDataHandler->fieldExists('PRODUCT_ID', 'novalnet_callback_history'))
            $oDb->execute('ALTER TABLE novalnet_callback_history DROP PAYMENT_TYPE, DROP STATUS, DROP CURRENCY, DROP PRODUCT_ID');

        if ($oDbMetaDataHandler->fieldExists('AFF_AUTHCODE', 'novalnet_aff_account_detail'))
            $oDb->execute('ALTER TABLE novalnet_aff_account_detail CHANGE VENDOR_AUTHCODE VENDOR_AUTHCODE varchar(40) COMMENT "Authentication Code",
                            CHANGE AFF_AUTHCODE AFF_AUTHCODE varchar(40) COMMENT "Affiliate Authentication Code"');

    }

    /**
     * Adds Novalnet payment methods
     *
     */
    public static function addNovalnetPaymentMethods()
    {
        $aPayments = [      'novalnetcreditcard'     => ['OXID'          => 'novalnetcreditcard',
                                                                'OXDESC_DE'     => 'Kreditkarte',
                                                                'OXDESC_EN'     => 'Credit Card',
                                                                'OXLONGDESC_DE' => 'Der Betrag wird von Ihrer Kreditkarte abgebucht, sobald die Bestellung abgeschickt wird.',
                                                                'OXLONGDESC_EN' => 'The amount will be debited from your credit card once the order is submitted.'
                                                             ],
                            'novalnetsepa'           => ['OXID'          => 'novalnetsepa',
                                                                'OXDESC_DE'     => 'Lastschrift SEPA',
                                                                'OXDESC_EN'     => 'Direct Debit SEPA',
                                                                'OXLONGDESC_DE' => 'Ihr Konto wird nach Abschicken der Bestellung belastet.',
                                                                'OXLONGDESC_EN' => 'Your account will be debited upon the order submission.'
                                                             ],
                            'novalnetinvoice'        => ['OXID'          => 'novalnetinvoice',
                                                                'OXDESC_DE'     => 'Kauf auf Rechnung',
                                                                'OXDESC_EN'     => 'Invoice',
                                                                'OXLONGDESC_DE' => 'Nachdem Sie die Bestellung abgeschickt haben, erhalten Sie eine Email mit den Bankdaten, um die Zahlung durchzuführen.',
                                                                'OXLONGDESC_EN' => 'Once you\'ve submitted the order, you will receive an e-mail with account details to make payment.'
                                                             ],
                            'novalnetprepayment'     => ['OXID'          => 'novalnetprepayment',
                                                                'OXDESC_DE'     => 'Vorauskasse',
                                                                'OXDESC_EN'     => 'Prepayment',
                                                                'OXLONGDESC_DE' => 'Nachdem Sie die Bestellung abgeschickt haben, erhalten Sie eine Email mit den Bankdaten, um die Zahlung durchzuführen.',
                                                                'OXLONGDESC_EN' => 'Once you\'ve submitted the order, you will receive an e-mail with account details to make payment.'
                                                             ],
                            'novalnetonlinetransfer' => ['OXID'          => 'novalnetonlinetransfer',
                                                                'OXDESC_DE'     => 'Sofort',
                                                                'OXDESC_EN'     => 'Instant Bank Transfer',
                                                                'OXLONGDESC_DE' => 'Nach der erfolgreichen Überprüfung werden Sie auf die abgesicherte Novalnet-Bestellseite umgeleitet, um die Zahlung fortzusetzen.',
                                                                'OXLONGDESC_EN' => 'After the successful verification, you will be redirected to Novalnet secure order page to proceed with the payment.'
                                                             ],
                            'novalnetideal'          => ['OXID'          => 'novalnetideal',
                                                                'OXDESC_DE'     => 'iDEAL',
                                                                'OXDESC_EN'     => 'iDEAL',
                                                                'OXLONGDESC_DE' => 'Nach der erfolgreichen Überprüfung werden Sie auf die abgesicherte Novalnet-Bestellseite umgeleitet, um die Zahlung fortzusetzen.',
                                                                'OXLONGDESC_EN' => 'After the successful verification, you will be redirected to Novalnet secure order page to proceed with the payment.'
                                                             ],
                            'novalnetpaypal'         => ['OXID'          => 'novalnetpaypal',
                                                                'OXDESC_DE'     => 'PayPal',
                                                                'OXDESC_EN'     => 'PayPal',
                                                                'OXLONGDESC_DE' => 'Nach der erfolgreichen Überprüfung werden Sie auf die abgesicherte Novalnet-Bestellseite umgeleitet, um die Zahlung fortzusetzen.',
                                                                'OXLONGDESC_EN' => 'After the successful verification, you will be redirected to Novalnet secure order page to proceed with the payment.'
                                                             ],
                            'novalneteps'            => ['OXID'          => 'novalneteps',
                                                                'OXDESC_DE'     => 'eps',
                                                                'OXDESC_EN'     => 'eps',
                                                                'OXLONGDESC_DE' => 'Nach der erfolgreichen Überprüfung werden Sie auf die abgesicherte Novalnet-Bestellseite umgeleitet, um die Zahlung fortzusetzen.',
                                                                'OXLONGDESC_EN' => 'After the successful verification, you will be redirected to Novalnet secure order page to proceed with the payment.'
                                                             ],
                            'novalnetgiropay'        => ['OXID'          => 'novalnetgiropay',
                                                                'OXDESC_DE'     => 'giropay',
                                                                'OXDESC_EN'     => 'giropay',
                                                                'OXLONGDESC_DE' => 'Nach der erfolgreichen Überprüfung werden Sie auf die abgesicherte Novalnet-Bestellseite umgeleitet, um die Zahlung fortzusetzen.',
                                                                'OXLONGDESC_EN' => 'After the successful verification, you will be redirected to Novalnet secure order page to proceed with the payment.'
                                                             ],
                            'novalnetprzelewy24'     => ['OXID'          => 'novalnetprzelewy24',
                                                                'OXDESC_DE'     => 'Przelewy24',
                                                                'OXDESC_EN'     => 'Przelewy24',
                                                                'OXLONGDESC_DE' => 'Nach der erfolgreichen Überprüfung werden Sie auf die abgesicherte Novalnet-Bestellseite umgeleitet, um die Zahlung fortzusetzen.',
                                                                'OXLONGDESC_EN' => 'After the successful verification, you will be redirected to Novalnet secure order page to proceed with the payment.'
                                                             ],
                            'novalnetbarzahlen'      => ['OXID'          => 'novalnetbarzahlen',
                                                                'OXDESC_DE'     => 'Barzahlen',
                                                                'OXDESC_EN'     => 'Barzahlen',
                                                                'OXLONGDESC_DE' => 'Mit Abschluss der Bestellung bekommen Sie einen Zahlschein angezeigt, den Sie sich ausdrucken oder auf Ihr Handy schicken lassen können. Bezahlen Sie den Online-Einkauf mit Hilfe des Zahlscheins an der Kasse einer Barzahlen-Partnerfiliale.',
                                                                'OXLONGDESC_EN' => 'After completing your order you get a payment slip from Barzahlen that you can easily print out or have it sent via SMS to your mobile phone. With the help of that payment slip you can pay your online purchase at one of our retail partners (e.g. supermarket).'
                                                             ]
                            ];
        $oLangArray = \OxidEsales\Eshop\Core\Registry::getLang()->getLanguageArray();
        $oPayment = oxNew(\OxidEsales\Eshop\Application\Model\Payment::class);
        foreach ($oLangArray as $oLang) {
            foreach ($aPayments as $aPayment) {
                $oPayment->setId($aPayment['OXID']);
                $oPayment->setLanguage($oLang->id);
                $sLangAbbr = in_array($oLang->abbr, ['de', 'en']) ? $oLang->abbr : 'en';
                $oPayment->oxpayments__oxid          = new \OxidEsales\Eshop\Core\Field($aPayment['OXID']);
                $oPayment->oxpayments__oxaddsumrules = new \OxidEsales\Eshop\Core\Field('31');
                $oPayment->oxpayments__oxtoamount    = new \OxidEsales\Eshop\Core\Field('1000000');
                $oPayment->oxpayments__oxtspaymentid = new \OxidEsales\Eshop\Core\Field('');
                $oPayment->oxpayments__oxdesc     = new \OxidEsales\Eshop\Core\Field($aPayment['OXDESC_'. strtoupper($sLangAbbr)]);
                $oPayment->oxpayments__oxlongdesc = new \OxidEsales\Eshop\Core\Field($aPayment['OXLONGDESC_'. strtoupper($sLangAbbr)]);
                $oPayment->save();
            }
        }
        unset( $oPayment);
    }

    /**
     * Executes queries for creating Novalnet payments
     *
     */
    public static function addNovalnetTables()
    {
        $oDb  = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $sSql = 'CREATE TABLE IF NOT EXISTS novalnet_callback_history (
                ID int(11) unsigned AUTO_INCREMENT COMMENT "Auto increment ID",               
                ORDER_NO int(11) unsigned COMMENT "Order number in shop",
                AMOUNT int(11) DEFAULT NULL COMMENT "Amount in cents",               
                CALLBACK_TID bigint(20) DEFAULT NULL COMMENT "Callback reference ID",
                ORG_TID bigint(20) unsigned DEFAULT NULL COMMENT "Original transaction ID",                
                CALLBACK_DATE datetime COMMENT "Callback DATE TIME",
                PRIMARY KEY (ID),
                KEY ORDER_NO (ORDER_NO)
                ) COMMENT="Novalnet Callback History"';
        $oDb->execute($sSql);

        $sSql = 'CREATE TABLE IF NOT EXISTS novalnet_transaction_detail (
                ID int(11) unsigned AUTO_INCREMENT COMMENT "Auto increment ID",               
                TID bigint(20) COMMENT "Novalnet transaction reference ID",
                ORDER_NO int(11) unsigned COMMENT "Order ID from shop",               
                PAYMENT_ID int(11) unsigned COMMENT "Payment ID",
                PAYMENT_TYPE varchar(30) COMMENT "Executed payment type of this order",
                AMOUNT int(11) DEFAULT "0" COMMENT "Transaction amount",
                GATEWAY_STATUS varchar(9) NULL COMMENT "Novalnet transaction status",
                CUSTOMER_ID int(11) unsigned DEFAULT NULL COMMENT "Customer ID from shop",
                ORDER_DATE datetime COMMENT "Transaction Date for reference",
                REFUND_AMOUNT int(11) DEFAULT "0" COMMENT "Refund amount",
                TOTAL_AMOUNT int(11) DEFAULT "0" COMMENT "Customer refund the amount",                
                MASKED_DETAILS TEXT DEFAULT NULL COMMENT "Masked account details of customer",
                ZERO_TRXNDETAILS TEXT DEFAULT NULL COMMENT "Zero amount transaction details",
                ZERO_TRXNREFERENCE bigint(20) DEFAULT NULL COMMENT "Zero transaction TID",
                ZERO_TRANSACTION ENUM("0", "1") DEFAULT "0" COMMENT "Notify the zero amount order",
                REFERENCE_TRANSACTION ENUM("0", "1") DEFAULT "0" COMMENT "Notify the referenced order",
                ADDITIONAL_DATA TEXT DEFAULT NULL COMMENT "Stored Novalnet bank account details",
                PRIMARY KEY (ID),
                KEY TID (TID),
                KEY ORDER_NO (ORDER_NO)
                ) COMMENT="Novalnet Transaction History"';
        $oDb->execute($sSql);

        $sSql = 'CREATE TABLE IF NOT EXISTS novalnet_aff_account_detail (
                ID int(11) unsigned AUTO_INCREMENT COMMENT "Auto increment ID",
                VENDOR_ID int(11) unsigned COMMENT "Vendor ID",
                VENDOR_AUTHCODE varchar(40) COMMENT "Authorisation ID",
                PRODUCT_ID int(11) unsigned COMMENT "Project ID",
                PRODUCT_URL varchar(200) DEFAULT NULL COMMENT "Product URL",
                ACTIVATION_DATE datetime DEFAULT NULL COMMENT "Affiliate activation date",
                AFF_ID int(11) unsigned COMMENT "Affiliate vendor ID",
                AFF_AUTHCODE varchar(40) COMMENT "Affiliate authorisation ID",
                AFF_ACCESSKEY varchar(40) COMMENT "Affiliate access Key",
                PRIMARY KEY (ID),
                KEY AFF_ID (AFF_ID)
                ) COMMENT="Novalnet merchant / affiliate account information"';
        $oDb->execute($sSql);

        $sSql = 'CREATE TABLE IF NOT EXISTS novalnet_aff_user_detail (
                ID int(11) unsigned AUTO_INCREMENT COMMENT "Auto increment ID",
                AFF_ID int(11) unsigned COMMENT "Affiliate vendor ID",
                CUSTOMER_ID int(11) unsigned COMMENT "Affiliate customer ID",
                AFF_ORDER_NO int(11) unsigned COMMENT "Affiliate order Number",
                PRIMARY KEY (ID),
                KEY CUSTOMER_ID (CUSTOMER_ID),
                KEY AFF_ORDER_NO (AFF_ORDER_NO)
                ) COMMENT="Novalnet merchant / affiliate user details"';
        $oDb->execute($sSql);
    }  
}
?>
