<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class CapSalesIndexPage extends FanniePage 
{
    protected $title = "Fannie - Co+op Deals sales";
    protected $header = "Co+op Deals Sales";

    public $description = '[Co+op Deals Menu] lists options for importing and creating
    Co+op Deals batches.';
    public $themed = true;

    function body_content(){
        ob_start();
        ?>
        <ul>
        <li>Co+op Deals<ul>
            <li><a href="CoopDealsUploadPage.php">Upload Price File</a></li>
            <li><a href="CoopDealsReviewPage.php">Review data &amp; create sales batches</a></li>
            <li><a href="CoopDealsMergePage.php">Merge items into existing batches</a></li>
            <li><a href="CoopDealsSignsPage.php">Print Sale Signs</a></li>
        </ul></li>
        <li>EDLP<ul>
            <li><a href="EdlpUploadPage.php">Upload EDLP Max Prices</a></li>
            <li><a href="EdlpBatchPage.php">Create Price Change Batch</a></li>
            <li><a href="EdlpCatalogOverwrite.php">Update Item and Vendor Catalog Costs</a></li>
        </ul></li>
        </ul>
        <?php
        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>Upload Co+op Deals item data spreadsheet then review the
            data to assign sale start and end dates.</p>
            <p>Alternately, upload maximum pricing for EDLP items and
            create a price change batch if necessary.</p>
            <p>The unifying thread here is NCG-related.</p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }
}

FannieDispatch::conditionalExec();

