<?php
/*******************************************************************************

    Copyright 2014 Foods Co-op

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

//use COREPOS\Fannie\API\lib\FannieUI;

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}
if (!class_exists('WFC_Hybrid_PDF')) {
    include(__DIR__ . '/../../admin/labels/pdf_layouts/WFC_Hybrid_No_Sort.php');
}
if (!class_exists('WFC_Hybrid_Guidelines_nosort_PDF')) {
    include(__DIR__ . '/../../admin/labels/pdf_layouts/WFC_Hybrid_Guidelines_nosort.php');
}
if (!class_exists('New_WFC_Deli_Regular_PDF')) {
    include(__DIR__ . '/../../admin/labels/pdf_layouts/New_WFC_Deli_Regular.php');
}
if (!class_exists('WFC_EssOil_PDF')) {
    include(__DIR__ . '/../../admin/labels/pdf_layouts/WFC_EssOil.php');
}
if (!class_exists('WFC_EssOil_PDF')) {
    include(__DIR__ . '/../../classlib2.0/item/signage/TagsNoPrice.php');
}
if (!class_exists('TradeLabelNewPrice_PDF')) {
    include(__DIR__ . '/../../admin/labels/pdf_layouts/TradeLabelNewPrice.php');
}

/*
 *  @class ShelfTagsInOrder 
 *
 *  Print a list of tags in list order.
 *
 */
class ShelfTagsInOrder extends FannieRESTfulPage 
{
    protected $title = "Fannie : Print Tags In Order";
    protected $header = "Print Tags In Order";
    protected $signage_obj;

    public $description = '[Print Ordered Tags] Print a list of shelf tags 
        in the order the tags were entered.';

    public function preprocess()
    {
        $this->__routes[] = 'get<upcs>';
        $this->__routes[] = 'get<upcs><print>';

        return parent::preprocess();
    }

    protected function get_upcs_print_handler()
    {
        $dbc  = $this->connection;
        $upcs = FormLib::get('upcs');
        $signtype = FormLib::get('signtype', false);
        $upcs = explode("\n", $upcs);
        $offset = (FormLib::get('offset') == 'on') ? 1 : 0;
        $itemrows = FormLib::get("itemrows", 13);
        $spacing = FormLib::get("spacing", 1);
        $showBarcode = (FormLib::get("showBarcode", false) == false) ? false : true;
        $showPrice = (FormLib::get("showPrice", false) == false) ? false  : true;

        $upcStr = '';
        foreach($upcs as $k => $upc) {
            $upcs[$k] = BarcodeLib::padUPC($upc); 
            $upcStr .= "\n$upc";
        }
        list($data, $td) = $this->getProdData($dbc, $upcs);
        $data = array_reverse($data);

        if ($signtype == "hybrid") {
            WFC_Hybrid_No_Sort($data, $offset);
        } else if ($signtype == "hyguide") {
            WFC_Hybrid_Guidelines_nosort($data, $offset);
        } else if ($signtype == "deli1") {
            $obj = new COREPOS\Fannie\API\item\signage\FancyShelfTags($data, 'provided', 0);
            $obj->drawPDF($showBarcode, $showPrice);
        } else if ($signtype == "deli2") {
            $obj = new COREPOS\Fannie\API\item\signage\FancyShelfTags_Narrow($data, 'provided', 0);
            $obj->drawPDF($showBarcode, $showPrice);
        } else if ($signtype == "deli3") {
            $obj = new COREPOS\Fannie\API\item\signage\FancyShelfTags_Short($data, 'provided', 0);
            /*
                !important help with printing barcodes, prices
                drawPDF(show_barcodes, show_price);
            */
            $obj->drawPDF($showBarcode, $showPrice);
        } else if ($signtype == "deli4") {
            $obj = new COREPOS\Fannie\API\item\signage\FancyShelfTags_NarrowShort($data, 'provided', 0);
            $obj->drawPDF($showBarcode, $showPrice);
        } else if ($signtype == "tagsnoprice") {
            $obj = new COREPOS\Fannie\API\item\signage\TagsNoPrice($data, 'provided', 0);
            $obj->drawPDF(true, true);
        } else if ($signtype == "tradelabel") {
            TradeLabelNewPrice($data,0);
        }



        //WFC_EssOil_PDF::WFC_EssOil($data, $offset, $itemrows, $spacing);

        return false;
    }

    protected function form($upcs='')
    {
        $offset = (FormLib::get('offset') == 'on') ? 'checked': '';
        $hybrid = (FormLib::get('signtype') == 'hybrid') ? 'checked': '';
        $hyguide = (FormLib::get('signtype') == 'hyguide') ? 'checked': '';
        $deli1 = (FormLib::get('signtype') == 'deli1') ? 'checked': '';
        $deli2 = (FormLib::get('signtype') == 'deli2') ? 'checked': '';
        $deli3 = (FormLib::get('signtype') == 'deli3') ? 'checked': '';
        $deli4 = (FormLib::get('signtype') == 'deli4') ? 'checked': '';
        $tagsnoprice = (FormLib::get('signtype') == 'tagsnoprice') ? 'checked': '';
        $tradelabel = (FormLib::get('signtype') == 'tradelabel') ? 'checked': '';
        $itemrows = FormLib::get("itemrows", 13);
        $spacing = FormLib::get("spacing", 1);
        $showBarcode = (FormLib::get("showBarcode", 0) == 0) ? '' : 'checked';
        $showPrice = (FormLib::get("showPrice", 0) == 0) ? '' : 'checked';

        return <<<HTML
<form action="ShelfTagsInOrder.php" method="get" name="myform">
<div class="row">
    <div class="col-lg-4">
        <div class="form-group"m>
            <div><label for="upcs">UPC List</label></div>
            <textarea id="upcs" name="upcs" class="form-control" rows=6>$upcs</textarea>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <div class="form-group">
                    <label for="offset">Offset</label>:&nbsp;&nbsp;
                    <input type="checkbox" id="offset" name="offset" $offset />
                </div>
                <div style="border-bottom: 1px solid grey; font-style: italic;">Grocery/Merch</div>
                <div class="form-group">
                    <label for="hybrid">hybrid</label>:&nbsp;&nbsp;
                    <input type="radio" id="hybrid" value="hybrid" name="signtype" onclick="document.forms['myform'].submit();" $hybrid />
                </div>
                <div class="form-group">
                    <label for="hyguide">hybrid with guidelines</label>:&nbsp;&nbsp;
                    <input type="radio" id="hyguide" value="hyguide" name="signtype" onclick="document.forms['myform'].submit();" $hyguide/>
                </div>
                <div style="border-bottom: 1px solid grey; font-style: italic;">Deli/Fancy</div>
                <div>
                    <label for="showBarcode"><i>Show Barcode</i></label>
                    <input name="showBarcode" id="showBarcode" type="checkbox" value="1" $showBarcode/>&nbsp;
                    <label for="showPrice"><i>Show Price</i></label>
                    <input name="showPrice" id="showPrice" type="checkbox" value="1" $showPrice/>
                </div>
                <div class="form-group">
                    <label for="deli1">Deli Regular</label>:&nbsp;&nbsp;
                    <input type="radio" id="deli1" value="deli1" name="signtype" onclick="document.forms['myform'].submit();" $deli1 />
                </div>
                <div class="form-group">
                    <label for="deli2">Deli Narrow</label>:&nbsp;&nbsp;
                    <input type="radio" id="deli2" value="deli2" name="signtype" onclick="document.forms['myform'].submit();" $deli2 />
                </div>
                <div class="form-group">
                    <label for="deli3">Deli Short (& Meat)</label>:&nbsp;&nbsp;
                    <input type="radio" id="deli3" value="deli3" name="signtype" onclick="document.forms['myform'].submit();" $deli3 />
                </div>
                <div class="form-group">
                    <label for="deli4">Deli Narrow & Short</label>:&nbsp;&nbsp;
                    <input type="radio" id="deli4" value="deli4" name="signtype" onclick="document.forms['myform'].submit();" $deli4 />
                </div>
                <div class="form-group">
                    <label for="tagsnoprice">Tags No Price (Order Tags)</label>:&nbsp;&nbsp;
                    <input type="radio" id="tagsnoprice" value="tagsnoprice" name="signtype" onclick="document.forms['myform'].submit();" $tagsnoprice />
                </div>
                <div style="border-bottom: 1px solid grey; font-style: italic;">Bulk</div>
                <div class="form-group">
                    <label for="tradelabel">Trade Label Tabs<label>:&nbsp;&nbsp;
                    <input type="radio" id="tradelabel" value="tradelabel" name="signtype" onclick="document.forms['myform'].submit();" $tradelabel />
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <button class="btn btn-default" type="submit">Submit</button>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">

            <div><a href="../../admin/labels/CreateTagsByDept.php">Create Tags By Department</a></div>
            <div><a href="../../admin/labels/CreateTagsByManu.php">Create Tags By Brand</a></div>
            <div><a href="../../admin/labels/QueueTagsByList.php">Queue Tags by A List</a></div>
            <div><a href="../../admin/labels/QueueTagsByLC.php">Queue Tags by Like Code</a></div>
            <div><a href="../../admin/labels/MovementTagTracker.php">Movement Tag Tracker</a>
                | <a href="../../admin/labels/MovementTagTracker.php?id=config">Settings</a>
                | <a href="../../admin/labels/MovementTagTracker.php?data=view">Data</a></div>
            <div><a href="../../item/handheld/ItemStatusPage.php">Scan a Single Item</a></div>
            <div><a href="../../item/vendors/PrintEssentialOilStrips.php">Print Essential Oil Strips</a></div>
    </div>
    <div class="col-lg-4">
    </div>
</div>
</form>
HTML;
    }

    protected function get_view()
    {
        return <<<HTML
{$this->form()}
HTML;
    }

    protected function getProdData($dbc, $upcs)
    {
        $td = '';
        $data = array();
        //$i = 0;
        $i = count($upcs);
        foreach ($upcs as $k => $upc) {
            $args = array($upc);
            $prep = $dbc->prepare("SELECT *, p.brand AS pbrand, p.description AS pdesc, vendors.vendorName AS vendor,
                CONCAT(ROUND(normal_price / substring_index(v.size, ' ', 1), 3), '/', substring_index(v.size, ' ', -1)) AS pricePerUnit,
                pu.brand as signbrand, pu.description as signdesc
                FROM products AS p 
                    LEFT JOIN vendorItems AS v ON p.default_vendor_id=v.vendorID AND p.upc=v.upc
                    LEFT JOIN vendors ON v.vendorID=vendors.vendorID
                    LEFT JOIN productUser pu on pu.upc=p.upc
                WHERE p.upc = ? ");
            $res = $dbc->execute($prep, $args);
            while ($row = $dbc->fetchRow($res)) {
                $price = $row['normal_price'];
                $desc = $row['pdesc'];
                $signdesc = $row['signdesc'];
                $signbrand = $row['signbrand'];
                $brand = $row['pbrand'];
                $units = $row['units'];
                $size = $row['size'];
                $sku = $row['sku'];
                $upc = $row['upc'];
                $scale = $row['scale'];
                $vendor = (isset($row['vendor'])) ? $row['vendor'] : '';
                $ppu = isset($row['pricePerUnit']) ? $row['pricePerUnit'] : '';
                $data[$i]['normal_price'] = $price;
                $data[$i]['description'] = $desc;
                $data[$i]['brand'] = $brand;
                $data[$i]['units'] = $units;
                $data[$i]['size'] = $size;
                $data[$i]['sku'] = $sku;
                $data[$i]['upc'] = $upc;
                $data[$i]['scale'] = $scale;
                $data[$i]['vendor'] = $vendor;
                $data[$i]['pricePerUnit'] = $ppu;
                $td .= "<tr><td>$upc</td><td>$brand</td><td>$desc</td>
                    <td>$signbrand</td><td>$signdesc</td><td>$units</td>
                    <td>$size</td><td>$sku</td><td>$scale</td></tr>";
                //$i++;
                $i--;
                break; // only need one result per UPC
            }
        }

        return array($data, $td);
    }

    protected function get_upcs_view()
    {
        $URI = $_SERVER['REQUEST_URI'] . "&print=1";
        $upcs = FormLib::get('upcs');
        $itemrows = FormLib::get("itemrows", 13);
        $upcs = explode("\n", $upcs);
        $upcStr = '';
        foreach($upcs as $k => $upc) {
            $upcs[$k] = BarcodeLib::padUPC($upc); 
            $upcStr .= "\n$upc";
        }
        $dbc = $this->connection;

        $data = array();
        list($data, $td) = $this->getProdData($dbc, $upcs);

        return <<<HTML
{$this->form($upcStr)} 
<div class="row">
    <div class="col-lg-2">
        <div class="form-group">
            <button class="btn btn-primary form-control" onclick="window.location.href = '$URI'">Print</button>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-1">
        <div class="form-group">
            <button class="btn btn-default form-control" onclick="window.location.href= 'http://key/git/fannie/item/vendors/ShelfTagsInOrder.php'">Clear</button>
        </div>
    </div>
</div>
<div class="table-responsive">
    <table class="table table-bordered"><thead></thead><tbody>$td</tbody></table>
</div>
HTML;
    }

    public function javascript_content()
    {
        return <<<JAVASCRIPT
$('#offset').change(function(){
    document.forms['myform'].submit();
});
$('#spacing').on('change', function() {
    document.forms['myform'].submit();
});
JAVASCRIPT;
    }

    public function helpContent()
    {
        return '<p>Print a list of shelf tags in order.</p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertEquals(false, $this->get_upcs_print_handler());
    }
}

FannieDispatch::conditionalExec();

