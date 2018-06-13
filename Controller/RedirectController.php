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
 * Class RedirectController.
 */
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
             \OxidEsales\Eshop\Core\Registry::getUtils()->redirect($oNovalnetUtil->oConfig->getShopMainUrl(), false);

        $this->_aViewData['sNovalnetFormAction'] = $oNovalnetUtil->oSession->getVariable('sNovalnetRedirectURL');
        $this->_aViewData['aNovalnetFormData']   = $oNovalnetUtil->oSession->getVariable('aNovalnetRedirectRequest');

        // checks to verify the redirect payment details available
        if (empty($this->_aViewData['sNovalnetFormAction']) || empty($this->_aViewData['aNovalnetFormData']))
             \OxidEsales\Eshop\Core\Registry::getUtils()->redirect($oNovalnetUtil->oConfig->getShopMainUrl() . 'index.php?cl=payment', false);
        elseif (!empty($this->_aViewData['sNovalnetFormAction']) && !empty($this->_aViewData['aNovalnetFormData']))
            return 'novalnetredirect.tpl';

    }
}
