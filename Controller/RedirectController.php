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
 * Script : RedirectController.php
 *
 */
namespace oe\novalnet\Controller;

use OxidEsales\Eshop\Application\Controller\FrontendController;
use oe\novalnet\Classes\NovalnetUtil;

class RedirectController extends FrontendController
{
    /**
     * Returns name of template to render
     *
     * @return string
     */
    public function render()
    {

        parent::render();

        $oNovalnetUtil = oxNew(NovalnetUtil::class);
        $oUser    = oxNew(\OxidEsales\Eshop\Application\Model\User::class);
        $sUserID  = $oNovalnetUtil->oSession->getVariable('usr');
        $oUser->load($sUserID);
        if (!$oUser->getUser())
            $oNovalnetUtil->oUtils->redirect($oNovalnetUtil->oConfig->getShopMainUrl(), false);

        $this->_aViewData['sNovalnetFormAction'] = $oNovalnetUtil->oSession->getVariable('sNovalnetRedirectURL');
        $this->_aViewData['aNovalnetFormData']   = $oNovalnetUtil->oSession->getVariable('aNovalnetRedirectRequest');

        // checks to verify the redirect payment details available
        if (empty($this->_aViewData['sNovalnetFormAction']) || empty($this->_aViewData['aNovalnetFormData']))
            $oNovalnetUtil->oUtils->redirect($oNovalnetUtil->oConfig->getShopMainUrl() . 'index.php?cl=payment', false);
        elseif (!empty($this->_aViewData['sNovalnetFormAction']) && !empty($this->_aViewData['aNovalnetFormData']))
            return 'novalnetredirect.tpl';

    }
}
