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

$sLangName = 'English';

$aLang = [
    'charset'                                   => 'UTF-8',
    'MESSAGE_PAYMENT_UNAVAILABLE_PAYMENT_ERROR' => '(Note',

    'NOVALNET_TEST_MODE_MESSAGE'                    => 'The payment will be processed in the test mode therefore amount for this transaction will not be charged',
    'NOVALNET_REDIRECT_DESCRIPTION_MESSAGE'         => 'Please don’t close the browser after successful payment, until you have been redirected back to the Shop',
    'NOVALNET_CC_REDIRECT_DESCRIPTION_MESSAGE'      => 'After the successful verification, you will be redirected to Novalnet secure order page to proceed with the payment<br>Please don’t close the browser after successful payment, until you have been redirected back to the Shop',
    'NOVALNET_PAYPAL_REFERENCE_DESCRIPTION_MESSAGE' => 'Once the order is submitted, the payment will be processed as a reference transaction at Novalnet',
    'NOVALNET_PAYMENT_TYPE'                       => 'Payment with',
    'NOVALNET_LINK_URL'                           => 'http://www.novalnet.com',
    'NOVALNET_CREDITCARD_TYPE'                    => 'Card type',
    'NOVALNET_CREDITCARD_HOLDER_NAME'             => 'Card holder name',
    'NOVALNET_CREDITCARD_NUMBER'                  => 'Card number',
    'NOVALNET_CREDITCARD_EXPIRY_DATE'             => 'Expiry date',
    'NOVALNET_CREDITCARD_CVC'                     => 'CVC/CVV/CID',
    'NOVALNET_CREDITCARD_HOLDER_NAME_PLACEHOLDER' => 'Name on card',
    'NOVALNET_CREDITCARD_NUMBER_PLACEHOLDER'      => 'XXXX XXXX XXXX XXXX',
    'NOVALNET_CREDITCARD_EXPIRY_DATE_PLACEHOLDER' => 'MM / YYYY',
    'NOVALNET_CREDITCARD_CVC_PLACEHOLDER'         => 'XXX',
    'NOVALNET_CREDITCARD_CVC_HINT'                => 'what is this?',
    'NOVALNET_CREDITCARD_ERROR_TEXT'              => 'Your credit card details are invalid',
    'NOVALNET_INVALID_MERCHANT_DETAILS'           => 'Please fill in all the mandatory fields',
    'NOVALNET_BIRTH_DATE'                         => 'Your date of birth',

    'NOVALNET_SEPA_HOLDER_NAME'                 => 'Account holder',
    'NOVALNET_COUNTRY'                          => 'Bank country',
    'NOVALNET_SEPA_IBAN'                        => 'IBAN or Account number',
    'NOVALNET_SEPA_BIC'                         => 'BIC or Bank code',
    'NOVALNET_SEPA_MANDATE_TERMS'               => 'I hereby grant the SEPA direct debit mandate and confirm that the given IBAN and BIC are correct',
    'NOVALNET_SEPA_INVALID_DETAILS'             => 'Your account details are invalid',
    'NOVALNET_SEPA_UNCONFIRM_DETAILS'           => 'Please accept the SEPA direct debit mandate',
    'NOVALNET_SEPA_INVALID_COUNTRY'             => 'Please select the country',

    'NOVALNET_INVOICE_COMMENTS_TITLE'           => '<br><br>Please transfer the amount to the below mentioned account details of our payment processor Novalnet<br>',
    'NOVALNET_DUE_DATE'                         => 'Due date: ',
    'NOVALNET_ACCOUNT'                          => '<br>Account holder: ',
    'NOVALNET_AMOUNT'                           => '<br>Amount: ',
    'NOVALNET_INVOICE_SINGLE_REF_DESCRIPTION'   => '<br>Please use the following payment reference for your money transfer, as only through this way your payment is matched and assigned to the order:',
    'NOVALNET_INVOICE_MULTI_REF_DESCRIPTION'    => '<br>Please use any one of the following references as the payment reference, as only through this way your payment is matched and assigned to the order:',
    'NOVALNET_INVOICE_SINGLE_REFERENCE'         => '<br>Payment Reference: ',
    'NOVALNET_INVOICE_MULTI_REFERENCE'          => '<br>Payment Reference %s: ',
    'NOVALNET_ORDER_NO'                         => 'Order number ',

    'NOVALNET_TRANSACTION_DETAILS'              => 'Novalnet transaction details<br>',
    'NOVALNET_TRANSACTION_ID'                   => 'Novalnet transaction ID: ',
    'NOVALNET_TEST_ORDER'                       => '<br>Test order',
    'NOVALNET_REDIRECT_MESSAGE'                 => 'After the successful verification, you will be redirected to Novalnet secure order page to proceed with the payment',
    'NOVALNET_REDIRECT_SUBMIT'                  => 'Redirecting...',
    'NOVALNET_NEW_CARD_DETAILS'                 => 'Enter new card details',
    'NOVALNET_GIVEN_CARD_DETAILS'               => 'Given card details',
    'NOVALNET_NEW_ACCOUNT_DETAILS'              => 'Enter new account details',
    'NOVALNET_GIVEN_ACCOUNT_DETAILS'            => 'Given account details',
    'NOVALNET_PAYPAL_NEW_ACCOUNT_DETAILS'       => 'Proceed with new PayPal account details',
    'NOVALNET_PAYPAL_GIVEN_ACCOUNT_DETAILS'     => 'Given PayPal account details',
    'NOVALNET_CHECK_HASH_FAILED_ERROR'          => 'While redirecting some data has been changed. The hash check failed',
    'NOVALNET_NOSCRIPT_MESSAGE'                 => 'Please enable the Javascript in your browser to proceed further with the payment',
    'NOVALNET_FRAUD_MODULE_PHONE'               => 'Telephone number',
    'NOVALNET_FRAUD_MODULE_MOBILE'              => 'Mobile number',
    'NOVALNET_FRAUD_MODULE_PIN'                 => 'Transaction PIN',
    'NOVALNET_FRAUD_MODULE_FORGOT_PIN'          => 'Forgot your PIN?',
    'NOVALNET_FRAUD_MODULE_PHONE_INVALID'       => 'Please enter your telephone number',
    'NOVALNET_FRAUD_MODULE_MOBILE_INVALID'      => 'Please enter your mobile number',
    'NOVALNET_FRAUD_MODULE_AMOUNT_CHANGE_ERROR' => 'The order amount has been changed, please proceed with the new order',
    'NOVALNET_FRAUD_MODULE_PIN_EMPTY'           => 'Enter your PIN',
    'NOVALNET_FRAUD_MODULE_PIN_INVALID'         => 'The PIN you entered is incorrect',
    'NOVALNET_FRAUD_MODULE_PHONE_MESSAGE'       => 'You will shortly receive a transaction PIN through phone call to complete the payment',
    'NOVALNET_FRAUD_MODULE_MOBILE_MESSAGE'      => 'You will shortly receive an SMS containing your transaction PIN to complete the payment',
    'NOVALNET_INVALID_BIRTHDATE_ERROR'          => 'You need to be at least 18 years old',
    'NOVALNET_INVALID_DATE_ERROR'               => 'The date format is invalid',
    'NOVALNET_EMPTY_BIRTHDATE_ERROR'            => 'Please enter your date of birth',
    'NOVALNET_GUARANTEE_FORCE_DISABLED_MESSAGE' => 'The payment cannot be processed, because the basic requirements haven’t been met',
    'NOVALNET_PAYPAL_REFERENCE_TID'             => 'PayPal transaction ID',
    'NOVALNET_REFERENCE_TID'                    => 'Novalnet transaction ID',
    'NOVALNET_TEST_MODE_NOTIFICATION_SUBJECT'   => 'Novalnet trial order notification - OXID eShop',
    'NOVALNET_TEST_MODE_NOTIFICATION_MESSAGE'   => 'Dear client, <br><br>We would like to inform you that test order %s has been placed in your shop recently.Please make sure your project is in LIVE mode at Novalnet administration portal and Novalnet payments are enabled in your shop system. Please ignore this email if the order has been placed by you for testing purpose. <br><br>Regards, <br>Novalnet AG',

    'NOVALNET_PLEASE_SELECT'                    => '--Select--',
    'NOVALNET_UPDATE'                           => 'Update',
    'NOVALNET_SUBSCRIPTION_CANCEL_TITLE'        => 'Cancel Subscription Process',
    'NOVALNET_SUBSCRIPTION_CANCEL_LABEL'        => 'Please select reason',
    'NOVALNET_SUBSCRIPTION_CANCEL_REASON_1'     => 'Product is costly',
    'NOVALNET_SUBSCRIPTION_CANCEL_REASON_2'     => 'Cheating',
    'NOVALNET_SUBSCRIPTION_CANCEL_REASON_3'     => 'Partner interfered',
    'NOVALNET_SUBSCRIPTION_CANCEL_REASON_4'     => 'Financial problem',
    'NOVALNET_SUBSCRIPTION_CANCEL_REASON_5'     => 'Content does not match my likes',
    'NOVALNET_SUBSCRIPTION_CANCEL_REASON_6'     => 'Content is not enough',
    'NOVALNET_SUBSCRIPTION_CANCEL_REASON_7'     => 'Interested only for a trial',
    'NOVALNET_SUBSCRIPTION_CANCEL_REASON_8'     => 'Page is very slow',
    'NOVALNET_SUBSCRIPTION_CANCEL_REASON_9'     => 'Satisfied customer',
    'NOVALNET_SUBSCRIPTION_CANCEL_REASON_10'    => 'Logging in problems',
    'NOVALNET_SUBSCRIPTION_CANCEL_REASON_11'    => 'Other',
    'NOVALNET_SUBSCRIPTION_CANCELED_MESSAGE'    => '<br><br>Subscription has been canceled due to: ',
    'NOVALNET_INVALID_CANCEL_REASON'            => 'Please select the reason of subscription cancellation',
    'NOVALNET_CANCEL_REASON'                    => 'Are you sure you want to cancel the subscription?',
    'NOVALNET_DEFAULT_ERROR_MESSAGE'            => 'Payment was not successful. An error occurred',
    'NOVALNET_INVALID_NAME_EMAIL'               => 'Customer name/email fields are not valid',
    'NOVALNET_BARZAHLEN_DUE_DATE'               => '<br>Slip expiry date: ',
    'NOVALNET_BARZAHLEN_PAYMENT_STORE'          => '<br><br>Store(s) near you<br><br>',
    'NOVALNET_PAYMENT_GURANTEE_COMMENTS'        => 'This is processed as a guarantee payment<br>',
    'NOVALNET_REFERENCE_ORDER_NUMBER'           => '<br>Reference Order number: ',
    'NOVALNET_NEXT_CHARGING_DATE'               => 'Next charging date: ',
];
?>
