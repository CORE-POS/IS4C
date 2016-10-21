<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
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
    public $themed = true;

    function body_content(){
        ob_start();
        ?>
        <table class="table">
        <tr>
            <td><a href="../../item/vendors/">Manage Vendors<a></td>
            <td>Tools to create and edit vendors</td>
        </tr>
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
    
    public function helpContent()
    {
        return '
            <p>These tools are for managing prices based on vendor item costs 
            and store margin targets
            </p>
            <p>
            To create price change batches, the following pre-requites must be
            fulfilled:
            <ul>
                <li>Products the store sells must be assigned to a vendor</li>
                <li>The vendor\'s catalog must be in the system with unit costs
                    and SRPs</li>
                <li>Margin tagets must be entered for POS departments and/or
                    the vendor\'s subcategories</li>
            </ul>
            </p>
            <p>
            SRPs (standard retail prices) are critical to this tool set as it
            chiefly compares current prices to SRPs. These SRPs come from one of
            two places:
            <ul>
                <li>If you specify a column of SRPs when importing a vendor catalog,
                    those values will be used.</li>
                <li>If you did not specify a column or SRPs <strong>or</strong> you
                    wish to replace those SRPs with values based on margin targets, use
                    the <em>Recalculate SRPs</em> tool. Read the Help text on that tool
                    for details on the exact calculations.</li>
            </ul>
            </p>
            <p>
            When all prerequisites are fulfilled, use <em>Create Price Change Batch</em>
            to compare pricing and create price change batches.
            </p>
            ';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }
}

FannieDispatch::conditionalExec();

