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
 * Script : metadata.php
 *
 */

/**
 * Metadata version
 */
$sMetadataVersion = '2.0';

/**
 * Module information
 */
$aModule = array(
    'id'          => 'novalnet',
    'title'       => array(
        'de' => 'Novalnet',
        'en' => 'Novalnet',
    ),
    'description' => array(
         'de' => 'Novalnet Zahlungsmodul',
         'en' => 'Novalnet Payment module'
    ),
    'thumbnail'   => 'icon.png',
    'version'     => '11.2.0',
    'author'      => 'Novalnet',
    'url'         => 'https://www.novalnet.de',
    'email'       => 'technic@novalnet.de',
    'extend'      => array(
     \OxidEsales\Eshop\Application\Controller\PaymentController::class          => \oe\novalnet\Controller\PaymentController::class,
     \OxidEsales\Eshop\Core\InputValidator::class => \oe\novalnet\Core\InputValidator::class,
     \OxidEsales\Eshop\Application\Model\PaymentGateway::class  => \oe\novalnet\Model\PaymentGateway::class,
     \OxidEsales\Eshop\Application\Model\Order::class => \oe\novalnet\Model\Order::class,
     \OxidEsales\Eshop\Application\Controller\AccountOrderController::class => \oe\novalnet\Controller\AccountOrderController::class,
     \OxidEsales\Eshop\Application\Controller\Admin\OrderOverview::class => \oe\novalnet\Controller\Admin\OrderOverview::class,
     \OxidEsales\Eshop\Application\Controller\OrderController::class => \oe\novalnet\Controller\OrderController::class,
     \OxidEsales\Eshop\Core\ShopControl::class => \oe\novalnet\Core\ShopControl::class,
    ),
    'controllers'       => array(
        'novalnetadmincontroller' => \oe\novalnet\Controller\Admin\AdminController::class,
        'novalnetadmin'  => \oe\novalnet\Controller\Admin\NovalnetAdmin::class,
        'novalnetredirectcontroller' => \oe\novalnet\Controller\RedirectController::class,
        'novalnetcallback'  => \oe\novalnet\Controller\CallbackController::class,
    ),
    'files'       => array(),
    'templates'   => array(
        'novalnetconfig.tpl'      => 'oe/novalnet/views/admin/tpl/novalnetconfig.tpl',
        'novalnetadmin.tpl'       => 'oe/novalnet/views/admin/tpl/novalnetadmin.tpl',
        'novalnetcreditcard.tpl'  => 'oe/novalnet/views/blocks/page/checkout/inc/novalnetcreditcard.tpl',
        'novalnetsepa.tpl'        => 'oe/novalnet/views/blocks/page/checkout/inc/novalnetsepa.tpl',
        'novalnetinvoice.tpl'     => 'oe/novalnet/views/blocks/page/checkout/inc/novalnetinvoice.tpl',
        'novalnetpaypal.tpl'      => 'oe/novalnet/views/blocks/page/checkout/inc/novalnetpaypal.tpl',
        'novalnetredirect.tpl'    => 'oe/novalnet/views/tpl/novalnetredirect.tpl',
        'novalnetcallback.tpl'    => 'oe/novalnet/views/tpl/novalnetcallback.tpl',
    ),
    'blocks'      => array(
                            array(
                                'template' => 'page/checkout/payment.tpl',
                                'block'    => 'select_payment',
                                'file'     => '/views/blocks/page/checkout/novalnetpayments.tpl'
                                ),
						    array(
							    'template' => 'email/html/order_cust.tpl',
							    'block'    => 'email_html_order_cust_username',
							    'file'     => '/views/blocks/email/html/novalnettransaction.tpl'
								 ),
						   array(
								  'template' => 'email/html/order_owner.tpl',
								  'block'    => 'email_html_order_owner_username',
								  'file'     => '/views/blocks/email/html/novalnettransaction.tpl'
								),
						   array(
								  'template' => 'email/plain/order_cust.tpl',
								  'block'    => 'email_plain_order_cust_username',
								  'file'     => '/views/blocks/email/html/novalnettransaction.tpl'
								),
						   array(
								  'template' => 'email/plain/order_owner.tpl',
								  'block'    => 'email_plain_order_ownerusername',
								  'file'     => '/views/blocks/email/html/novalnettransaction.tpl'
								),
						   array(
								  'template' => 'page/account/order.tpl',
								  'block'    => 'account_order_history',
								  'file'     => '/views/blocks/page/account/novalnetorder.tpl'
								),
						   array(
								  'template' => 'page/checkout/order.tpl',
								  'block'    => 'checkout_order_btn_confirm_bottom',
								  'file'     => '/views/blocks/page/checkout/novalnetorder.tpl'
								),
						   array(
								  'template' => 'order_overview.tpl',
								  'block'    => 'admin_order_overview_checkout',
								  'file'     => '/views/admin/blocks/novalnetcomments.tpl'
								),
						   array(
								  'template' => 'order_overview.tpl',
								  'block'    => 'admin_order_overview_dynamic',
								  'file'     => '/views/admin/blocks/novalnetdynamic.tpl'
								),
						   array(
								  'template' => 'order_overview.tpl',
								  'block'    => 'admin_order_overview_export',
								  'file'     => '/views/admin/blocks/novalnetextensions.tpl'
								)
                        ),
    'settings'    => array(),
    'events'       => array(
        'onActivate'   => '\oe\novalnet\Core\Events::onActivate',
        'onDeactivate' => '\oe\novalnet\Core\Events::onDeactivate'
    ),
);

