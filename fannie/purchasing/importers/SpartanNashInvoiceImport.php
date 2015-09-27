<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
if (!class_exists('ImportPurchaseOrder')) {
    include(dirname(__FILE__) . '/../ImportPurchaseOrder.php');
}

class SpartanNashInvoiceImport extends ImportPurchaseOrder 
{
    protected $title = "Fannie - Purchase Order";
    protected $header = "Upload Purchase Spartan Nash Invoice";

    public $description = '[Spartan Nash Order Import] loads a vendor purchase order / invoice 
    from a spreadsheet.';
    public $themed = true;

    protected $preview_opts = array(
        'sku' => array(
            'name' => 'sku',
            'display_name' => 'SKU*',
            'default' => 5,
            'required' => true
        ),
        'cost' => array(
            'name' => 'cost',
            'display_name' => 'Cost (Total)*',
            'default' => 13,
            'required' => true
        ),
        'unitQty' => array(
            'name' => 'unitQty',
            'display_name' => 'Qty (Units)+',
            'default' => -1,
            'required' => false
        ),
        'caseQty' => array(
            'name' => 'caseQty',
            'display_name' => 'Qty (Cases)+',
            'default' => 9,
            'required' => false
        ),
        'caseSize' => array(
            'name' => 'caseSize',
            'display_name' => 'Units / Case',
            'default' => 7,
            'required' => false
        ),
        'unitSize' => array(
            'name' => 'unitSize',
            'display_name' => 'Unit Size',
            'default' => -1,
            'required' => false
        ),
        'brand' => array(
            'name' => 'brand',
            'display_name' => 'Brand',
            'default' => -1,
            'required' => false
        ),
        'desc' => array(
            'name' => 'desc',
            'display_name' => 'Description',
            'default' => 6,
            'required' => false
        ),
        'upc' => array(
            'name' => 'upc',
            'display_name' => 'UPC (w/o check)',
            'default' => 8,
            'required' => false
        ),
        'upcc' => array(
            'name' => 'upcc',
            'display_name' => 'UPC (w/ check)',
            'default' => -1,
            'required' => false
        ),
    );

    /**
      overriding the basic form since I need several extra fields   
    */
    protected function basicForm()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $vendors = new VendorsModel($dbc);
        ob_start();
        ?>
        <form enctype="multipart/form-data" class="form-horizontal" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="FannieUploadForm" method="post">
        <div class="form-group col-sm-6">
            <label class="control-label col-sm-3">Vendor</label>
            <div class="col-sm-9"><select name=vendorID class="form-control">
            <?php foreach($vendors->find('vendorName') as $v) printf("<option %s value=%d>%s</option>",
                ($v->vendorName() == 'SPARTAN NASH' ? 'selected' : ''),
                $v->vendorID(), $v->vendorName()); ?>
                </select></div>
        </div>
        <div class="form-group col-sm-6">
            <label class="control-label col-sm-3">Order Date</label>
            <div class="col-sm-9"><input type="text" class="form-control date-field" name="orderDate" id="orderDate" /></div>
        </div>
        <div class="form-group col-sm-6">
            <label class="control-label col-sm-3">PO#/Invoice#</label>
            <div class="col-sm-9"><input type="text" class="form-control" name="identifier" /></div>
        </div>
        <div class="form-group col-sm-6">
            <label class="control-label col-sm-3">Recv'd Date</label>
            <div class="col-sm-9"><input type="text" class="form-control date-field" name="recvDate" id="recvDate" /></div>
        </div>
        <div class="form-group col-sm-6">
            <label class="control-label col-sm-3">Filename</label>
            <div class="col-sm-9"><input type="file" class="form-control" name="FannieUploadFile" id="FannieUploadFile" /></div>
        </div>
        <div class="form-group col-sm-6">
            <button type="submit" class="btn btn-default">Upload File</button>
            <button type="button" class="btn btn-default" 
                onclick="location='PurchasingIndexPage.php'; return false;">Home</button>
        </div>
        <?php

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

?>
