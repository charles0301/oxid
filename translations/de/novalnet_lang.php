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
 * Script : novalnet_lang.php
 *
 */

$sLangName = 'Deutsch';

$aLang = [
    'charset'                                   => 'UTF-8',
    'MESSAGE_PAYMENT_UNAVAILABLE_PAYMENT_ERROR' => '(Beachten',

    'NOVALNET_TEST_MODE_MESSAGE'                    => 'Die Zahlung wird im Testmodus durchgeführt, daher wird der Betrag für diese Transaktion nicht eingezogen',
    'NOVALNET_REDIRECT_DESCRIPTION_MESSAGE'         => 'Bitte schließen Sie den Browser nach der erfolgreichen Zahlung nicht, bis Sie zum Shop zurückgeleitet wurden',
    'NOVALNET_CC_REDIRECT_DESCRIPTION_MESSAGE'      => 'Nach der erfolgreichen Überprüfung werden Sie auf die abgesicherte Novalnet-Bestellseite umgeleitet, um die Zahlung fortzusetzen<br>Bitte schließen Sie den Browser nach der erfolgreichen Zahlung nicht, bis Sie zum Shop zurückgeleitet wurden',
    'NOVALNET_PAYPAL_REFERENCE_DESCRIPTION_MESSAGE' => 'Sobald die Bestellung abgeschickt wurde, wird die Zahlung bei Novalnet als Referenztransaktion verarbeitet',
    'NOVALNET_PAYMENT_TYPE'                       => 'Bezahlung mit',
    'NOVALNET_LINK_URL'                           => 'https://www.novalnet.de',
    'NOVALNET_CREDITCARD_TYPE'                    => 'Kartentyp',
    'NOVALNET_CREDITCARD_HOLDER_NAME'             => 'Name des Karteninhabers',
    'NOVALNET_CREDITCARD_NUMBER'                  => 'Kreditkartennummer',
    'NOVALNET_CREDITCARD_EXPIRY_DATE'             => 'Ablaufdatum',
    'NOVALNET_CREDITCARD_CVC'                     => 'CVC/CVV/CID',
    'NOVALNET_CREDITCARD_HOLDER_NAME_PLACEHOLDER' => 'Name auf der Kreditkarte',
    'NOVALNET_CREDITCARD_NUMBER_PLACEHOLDER'      => 'XXXX XXXX XXXX XXXX',
    'NOVALNET_CREDITCARD_EXPIRY_DATE_PLACEHOLDER' => 'MM / YYYY',
    'NOVALNET_CREDITCARD_CVC_PLACEHOLDER'         => 'XXX',
    'NOVALNET_CREDITCARD_CVC_HINT'                => 'Was ist das?',
    'NOVALNET_CREDITCARD_ERROR_TEXT'              => 'Ihre Kreditkartendaten sind ungültig',
    'NOVALNET_INVALID_MERCHANT_DETAILS'           => 'Füllen Sie bitte alle Pflichtfelder aus',
    'NOVALNET_BIRTH_DATE'                         => 'Ihr Geburtsdatum',

    'NOVALNET_SEPA_HOLDER_NAME'                 => 'Kontoinhaber',
    'NOVALNET_COUNTRY'                          => 'Land der Bank',
    'NOVALNET_SEPA_IBAN'                        => 'IBAN oder Kontonummer',
    'NOVALNET_SEPA_BIC'                         => 'BIC oder Bankleitzahl',
    'NOVALNET_SEPA_MANDATE_TERMS'               => 'Hiermit erteile ich das SEPA-Lastschriftmandat und bestätige, dass die angegebene IBAN und BIC korrekt sind',
    'NOVALNET_SEPA_INVALID_DETAILS'             => 'Ihre Kontodaten sind ungültig',
    'NOVALNET_SEPA_UNCONFIRM_DETAILS'           => 'Akzeptieren Sie bitte das SEPA-Lastschriftmandat',
    'NOVALNET_SEPA_INVALID_COUNTRY'             => 'Wählen Sie bitte ein Land aus',

    'NOVALNET_INVOICE_COMMENTS_TITLE'           => '<br><br>Überweisen Sie bitte den Betrag an die unten aufgeführte Bankverbindung unseres Zahlungsdienstleisters Novalnet<br>',
    'NOVALNET_DUE_DATE'                         => 'Fälligkeitsdatum: ',
    'NOVALNET_ACCOUNT'                          => '<br>Kontoinhaber: ',
    'NOVALNET_AMOUNT'                           => '<br>Betrag: ',
    'NOVALNET_INVOICE_SINGLE_REF_DESCRIPTION'   => '<br>Bitte verwenden Sie nun der unten angegebenen Verwendungszweck für die Überweisung, da nur so Ihr Geldeingang zugeordnet werden kann:',
    'NOVALNET_INVOICE_MULTI_REF_DESCRIPTION'    => '<br>Bitte verwenden Sie einen der unten angegebenen Verwendungszwecke für die Überweisung, da nur so Ihr Geldeingang zugeordnet werden kann:',
    'NOVALNET_INVOICE_SINGLE_REFERENCE'         => '<br>Verwendungszweck: ',
    'NOVALNET_INVOICE_MULTI_REFERENCE'          => '<br>%s. Verwendungszweck: ',
    'NOVALNET_ORDER_NO'                         => 'Bestellnummer ',

    'NOVALNET_TRANSACTION_DETAILS'              => 'Novalnet-Transaktionsdetails<br>',
    'NOVALNET_TRANSACTION_ID'                   => 'Novalnet Transaktions-ID: ',
    'NOVALNET_TEST_ORDER'                       => '<br>Testbestellung',
    'NOVALNET_REDIRECT_MESSAGE'                 => 'Nach der erfolgreichen Überprüfung werden Sie auf die abgesicherte Novalnet-Bestellseite umgeleitet, um die Zahlung fortzusetzen',
    'NOVALNET_REDIRECT_SUBMIT'                  => 'Weiterleitung...',
    'NOVALNET_NEW_CARD_DETAILS'                 => 'Neue Kartendaten eingeben',
    'NOVALNET_GIVEN_CARD_DETAILS'               => 'Eingegebene Kartendaten',
    'NOVALNET_NEW_ACCOUNT_DETAILS'              => 'Neue Kontodaten eingeben',
    'NOVALNET_GIVEN_ACCOUNT_DETAILS'            => 'Eingegebene Kontodaten',
    'NOVALNET_PAYPAL_NEW_ACCOUNT_DETAILS'       => 'Mit neuen PayPal-Kontodetails fortfahren',
    'NOVALNET_PAYPAL_GIVEN_ACCOUNT_DETAILS'     => 'Angegebene PayPal-Kontodetails',
    'NOVALNET_CHECK_HASH_FAILED_ERROR'          => 'Während der Umleitung wurden einige Daten geändert. Die Überprüfung des Hashes schlug fehl',
    'NOVALNET_NOSCRIPT_MESSAGE'                 => 'Aktivieren Sie bitte JavaScript in Ihrem Browser, um die Zahlung fortzusetzen',
    'NOVALNET_FRAUD_MODULE_PHONE'               => 'Telefonnummer',
    'NOVALNET_FRAUD_MODULE_MOBILE'              => 'Mobiltelefonnummer',
    'NOVALNET_FRAUD_MODULE_PIN'                 => 'PIN zu Ihrer Transaktion',
    'NOVALNET_FRAUD_MODULE_FORGOT_PIN'          => 'PIN vergessen?',
    'NOVALNET_FRAUD_MODULE_PHONE_INVALID'       => 'Geben Sie bitte Ihre Telefonnummer ein',
    'NOVALNET_FRAUD_MODULE_MOBILE_INVALID'      => 'Geben Sie bitte Ihre Mobiltelefonnummer ein',
    'NOVALNET_FRAUD_MODULE_AMOUNT_CHANGE_ERROR' => 'Der Bestellbetrag hat sich geändert, setzen Sie bitte die neue Bestellung fort',
    'NOVALNET_FRAUD_MODULE_PIN_EMPTY'           => 'PIN eingeben',
    'NOVALNET_FRAUD_MODULE_PIN_INVALID'         => 'Die von Ihnen eingegebene PIN ist falsch',
    'NOVALNET_FRAUD_MODULE_PHONE_MESSAGE'       => 'In Kürze erhalten Sie einen Telefonanruf mit der PIN zu Ihrer Transaktion, um die Zahlung abzuschließen',
    'NOVALNET_FRAUD_MODULE_MOBILE_MESSAGE'      => 'In Kürze erhalten Sie eine SMS mit der PIN zu Ihrer Transaktion, um die Zahlung abzuschließen',
    'NOVALNET_INVALID_BIRTHDATE_ERROR'          => 'Sie müssen mindestens 18 Jahre alt sein',
    'NOVALNET_INVALID_DATE_ERROR'               => 'Ungültiges Datumsformat',
    'NOVALNET_EMPTY_BIRTHDATE_ERROR'            => 'Geben Sie bitte Ihr Geburtsdatum ein',
    'NOVALNET_GUARANTEE_FORCE_DISABLED_MESSAGE' => 'Die Zahlung kann nicht verarbeitet werden, weil die grundlegenden Anforderungen nicht erfüllt wurden',
    'NOVALNET_PAYPAL_REFERENCE_TID'             => 'PayPal Transaktions-ID',
    'NOVALNET_REFERENCE_TID'                    => 'Novalnet Transaktions-ID',
    'NOVALNET_TEST_MODE_NOTIFICATION_SUBJECT'   => 'Benachrichtigung zu Novalnet-Testbestellungen - OXID eShop',
    'NOVALNET_TEST_MODE_NOTIFICATION_MESSAGE'   => 'Sehr geehrte Kundin, <br><br>wir möchten Sie darüber informieren, dass eine Testbestellung %s kürzlich in Ihrem Shop durchgeführt wurde. Stellen Sie bitte sicher, dass für Ihr Projekt im Novalnet-Administrationsportal der Live-Modus gesetzt wurde und Zahlungen über Novalnet in Ihrem Shopsystem aktiviert sind. Ignorieren Sie bitte diese E-Mail, falls die Bestellung von Ihnen zu Testzwecken durchgeführt wurde. <br><br>Mit freundlichen Grüßen <br>Novalnet AG',

    'NOVALNET_PLEASE_SELECT'                    => '--Auswählen--',
    'NOVALNET_UPDATE'                           => 'Ändern',
    'NOVALNET_SUBSCRIPTION_CANCEL_TITLE'        => 'Stornierung von Abonnements',
    'NOVALNET_SUBSCRIPTION_CANCEL_LABEL'        => 'Wählen Sie bitte den Grund aus',
    'NOVALNET_SUBSCRIPTION_CANCEL_REASON_1'     => 'Angebot zu teuer',
    'NOVALNET_SUBSCRIPTION_CANCEL_REASON_2'     => 'Betrug',
    'NOVALNET_SUBSCRIPTION_CANCEL_REASON_3'     => '(Ehe-)Partner hat Einspruch eingelegt',
    'NOVALNET_SUBSCRIPTION_CANCEL_REASON_4'     => 'Finanzielle Schwierigkeiten',
    'NOVALNET_SUBSCRIPTION_CANCEL_REASON_5'     => 'Inhalt entsprach nicht meinen Vorstellungen',
    'NOVALNET_SUBSCRIPTION_CANCEL_REASON_6'     => 'Inhalte nicht ausreichend',
    'NOVALNET_SUBSCRIPTION_CANCEL_REASON_7'     => 'Nur an Probezugang interessiert',
    'NOVALNET_SUBSCRIPTION_CANCEL_REASON_8'     => 'Seite zu langsam',
    'NOVALNET_SUBSCRIPTION_CANCEL_REASON_9'     => 'Zufriedener Kunde',
    'NOVALNET_SUBSCRIPTION_CANCEL_REASON_10'    => 'Zugangsprobleme',
    'NOVALNET_SUBSCRIPTION_CANCEL_REASON_11'    => 'Sonstige',
    'NOVALNET_SUBSCRIPTION_CANCELED_MESSAGE'    => '<br><br>Das Abonnement wurde gekündigt wegen: ',
    'NOVALNET_INVALID_CANCEL_REASON'            => 'Wählen Sie bitte den Grund für die Abonnementskündigung aus',
    'NOVALNET_CANCEL_REASON'                    => 'Sind Sie sicher, dass Sie das Abonnement kündigen wollen?',
    'NOVALNET_DEFAULT_ERROR_MESSAGE'            => 'Die Zahlung war nicht erfolgreich. Ein Fehler trat auf',
    'NOVALNET_INVALID_NAME_EMAIL'               => 'Ungültige Werte für die Felder Kundenname-/email',
    'NOVALNET_BARZAHLEN_DUE_DATE'               => '<br>Verfallsdatum des Zahlscheins: ',
    'NOVALNET_BARZAHLEN_PAYMENT_STORE'          => '<br><br>Barzahlen-Partnerfiliale in Ihrer Nähe<br><br>',
    'NOVALNET_PAYMENT_GURANTEE_COMMENTS'        => 'Diese Transaktion wird mit Zahlungsgarantie verarbeitet<br>',
    'NOVALNET_REFERENCE_ORDER_NUMBER'           => '<br>Verwendungszweck Bestellnummer: ',
    'NOVALNET_NEXT_CHARGING_DATE'               => 'Nächstes Belastungsdatum: ',
];
?>
