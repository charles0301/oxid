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
 * Script : ShopControl.php
 *
 */

namespace oe\novalnet\Core;

use oe\novalnet\Classes\NovalnetUtil;

class ShopControl extends ShopControl_parent
{
    public function __construct()
    {
        $oNovalnetUtil = oxNew(NovalnetUtil::class);

        $sNovalnetAffiliateId = $oNovalnetUtil->oConfig->getRequestParameter('nn_aff_id');

        // checks the Novalnet affiliate id is passed
        if (!empty($sNovalnetAffiliateId)) {
            $oNovalnetUtil->oSession->setVariable('nn_aff_id', $sNovalnetAffiliateId);
        }
    }
}
?>
