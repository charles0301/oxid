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

namespace oe\novalnet\Controller\Admin;

/**
 * Class NovalnetAdmin.
 */
class NovalnetAdmin extends \OxidEsales\Eshop\Application\Controller\Admin\ShopConfiguration
{
    protected $_sThisTemplate = 'novalnetadmin.tpl';

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
     * Gets current language
     *
     * @return string
     */
    public function getNovalnetLanguage()
    {
        $iLang = \OxidEsales\Eshop\Core\Registry::getLang()->getTplLanguage();
        return \OxidEsales\Eshop\Core\Registry::getLang()->getLanguageAbbr($iLang);
    }

    /**
     * Get Image path
     *
     * @param $image
     *
     * @return string
     */
    public function getImagePath($image)
    {
        $viewConfig = oxNew(\OxidEsales\Eshop\Core\ViewConfig::class);
        return  $viewConfig->getModuleUrl('novalnet', 'out/admin/img/updates/'.$this->getNovalnetLanguage()."/".$image);
    }
}
