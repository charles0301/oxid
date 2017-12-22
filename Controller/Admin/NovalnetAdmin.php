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
 * Script : NovalnetAdmin.php
 *
 */

namespace oe\novalnet\Controller\Admin;

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
}
