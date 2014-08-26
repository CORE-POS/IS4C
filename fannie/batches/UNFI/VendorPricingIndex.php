<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class VendorPricingIndex extends FanniePage {
    /* html header, including navbar */
    protected $title = "Fannie - Vendor Price File";
    protected $header = "Vendor Price File";

    public $description = '[Vendor Pricing Menu] lists tools for managing vendor
    cost information and making price changes when costs change.';

    function body_content(){
        ob_start();
        ?>
        <table cellspacing=0 cellpadding=3 border=1>
        <tr>
            <td><a href=RecalculateVendorSRPs.php>Recalculate SRPs</a></td>
            <td>Re-compute SRPs for the vendor price change page based on
                desired margins</td>
        </tr>
        <tr>
            <td><a href=UploadVendorPriceFile.php>Upload Price Sheet</a></td>
            <td>Load a new vendor price sheet (this is still a bit complicated. <a href=HowToVendorPricing.php>Howto</a>.)</td>
        </tr>
        <tr>
            <td><a href=VendorPricingBatchPage.php>Create Price Change Batch</a></td>
            <td>Compare current &amp; desired margins, create batch for updates</td>
        </tr>
        </table>
        <?php
        return ob_get_clean();
    }

}

FannieDispatch::conditionalExec(false);

?>
