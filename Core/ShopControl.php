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

namespace oe\novalnet\Core;

use oe\novalnet\Classes\NovalnetUtil;

/**
 * Class ShopControl.
 */
class ShopControl extends ShopControl_parent
{
    public function __construct()
    {
        $oNovalnetUtil = oxNew(NovalnetUtil::class);

        // checks the Novalnet affiliate id is passed
        if ($sNovalnetAffiliateId = $oNovalnetUtil->oConfig->getRequestParameter('nn_aff_id'))
            $oNovalnetUtil->oSession->setVariable('nn_aff_id', $sNovalnetAffiliateId);
    }
}
?>
