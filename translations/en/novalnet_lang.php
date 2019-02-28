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

$sLangName = 'English';

$aLang = [
    'charset'                                       	=> 'UTF-8',

    'NOVALNET_TEST_MODE_MESSAGE'                    	=> 'The payment will be processed in the test mode therefore amount for this transaction will not be charged.',
    'NOVALNET_REDIRECT_DESCRIPTION_MESSAGE'         	=> 'Please don’t close the browser after successful payment, until you have been redirected back to the Shop.',
    'NOVALNET_CC_REDIRECT_DESCRIPTION_MESSAGE'      	=> 'After the successful verification, you will be redirected to Novalnet secure order page to proceed with the payment<br>Please don’t close the browser after successful payment, until you have been redirected back to the Shop.',
    'NOVALNET_PAYPAL_REFERENCE_DESCRIPTION_MESSAGE' 	=> 'Once the order is submitted, the payment will be processed as a reference transaction at Novalnet.',
    'NOVALNET_PAYMENT_TYPE'                       		=> 'Payment with',
    'NOVALNET_LINK_URL'                           		=> 'http://www.novalnet.com',
    'NOVALNET_CREDITCARD_TYPE'                    		=> 'Card type',
    'NOVALNET_CREDITCARD_HOLDER_NAME'             		=> 'Card holder name',
    'NOVALNET_CREDITCARD_NUMBER'                  		=> 'Card number',
    'NOVALNET_CREDITCARD_EXPIRY_DATE'             		=> 'Expiry date',
    'NOVALNET_CREDITCARD_CVC'                     		=> 'CVC/CVV/CID',
    'NOVALNET_CREDITCARD_HOLDER_NAME_PLACEHOLDER' 		=> 'Name on card',
    'NOVALNET_CREDITCARD_NUMBER_PLACEHOLDER'      		=> 'XXXX XXXX XXXX XXXX',
    'NOVALNET_CREDITCARD_EXPIRY_DATE_PLACEHOLDER' 		=> 'MM / YYYY',
    'NOVALNET_CREDITCARD_CVC_PLACEHOLDER'         		=> 'XXX',
    'NOVALNET_CREDITCARD_CVC_HINT'                		=> 'what is this?',
    'NOVALNET_CREDITCARD_ERROR_TEXT'              		=> 'Your credit card details are invalid',
    'NOVALNET_BIRTH_DATE'                         		=> 'Your date of birth',

    'NOVALNET_SEPA_HOLDER_NAME'                 		=> 'Account holder',
    'NOVALNET_SEPA_IBAN'                        		=> 'IBAN',
    
    'NOVALNET_INVOICE_COMMENTS_TITLE'           		=> '<br><br>Please transfer the amount to the below mentioned account details of our payment processor Novalnet<br>',
    'NOVALNET_DUE_DATE'                         		=> '<br>Due date: ',
    'NOVALNET_ACCOUNT'                          		=> '<br>Account holder: ',
    'NOVALNET_AMOUNT'                           		=> '<br>Amount: ',
    'NOVALNET_INVOICE_SINGLE_REF_DESCRIPTION'   		=> '<br>Please use the following payment reference for your money transfer, as only through this way your payment is matched and assigned to the order:',
    'NOVALNET_INVOICE_MULTI_REF_DESCRIPTION'    		=> '<br>Please use any one of the following references as the payment reference, as only through this way your payment is matched and assigned to the order:',
    'NOVALNET_INVOICE_SINGLE_REFERENCE'         		=> '<br>Payment Reference: ',
    'NOVALNET_INVOICE_MULTI_REFERENCE'          		=> '<br>Payment Reference %s: ',
    'NOVALNET_ORDER_NO'                         		=> 'Order number ',

    'NOVALNET_TRANSACTION_DETAILS'              		=> 'Novalnet transaction details<br>',
    'NOVALNET_TRANSACTION_ID'                   		=> 'Novalnet transaction ID: ',
    'NOVALNET_TEST_ORDER'                       		=> '<br>Test order',
    'NOVALNET_REDIRECT_MESSAGE'                 		=> 'After the successful verification, you will be redirected to Novalnet secure order page to proceed with the payment',
    'NOVALNET_REDIRECT_SUBMIT'                  		=> 'Redirecting...',
    'NOVALNET_NEW_CARD_DETAILS'                 		=> 'Enter new card details',
    'NOVALNET_GIVEN_CARD_DETAILS'               		=> 'Given card details',
    'NOVALNET_NEW_ACCOUNT_DETAILS'              		=> 'Enter new account details',
    'NOVALNET_GIVEN_ACCOUNT_DETAILS'            		=> 'Given account details',
    'NOVALNET_PAYPAL_NEW_ACCOUNT_DETAILS'       		=> 'Proceed with new PayPal account details',
    'NOVALNET_PAYPAL_GIVEN_ACCOUNT_DETAILS'     		=> 'Given PayPal account details',
    'NOVALNET_CHECK_HASH_FAILED_ERROR'          		=> 'While redirecting some data has been changed. The hash check failed',
    'NOVALNET_NOSCRIPT_MESSAGE'                 		=> 'Please enable the Javascript in your browser to proceed further with the payment',
    'NOVALNET_FRAUD_MODULE_PHONE'               		=> 'Telephone number',
    'NOVALNET_FRAUD_MODULE_MOBILE'              		=> 'Mobile number',
    'NOVALNET_FRAUD_MODULE_PIN'                 		=> 'Transaction PIN',
    'NOVALNET_FRAUD_MODULE_FORGOT_PIN'          		=> 'Forgot your PIN?',
    'NOVALNET_FRAUD_MODULE_PHONE_INVALID'       		=> 'Please enter your telephone number',
    'NOVALNET_FRAUD_MODULE_MOBILE_INVALID'      		=> 'Please enter your mobile number',
    'NOVALNET_FRAUD_MODULE_AMOUNT_CHANGE_ERROR' 		=> 'The order amount has been changed, please proceed with the new order',
    'NOVALNET_FRAUD_MODULE_PIN_EMPTY'           		=> 'Enter your PIN',
    'NOVALNET_FRAUD_MODULE_PIN_INVALID'         		=> 'The PIN you entered is incorrect',
    'NOVALNET_FRAUD_MODULE_PHONE_MESSAGE'       		=> 'You will shortly receive a transaction PIN through phone call to complete the payment',
    'NOVALNET_FRAUD_MODULE_MOBILE_MESSAGE'      		=> 'You will shortly receive an SMS containing your transaction PIN to complete the payment',
    'NOVALNET_INVALID_BIRTHDATE_ERROR'          		=> 'You need to be at least 18 years old',
    'NOVALNET_INVALID_DATE_ERROR'               		=> 'The date format is invalid',
    'NOVALNET_EMPTY_BIRTHDATE_ERROR'            		=> 'Please enter your date of birth',
    'NOVALNET_PAYPAL_REFERENCE_TID'             		=> 'PayPal transaction ID',
    'NOVALNET_REFERENCE_TID'                    		=> 'Novalnet transaction ID',
    'NOVALNET_TEST_MODE_NOTIFICATION_SUBJECT'   		=> 'Novalnet trial order notification - OXID eShop',
    'NOVALNET_TEST_MODE_NOTIFICATION_MESSAGE'   		=> 'Dear client, <br><br>We would like to inform you that test order %s has been placed in your shop recently.Please make sure your project is in LIVE mode at Novalnet administration portal and Novalnet payments are enabled in your shop system. Please ignore this email if the order has been placed by you for testing purpose. <br><br>Regards, <br>Novalnet AG',

    'NOVALNET_PLEASE_SELECT'                    		=> '--Select--',
    'NOVALNET_UPDATE'                           		=> 'Update',
    'NOVALNET_DEFAULT_ERROR_MESSAGE'            		=> 'Payment was not successful. An error occurred',
    'NOVALNET_INVALID_NAME_EMAIL'               		=> 'Customer name/email fields are not valid',
    'NOVALNET_BARZAHLEN_DUE_DATE'               		=> '<br>Slip expiry date: ',
    'NOVALNET_BARZAHLEN_PAYMENT_STORE'          		=> '<br><br>Store(s) near you<br><br>',
    'NOVALNET_BARZAHLEN_BUTTON'                 		=> 'Pay now with Barzahlen',
    'NOVALNET_PAYMENT_GUARANTEE_COMMENTS'       		=> 'This is processed as a guarantee payment<br>',
    'NOVALNET_REFERENCE_ORDER_NUMBER'           		=> '<br>Reference Order number: ',
    'NOVALNET_NEXT_CHARGING_DATE'               		=> 'Next charging date: ',
    'NOVALNET_SAVE_CARD_DATA'                   		=> 'Save my card details for future purchases',
    'NOVALNET_SEPA_INVALID_DETAILS'             		=> 'Your account details are invalid',
    'NOVALNET_GUARANTEE_FORCE_DISABLED_MESSAGE' 		=> 'The payment cannot be processed, because the basic requirements haven’t been met',
    
    'NOVALNET_GUARANTEE_AMOUNT_ERROR_MESSAGE'           => 'Minimum order amount should be ',
    'NOVALNET_GUARANTEE_CURRENCY_ERROR_MESSAGE'         => 'Only EUR currency allowed',
    'NOVALNET_GUARANTEE_ADDRESS_MISMATCH_ERROR_MESSAGE' => 'The shipping address should be the same as the billing address',
    'NOVALNET_GUARANTEE_COUNTRY_ERROR_MESSAGE'          => 'Only DACH countries allowed',
    
    'NOVALNET_GUARANTEE_PAYMENT_COMMENTS_DESCRIPTION'	=> '<br><br>Your order is under verification and once confirmed, we will send you our bank details to where the order amount should be transferred. Please note that this may take upto 24 hours.',
    
    'NOVALNET_ORDER_CONFIRMATION'                		=> 'Order Confirmation - Your Order ',
    'NOVALNET_ORDER_CONFIRMATION1'               		=> ' with ',
    'NOVALNET_ORDER_CONFIRMATION2'               		=> ' has been confirmed',
    'NOVALNET_ORDER_CONFIRMATION3'               		=> 'We are pleased to inform you that your order has been confirmed',
    'NOVALNET_PAYMENT_INFORMATION'               		=> 'Payment Information:',
    
    'NOVALNET_IBAN'				 						=> '<br>IBAN: ',
    'NOVALNET_BIC'				 						=> '<br>BIC: ',
    'NOVALNET_BANK'				 						=> '<br>Bank: ',
    'NOVALNET_PAYMENT_REFERENCE_1' 						=> '<br>Payment Reference 1: ',
    'NOVALNET_PAYMENT_REFERENCE_2'						=> '<br>Payment Reference 2: ',
    
    'NOVALNET_ZEROAMOUNT_BOOKING_MESSAGE'        		=>'This order will be processed as zero amount booking which store your payment data for further online purchases',
    
    'NOVALNET_CRITICAL_ERROR_MESSAGE1' 			 		=> 'Critical error on shop system ',
    'NOVALNET_CRITICAL_ERROR_MESSAGE2'           		=> ' : order not found for TID: ',
    'NOVALNET_CRITICAL_MESSAGE_SUBJECT'          		=> 'Dear Technic team,<br/><br/>Please evaluate this transaction and contact our payment module team at Novalnet.<br/><br/>',
    'NOVALNET_MERCHANT_ID'                       		=> 'Merchant ID: ',
    'NOVALNET_PROJECT_ID'                        		=> 'Project ID: ',
    'NOVALNET_TID'                               		=> 'TID: ',
    'NOVALNET_TID_STATUS'                        		=> 'TID status: ' ,
    'NOVALNET_ORDER_NO'                          		=> 'Order no: ',
    'NOVALNET_PAYMENT_TYPE'                      		=> 'Payment type: ','NOVALNET_GUARANTEE_TEXT'                   => '<br><br>Your order is under verification and once confirmed, we will send you our bank details to where the order amount should be transferred. Please note that this may take upto 24 hours<br>',
    'NOVALNET_EMAIL'                             		=> 'E-mail: ',
    'NOVALNET_REGARDS'                           		=> '<br/><br/>Regards,<br/>Novalnet Team',   
    
    'NOVALNET_SEPA_DECLARATION'                  		=> '<strong>I hereby grant the mandate for the SEPA direct debit (electronic transmission) and confirm that the given bank details are correct!</strong><br/><br/>',
    'NOVALNET_SEPA_AUTHORIZE'                    		=> 'I authorise (A) Novalnet AG to send instructions to my bank to debit my account and (B) my bank to debit my account in accordance with the instructions from Novalnet AG.<br/><br/>',  
    'NOVALNET_SEPA_CREDITOR_IDENTIFIER'          		=> '<strong>Creditor identifier: DE53ZZZ00000004253</strong><br/><br/>',
    'NOVALNET_SEPA_CLAIM_NOTES'                  		=> '<strong>Note:</strong> You are entitled to a refund from your bank under the terms and conditions of your agreement with bank. A refund must be claimed within 8 weeks starting from the date on which your account was debited.<br/>',
    'NOVALNET_GUARANTEE_TEXT'                   => '<br><br>Your order is under verification and once confirmed, we will send you our bank details to where the order amount should be transferred. Please note that this may take upto 24 hours<br>',
    'NOVALNETCREDITCARD_URL' 					=> 'https://www.novalnet.com/credit-card',
    'NOVALNETSEPA_URL' 							=> 'https://www.novalnet.com/sepa-direct-debit',
    'NOVALNETINVOICE_URL' 						=> 'https://www.novalnet.com/invoice',
    'NOVALNETPREPAYMENT_URL' 					=> 'https://www.novalnet.com/prepayment',
    'NOVALNETONLINETRANSFER_URL' 				=> 'https://www.novalnet.com/online-instant-transfer',
    'NOVALNETIDEAL_URL' 						=> 'https://www.novalnet.com/ideal',
    'NOVALNETEPS_URL' 							=> 'https://www.novalnet.com/eps-online-payment',
    'NOVALNETGIROPAY_URL' 						=> 'https://www.novalnet.com/giropay',
    'NOVALNETPRZELEWY24_URL' 					=> 'https://www.novalnet.com/przelewy24',
    'NOVALNETPAYPAL_URL' 						=> 'https://www.novalnet.com/paypal',
    'NOVALNETBARZAHLEN_URL' 					=> 'https://www.novalnet.com/barzahlen',
];
?>
