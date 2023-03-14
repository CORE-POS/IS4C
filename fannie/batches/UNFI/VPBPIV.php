<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

use \COREPOS\Fannie\API\item\Margin;
use \COREPOS\Fannie\API\item\PriceRounder;

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class VPBPIV extends FannieRESTfulPage
{
    protected $title = "Fannie - Create Price Change Batch";
    protected $header = "Create Price Change Batch";

    public $description = '[Vendor Price Change] creates a price change batch for a given
    vendor and edits it based on catalog cost information.';
    public $themed = true;

    protected $auth_classes = array('batches');
    protected $must_authenticate = true;

    private $mode = 'start';

    public function preprocess()
    {
        $this->__routes[] = 'post<cleanup>';

        return parent::preprocess();
    }

    private function getBatchedItems()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->OP_DB);
        $upcs = array();
        $args = array();
        /*
         * avoid using zero date
         * some mysql versions dislike it
         */
        $prep = $dbc->prepare("SELECT b.*, l.upc, s.batchName
            FROM batchReviewLog AS b
            INNER JOIN batchList AS l ON l.batchID=b.bid 
            INNER JOIN batches AS s ON s.batchID=l.batchID
            WHERE forced < '1900-01-01 00:00:00';");
        $res = $dbc->execute($prep, $args);
        while ($row = $dbc->fetchRow($res)) {
            $upcs[$row['upc']] = $row['batchName'];
        }

        return $upcs;
    }

    public function post_cleanup_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->OP_DB);
        $bids = FormLib::get('bid');

        $prepC = $dbc->prepare("SELECT COUNT(*) AS count 
            FROM batchList WHERE batchID = ?");
        $prepD = $dbc->prepare("DELETE FROM batches 
            WHERE batchID = ?");
        foreach ($bids as $bid) {
            $resC = $dbc->execute($prepC, array($bid));
            $rowC = $dbc->fetchRow($resC);
            $count = $rowC['count'];
            if ($count == 0) {
                $resD = $dbc->execute($prepD, array($bid));
            }
        }
        echo $dbc->error();
        echo json_encode($bids);

        return false;
    }

    public function css_content()
    {
        return <<<HTML
        span.grey {
            color: grey;
        }
        tr.green-green td.sub {
            background:#ccffcc;
        }
        tr.green-red td.sub {
            background:#ccffcc;
        }
        tr.greenb td.sub {
            background:#ddffcc;
        }
        tr.red td.sub {
            background:#F7BABA;
        }
        tr.white td.sub {
            background:#ffffff;
        }
        th.thead, td.thead {
            background: #fff4d6;
        }
        tr.yellow td.sub {
            background:#ffff96;
        }
        span.yellow {
            background:#ffff96;
        }
        span.red {
            background:#F7BABA;
        }
        span.white {
            background:#ffffff;
        }
        tr.selection td.sub {
            background:#add8e6;
        }
        td.srp {
            text-decoration: underline;
        }
        .adj-cost, .price, .cmargin {
            border: 5px solid red;
            background: red;
            background-color: red;
            color: gray;
        }
        .row-selected {
            border: 5px solid plum;
        }
        .uniq-table, .uniq-table tr,
        .uniq-table td {
            border: 1px solid grey;
            padding: 5px;
        }
        input {
            border: none;
            background-color: rgba(0,0,0,0);
        }
        label {
            font-weight: bold; 
            font-size: 9px;
            text-transform: uppercase; 
            color: grey;
        }
        button {
            width: 100%;
        }
        span.highlight {
            babkground-color: lightgreen;
            background-color: rgba(0,255,0, 0.1);
            color: green;
            padding: 2px;
        }
        .row{
            text-align: right;
        }
        #keypad {
            //border: 1px solid black;
        }
        #keypad-table {
            position: absolute;
        }
        td.border-cell { 
            border: 1px solid black;
            height: 30px;
            width: 30px;
            font-size: 10px;
            text-align: center;
            user-select: none;
            background: white;
        }
        td.border-cell { 
            border: 1px solid black;
            height: 30px;
            width: 30px;
            font-size: 10px;
            text-align: center;
            cursor: pointer;
            user-select: none;
            background: white;
        }
        div.item-info-container {
            position: relative;
        }
        div.label {
            position: absolute;
            left: 0px;
        }
HTML;
    }

    public function get_id_view()
    {
        $table = $this->getTable();
        $td = $table;

        return <<<HTML
<div class="container" style=" user-select: none;">
    <!--<label><u>Current Product</u></label>-->
    <div class="row">
        <div class="col-lg-2">
            <div class="item-info-container">
                <div class="label">
                    <label>upc</label>
                </div>
                <span id="cur-upc" style="user-select: text"></span>
            </div>
        </div>
        <div class="col-lg-2">
            <div class="item-info-container">
                <div class="label">
                    <label>sku</label>
                </div>
                <span id="cur-sku" style="user-select: text"></span>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="item-info-container">
                <div class="label">
                    <label>brand</label>
                </div>
                <span id="cur-brand" style="width: 120px" ></span>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="item-info-container">
                <div class="label">
                    <label>description</label>
                </div>
                <u><span id="cur-description" style="width: 300px" ></span></u>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-2">
            <div class="item-info-container">
                <div class="label">
                    <label>base cost</label>
                </div>
                <span id="cur-base-cost"></span>
            </div>
        </div>
        <div class="col-lg-2">
            <div class="item-info-container">
                <div class="label">
                    <label>adj. cost</label>
                </div>
                <span id="cur-cost"></span>
            </div>
        </div>
        <div class="col-lg-3">
            <!--<button class="btn btn-default btn-sm" id="btn-up" onClick="btnChangeSelected('up'); return false; ">PREVIOUS (<i>up arrow</i>)</button>-->
            <!--<button class="btn btn-default btn-sm" id="btn-down" onClick="btnChangeSelected('down'); return false; ">NEXT (<i>down arrow</i>)</button>-->
        </div>
    </div>
    <div class="row">
        <div class="col-lg-2">
            <div class="item-info-container">
                <div class="label">
                    <label>margin</label>
                </div>
                <span id="cur-margin"></span>%
            </div>
        </div>
        <div class="col-lg-2">
            <div class="item-info-container">
                <div class="label">
                    <label>target margin</label>
                </div>
                <span id="cur-target-margin"></span>%
            </div>
        </div>
        <div class="col-lg-3">
            <div class="item-info-container">
                <div class="label">
                    <label>off from target by</label>
                </div>
                <span id="cur-diff"></span>%
            </div>
        </div>
        <div class="col-lg-5">
            <!--<button class="btn btn-default btn-sm" id="btn-add-to-batch" onClick="btnRemoveFromBatch(); return false; ">REMOVE FROM BATCH (<i>left arrow</i>)</button>-->
            <div id="keypad" align="right"><div style="width: 300px">
                <table id="keypad-table">
                    <tr>
                    <td class="nobord-cell">&nbsp;</td> 
                    <td class="border-cell" onclick="btnChangeSelected('up'); return false; ">Up</td>  
                    <td>&nbsp;</td></tr>
                    <tr>
                    <td class="border-cell" title="Remove From Batch" onclick="btnRemoveFromBatch(); btnChangeSelected('down'); return false; "> - </td> 
                    <td class="border-cell"  onclick="btnChangeSelected('down'); return false; ">Down</td>  
                    <td class="border-cell"  ><span id="btnAdd" onclick="btnAddToBatch(); btnChangeSelected('down'); return false; " title="Add To Batch"> + </span></td></tr>
                    <tr>
                    <td class="border-cell" colspan="3" onclick="btnEditPrice(); return false;" title="Edit Price">Change SRP</td></tr>
                </table>
            </div></div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-2">
            <div class="item-info-container">
                <div class="label">
                    <label>last reviewed</label>
                </div>
                <span id="cur-reviewed"></span>
            </div>
        </div>
        <div class="col-lg-2"></div>
        <div class="col-lg-3">
            <div class="item-info-container">
                <div class="label">
                    <label>last change</label>
                </div>
                <span id="cur-change"></span>
            </div>
        </div>
        <div class="col-lg-3">
            <!--<button class="btn btn-default btn-sm" id="btn-edit-price" onClick="btnEditPrice(); return false; ">EDIT PRICE (<i>space</i>)</button>-->
        </div>
    </div>
    <div class="row">
        <div class="col-lg-2">
            <div class="item-info-container">
                <div class="label">
                    <label>current price</label>
                </div>
                <span id="cur-normalprice"></span>
            </div>
        </div>
        <div class="col-lg-2">
            <div class="item-info-container">
                <div class="label">
                    <label>raw/rnd</label>
                </div>
                <span id="cur-raw"></span> (<span id="cur-round"></span>)
            </div>
        </div>
        <div class="col-lg-3">
            <div class="item-info-container">
                <div class="label">
                    <label>new batch srp</label>
                </div>
                <span id="cur-srp"></span>
                <span id="cur-srp-visual"></span>
            </div>
        </div>
        <div class="col-lg-3">
            <!--<button class="btn btn-default btn-sm" id="btn-add-to-batch" onClick="btnAddToBatch(); return false; ">ADD TO BATCH (<i>right arrow</i>)</button>-->
        </div>
    </div>
    <div class="row">
        <div class="col-lg-3">
            <div class="item-info-container">
                <div class="label">
                    <label>price rule</label>
                </div>
                <span id="cur-pricerule"></span></span>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="item-info-container">
                </div>
                <span id="cur-inbatch" style="font-weight: bold; color: darkgreen;"></span></span>
            </div>
        </div>
        </div>
        <div class="col-lg-5">
    </div>
</div>
<div class="container">
    <div style="padding-top: 24px; padding-bottom: 5px; padding-left: 1px;">
        <a href="#" onclick="postActionBatchCleanup();" style="background-color: #FFF4D6;
            border: 1px solid black; padding: 5px; color: purple;">Cleanup & View Batches</a>
    </div>
    <div class="table-responsive" style="overflow-x: hidden;">
        <table class="uniq-table table table-condensed small" id="uniq-table">$td</table>
    </div>
</div>
HTML;
    }


    public function javascript_content()
    {
        return <<<JAVASCRIPT
var uniqTable = document.getElementById('uniq-table');
var currentRow = 2;
var currentPosition = 2;
var uniqTableRows = uniqTable.rows.length;
var uniqTop = 0;
var uniqBottom = 5;
var batchIdInc = $('#batchID0').val();
var batchIdRedux = $('#batchID1').val();
for (let i = 2; i < uniqTable.rows.length; i++) {
    uniqTable.rows[i].style.display = '';
    if (i > 6) {
        uniqTable.rows[i].style.display = 'none';
    }
    if (i < uniqTableRows - 3) {
        uniqTable.rows[i].insertCell(-1).innerHTML = (i-1) + ' / ' + (uniqTableRows - 5);
        uniqTable.rows[i].style.background = 'white';
    }
}
var changeSelectedRow = function(row) {
    uniqTable.rows[row].style.border = '3px solid rebeccapurple';
    let btnAdd = document.getElementById('btnAdd');
}
var deselectRow = function(row) {
    uniqTable.rows[row].style.border = '';
}
changeSelectedRow(currentRow);

var btnChangeSelected = function(direction)
{
    if (direction == 'up' && currentRow == 2) {
        return false;
    }
    if (direction == 'down' && currentRow + 4 == uniqTableRows) {
        return false;
    }

    deselectRow(currentRow);
    if (direction == 'down') {
        currentRow++;
        if (uniqTable.rows[uniqTop].id != 'uniq-thead')
            uniqTable.rows[uniqTop].style.display = 'none';
        uniqTable.rows[uniqBottom].style.display = '';
        if (uniqTable.rows[uniqTop+1] !== undefined)
            uniqTop++;
        if (uniqTable.rows[uniqBottom+1] !== undefined)
            uniqBottom++;
    } else if (direction == 'up') {
        if (uniqTable.rows[uniqTop] !== undefined) {

        }
        currentRow--;
        uniqTable.rows[uniqTop].style.display = '';
        uniqTable.rows[uniqBottom].style.display = 'none';
        if (uniqTable.rows[uniqTop-1] !== undefined)
            uniqTop--;
        if (uniqTable.rows[uniqBottom-1] !== undefined)
            uniqBottom--;
    }


    changeSelectedRow(currentRow);
    document.getElementById('cur-upc').innerHTML = uniqTable.rows[currentRow].cells[0].dataset.upc;
    document.getElementById('cur-sku').innerHTML = uniqTable.rows[currentRow].cells[1].innerHTML;
    document.getElementById('cur-cost').innerHTML = Math.round(uniqTable.rows[currentRow].cells[0].dataset.cost * 100) / 100;
    document.getElementById('cur-margin').innerHTML = uniqTable.rows[currentRow].cells[0].dataset.margin;
    document.getElementById('cur-target-margin').innerHTML = Number(uniqTable.rows[currentRow].cells[0].dataset.target).toFixed(1);
    document.getElementById('cur-diff').innerHTML = uniqTable.rows[currentRow].cells[0].dataset.diff;
    document.getElementById('cur-change').innerHTML = uniqTable.rows[currentRow].cells[0].dataset.change;
    document.getElementById('cur-description').innerHTML = uniqTable.rows[currentRow].cells[0].dataset.description;
    document.getElementById('cur-brand').innerHTML = uniqTable.rows[currentRow].cells[0].dataset.brand;
    document.getElementById('cur-reviewed').innerHTML = uniqTable.rows[currentRow].cells[0].dataset.reviewed;
    document.getElementById('cur-base-cost').innerHTML = uniqTable.rows[currentRow].cells[0].dataset.basecost;
    document.getElementById('cur-raw').innerHTML = uniqTable.rows[currentRow].cells[0].dataset.raw;
    document.getElementById('cur-round').innerHTML = uniqTable.rows[currentRow].cells[0].dataset.round;
    document.getElementById('cur-srp').innerHTML = uniqTable.rows[currentRow].cells[0].dataset.srp;
    document.getElementById('cur-srp-visual').innerHTML = uniqTable.rows[currentRow].cells[0].dataset.srpvisual;
    document.getElementById('cur-pricerule').innerHTML = uniqTable.rows[currentRow].cells[0].dataset.pricerule;
    document.getElementById('cur-inbatch').innerHTML = uniqTable.rows[currentRow].cells[0].dataset.inbatch;
    document.getElementById('cur-normalprice').innerHTML = uniqTable.rows[currentRow].cells[0].dataset.normalprice;

}

var btnAddToBatch = function()
{
    let upc = document.getElementById('cur-upc').innerHTML;
    let price = document.getElementById('cur-normalprice').innerHTML;
    let srp = document.getElementById('cur-srp').innerHTML;
    srp = parseFloat(srp);
    price = parseFloat(price);
    let bid = (price > srp) ? batchIdRedux : batchIdInc ;
    addToBatch(upc, bid, srp);

    return false;
}

var btnRemoveFromBatch = function()
{
    let upc = document.getElementById('cur-upc').innerHTML;
    let price = document.getElementById('cur-normalprice').innerHTML;
    let srp = document.getElementById('cur-srp').innerHTML;
    let bid = (parseInt(price, 10) > parseInt(srp, 10)) ? batchIdRedux : batchIdInc ;
    removeFromBatch(upc, bid);

    return false;
}

var postActionBatchCleanup = function()
{
    $.ajax({
        type: 'post',
        url: 'VPBPIV.php',
        data: 'cleanup=1&bid[]='+batchIdInc+'&bid[]='+batchIdRedux,
        success: function(r) {
            window.location.href ='../newbatch/BatchListPage.php';
        }
    });
}

let btnEditPrice = function()
{
    let input = document.getElementById('cur-srp');
    let upc = document.getElementById('cur-upc').innerHTML;
    input.contentEditable = true;
    input.focus();
    //var td = document.getElementById('id'+upc);
    //let newValue = document.getElementById('cur-srp').innerHTML;
    //td.setAttribute('data-srp', '123');
}

document.addEventListener('keydown', function(e) {
    let key = e.keyCode;
    switch(key) {
        case 40:
            btnChangeSelected('down');
            break;
        case 38:
            btnChangeSelected('up');
            break; 
        case 39:
            btnAddToBatch();
            btnChangeSelected('down');
            break; 
        case 37:
            btnRemoveFromBatch();
            btnChangeSelected('down');
            break; 
        case 32:
            btnEditPrice();
            break; 
        break;
    }
});

// prevent window from scrolling on keydown
window.addEventListener("keydown", function(e) {
    if(["Space","ArrowUp","ArrowDown","ArrowLeft","ArrowRight"].indexOf(e.code) > -1) {
        e.preventDefault();
    }
}, false)

$(window).load(function(){
    btnChangeSelected('down'); 
    btnChangeSelected('up'); 
});

$('tr.item').each(function(){
    var dataElement = $(this).find('td.sub'); 
    let srp = dataElement.attr('data-round');
    let price = dataElement.attr('data-normalprice');
    let calculated = dataElement.attr('data-srp');
    if (parseFloat(srp) == parseFloat(price)) {
        //$(this).hide();
        //alert(srp+', '+price+', hide this row!');
        $(this).css('background', 'tomato');
    } else if (srp == calculated) {
        $(this).css('background', '#76EE00');
    }
});
JAVASCRIPT;
    }

    public function helpContent()
    {
        return parent::helpContent();
    }

    public function unitTest($phpunit)
    {
        $this->id = 1;
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
    }

    public function getTable()
    {
        $this->addScript('pricing-batch-II.js');
        $dbc = $this->connection;
        $dbc->selectDB($this->config->OP_DB);
        $ret = '';

        $superID = FormLib::get('super', -1);
        $queueID = FormLib::get('queueID');
        $vendorID = $this->id;
        $filter = FormLib::get_form_value('filter') == 'Yes' ? True : False;

        $batched = $this->getBatchedItems();

        /* lookup vendor and superdept names to build a batch name */
        $sname = "All";
        if ($superID >= 0) {
            $smodel = new SuperDeptNamesModel($dbc);
            $smodel->superID($superID);
            $smodel->load();
            $sname = $smodel->super_name();
        }
        $vendor = new VendorsModel($dbc);
        $vendor->vendorID($vendorID);
        $vendor->load();

        $batchName = $sname." ".$vendor->vendorName()." PC ".date('m/d/y');

        $batchNames = array();
        $batchNames[] = $sname." ".$vendor->vendorName()." PC ".date('m/d/y') . " INC.";
        $batchNames[] = $sname." ".$vendor->vendorName()." PC ".date('m/d/y') . " REDUX.";

        /* find a price change batch type */
        $types = new BatchTypeModel($dbc);
        $types->discType(0);
        $bType = 0;
        foreach ($types->find() as $obj) {
            $bType = $obj->batchTypeID();
            break;
        }

        /* get the ID of the current batches. Create it if needed. */
        $batchIDs = array(0, 0);
        foreach ($batchNames as $k => $bName) {
            $bidQ = $dbc->prepare("
                SELECT batchID
                FROM batches
                WHERE batchName=?
                    AND batchType=?
                    AND discounttype=0
                ORDER BY batchID DESC");
            //$bidR = $dbc->execute($bidQ,array($batchName,$bType));
            $bidR = $dbc->execute($bidQ,array($bName,$bType));
            list($owner) = explode(' ', $bName);
            if ($dbc->numRows($bidR) == 0) {
                $b = new BatchesModel($dbc);
                $b->batchName($bName);
                $b->startDate('1900-01-01');
                $b->endDate('1900-01-01');
                $b->batchType($bType);
                $b->discountType(0);
                $b->priority(0);
                $b->owner($owner);
                $batchIDs[$k] = $b->save();
                $bu = new BatchUpdateModel($dbc);
                $bu->batchID($batchIDs[$k]);
                $bu->logUpdate($bu::UPDATE_CREATE);
                if ($this->config->get('STORE_MODE') === 'HQ') {
                    StoreBatchMapModel::initBatch($batchIDs[$k]);
                }
            } else {
                $bidW = $dbc->fetchRow($bidR);
                $batchIDs[$k] = $bidW['batchID'];
            }
        }

        // this wont exist anymore
        //$ret = sprintf('<b>Batch</b>:
        //            <a href="%sbatches/newbatch/BatchManagementTool.php?startAt=%d">%s</a>',
        //            $this->config->URL,
        //            $batchID,
        //            $batchName);
        $addHTML = '';
        foreach ($batchIDs as $k => $id) {
            $addHTML.= "<input type=hidden id=batchID$k value=$id /> ";
            $addHTML.= sprintf("<input type=hidden id=vendorID value=%d />
                <input type=hidden id=queueID value=%d />
                <input type=hidden id=superID value=%d />",
                $vendorID,$queueID,$superID);
            $ret .= sprintf("<input type=hidden id=vendorID value=%d />
                <input type=hidden id=batchID value=%d />
                <input type=hidden id=queueID value=%d />
                <input type=hidden id=superID value=%d />",
                $vendorID,$id,$queueID,$superID);
        }
        $ret .= '<br/><b>View: </b> 
            <button class="btn btn-danger btn-xs btn-filter active" data-filter-type="red">Red</button> 
            | <button class="btn btn-warning btn-xs btn-filter active" data-filter-type="yellow">Yellow</button> 
            | <button class="btn btn-success btn-xs btn-filter active" data-filter-type="green-green">Green</button> 
            | <button class="btn btn-success btn-xs btn-filter active" data-filter-type="green-red">Green
                 <span style="text-shadow: -1px -1px 0 crimson, 1px -1px 0 crimson, -1px 1px 0 crimson, 1px 1px crimson;">Red</span></button> 
            | <button class="btn btn-default btn-xs btn-filter active" data-filter-type="white">White</button> 
            | <button class="btn btn-default btn-xs multi-filter active" data-filter-type="multiple">
                <span class="glyphicon glyphicon-exclamation-sign" title="View only rows containing multiple SKUs"> </span>
            </button> 
            | <input type="" class="date-field" id="reviewed" placeholder="Reviewed on" style="border: 1px solid lightgrey; border-radius: 3px;"/>
            <br/><br/>';

        $batchUPCs = array();
        foreach ($batchIDs as $batchID) {
            $batchList = new BatchListModel($dbc);
            $batchList->batchID($batchID);
            foreach ($batchList->find() as $obj) {
                $batchUPCs[$obj->upc()] = true;
            }
        }

        $costSQL = Margin::adjustedCostSQL('p.cost', 'b.discountRate', 'b.shippingMarkup');
        $marginSQL = Margin::toMarginSQL($costSQL, 'p.normal_price');
        $marginCase = '
            CASE
                WHEN g.margin IS NOT NULL AND g.margin <> 0 THEN g.margin
                WHEN s.margin IS NOT NULL AND s.margin <> 0 THEN s.margin
                ELSE d.margin
            END';
        $srpSQL = Margin::toPriceSQL($costSQL, $marginCase);

        $aliasP = $dbc->prepare("
            SELECT v.srp,
                v.vendorDept,
                a.multiplier
            FROM VendorAliases AS a
                INNER JOIN vendorItems AS v ON a.sku=v.sku AND a.vendorID=v.vendorID
            WHERE a.upc=?");

        $vidsStart = FormLib::get('forcedStart', false);
        $vidsEnd = FormLib::get('forcedEnd', false);
        $query = "SELECT p.upc,
            p.description,
            p.brand,
            p.cost,
            b.shippingMarkup,
            b.discountRate,
            p.normal_price,
            " . Margin::toMarginSQL($costSQL, 'p.normal_price') . " AS current_margin,
            " . Margin::toMarginSQL($costSQL, 'v.srp') . " AS desired_margin,
            " . $costSQL . " AS adjusted_cost,
            v.srp,
            " . $srpSQL . " AS rawSRP,
            v.vendorDept,
            b.vendorName,
            p.price_rule_id AS variable_pricing,
            prt.priceRuleTypeID,
            prt.description AS prtDesc,
            " . $marginCase . " AS margin,
            CASE WHEN a.sku IS NULL THEN 0 ELSE 1 END as alias,
            CASE WHEN l.upc IS NULL THEN 0 ELSE 1 END AS likecoded,
            c.difference,
            c.date,
            r.reviewed,
            v.sku,
            m.super_name AS superName
            FROM products AS p
                LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                LEFT JOIN VendorAliases AS a ON p.upc=a.upc AND p.default_vendor_id=a.vendorID
                INNER JOIN vendors as b ON v.vendorID=b.vendorID
                LEFT JOIN departments AS d ON p.department=d.dept_no
                LEFT JOIN vendorDepartments AS s ON v.vendorDept=s.deptID AND v.vendorID=s.vendorID
                LEFT JOIN VendorSpecificMargins AS g ON p.department=g.deptID AND v.vendorID=g.vendorID
                LEFT JOIN upcLike AS l ON v.upc=l.upc 
                LEFT JOIN productCostChanges AS c ON p.upc=c.upc 
                LEFT JOIN prodReview AS r ON p.upc=r.upc
                LEFT JOIN PriceRules AS pr ON p.price_rule_id=pr.priceRuleID
                LEFT JOIN PriceRuleTypes AS prt ON pr.priceRuleTypeID=prt.priceRuleTypeID
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE v.cost > 0
            # AND v.srp > p.normal_price
        ";
        // AND pr.priceRuleTypeID NOT IN (5, 6, 7, 8, 12)

        //# TEMPORARILY DISABLING PRICE REDUCTIONS
        //AND v.srp > p.normal_price

        if ($vidsStart != false && $vidsEnd != false) {
            $ret .= "<h3 align=\"center\">Multiple Vendor View</h3>";
            $vidsA = array($vidsStart, $vidsEnd);
            $vidsP = $dbc->prepare("SELECT * FROM batchReviewLog WHERE forced >= ? AND forced < ? GROUP BY vid;");
            $vidsR = $dbc->execute($vidsP, $vidsA);
            $vids = array();
            while ($row = $dbc->fetchRow($vidsR)) {
                $vids[$row['vid']] = $row['vid'];
            }
            list($inStr, $args) = $dbc->safeInClause($vids);
            $query .= " AND v.vendorID IN ($inStr) ";
        } else {
            $args = array($vendorID);
            $query .= " AND v.vendorID = ? ";
        }

        $query .= " AND m.SuperID IN (1, 3, 4, 5, 8, 9, 13, 17, 18) ";
        if ($superID == -2) {
            $query .= " AND m.superID<>0 ";
        } elseif ($superID != -1) {
            $query .= " AND m.superID=? ";
            $args[] = $superID;
        }
        if ($filter === false) {
            $query .= " AND p.normal_price <> v.srp ";
        }
        if ($this->config->get('STORE_MODE') == 'HQ') {
            $query .= ' AND p.store_id=? ';
            $args[] = $this->config->get('STORE_ID');
        }

        $query .= ' AND p.upc IN (SELECT upc FROM products WHERE inUse = 1) ';

        $query .= " ORDER BY p.upc";

        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep,$args);

        $vendorModel = new VendorItemsModel($dbc);
        //$td.= "<div class=\"table-responsive\"><table class=\"uniq-table table table-condensed table table-bordered small\" id=\"uniq-table\">";

        $td = "";
        $td.= "<thead><tr class=\"thead\" id=\"uniq-thead\">
            <th class=\"thead\">UPC</th>
            <th class=\"thead\">SKU</th>
            <th class=\"thead\">Brand</th>
            <th class=\"thead\">Description</th>
            <th class=\"thead\">Vendor</th>
            <th class=\"thead\">Owner</th>
            <th class=\"thead\"></th>
            </thead><tbody><tr></tr>";
        $rounder = new PriceRounder();
        while ($row = $dbc->fetch_row($result)) {
            $vendorModel->reset();
            $vendorModel->upc($row['upc']);
            $vendorModel->vendorID($vendorID);
            $vendorModel->load();
            $numRows = $vendorModel->find();
            $multipleVendors = '';
            if (count($numRows) > 1) {
                $multipleVendors = '<span class="glyphicon glyphicon-exclamation-sign"
                    title="Multiple SKUs For This Product">
                    </span> ';
            }
            if ($row['alias']) {
                //$alias = $dbc->getRow($aliasP, array($row['upc']));
                //$row['vendorDept'] = $alias['vendorDept'];
                //$row['srp'] = $alias['srp'] * $alias['multiplier'];
                //$row['srp'] = $rounder->round($row['srp']);
            }
            $differenceVisual = '';
            $background = "white";
            $acceptPrtID = array(1, 10);
            if (isset($batchUPCs[$row['upc']]) && !$row['likecoded']) {
                $background = 'selection';
            } elseif (in_array($row['priceRuleTypeID'], $acceptPrtID) || $row['variable_pricing'] == 0 && $row['normal_price'] < 10.00) {
                $background = (
                    ($row['normal_price']+0.10 < $row['rawSRP'])
                    && ($row['srp']-.14 > $row['normal_price']
                    && $row['rawSRP'] - floor($row['rawSRP']) > 0.10)
                ) ?'red':'green-green';
                if ($row['normal_price']-.10 > $row['rawSRP']) {
                    $background = (
                        ($row['normal_price']-.10 > $row['rawSRP'])
                        && ($row['normal_price']-.14 > $row['srp'])
                        && ($row['rawSRP'] < $row['srp']+.10)
                    )?'yellow':'green-green';
                }
            } elseif (in_array($row['priceRuleTypeID'], $acceptPrtID) || $row['variable_pricing'] == 0 && $row['normal_price'] >= 10.00) {
                $background = ($row['normal_price'] < $row['rawSRP']
                    && $row['srp'] > $row['normal_price']
                    && $row['rawSRP'] - floor($row['rawSRP']) > 0.10
                    ) ?'red':'green-green';
                if ($row['normal_price']-0.49 > $row['rawSRP']) {
                    $background = ($row['normal_price']-0.49 > $row['rawSRP']
                        && ($row['normal_price'] > $row['srp'])
                        && ($row['rawSRP'] < $row['srp']+.10) )?'yellow':'green-green';
                }
            }
            if (isset($batchUPCs[$row['upc']])) {
                $icon = '<span class="glyphicon glyphicon-minus-sign"
                    title="Remove from batch">
                    </span>';
            } else {
                $icon = '<span class="glyphicon glyphicon-plus-sign"
                    title="Add to batch">
                    </span>';
            }
            $brand = substr($row['brand'], 0, 20);
            $symb = ($row['difference'] > 0) ? "+" : "";
            $cleanDate = $row['date'];
            $row['date'] = ($row['date']) ? "<span class='grey'> <i>on</i> </span> ".$row['date'] : "";
            $change = $row['srp'] - $row['normal_price'];
            //if (abs($change) > 1.99) {
            //    $change = (abs($change) < 1.99) ? 0 : round($change / 2);
            //    $row['srp'] = $row['srp'] - $change;
            //    $row['srp'] = $rounder->round($row['srp']);
            //}
            //if (abs(abs($row['normal_price']) - abs($row['rawSRP'])) < 0.03)
            //    continue;
            $date = new DateTime();
            $date = $date->format('Y-m-d');
            $changeClassA = ($date == substr($row['date'], -10)) ? 'highlight' : '';
            /* 
            $changeClassA = ('2022-07-06' == substr($row['date'], -10)) ? 'highlight' : '';
            */

            $changeClassB = ($date == $row['reviewed']) ? 'highlight' : '';
            /*
            $changeClassB = ('2022-07-06' == $row['reviewed']) ? 'highlight' : '';
            */

            $srpClassA = ($row['srp'] > $row['normal_price']) ? 'red' : 'yellow';
            $direction = ($row['srp'] > $row['normal_price']) ? '&#x2191;' : '&#x2193;';

            $row['inbatch'] = isset($batched[$row['upc']]) ? $batched[$row['upc']] : '';
            $td .= sprintf("<tr id=row%s class='%s %s item'>
                <td class=\"sub\" 
                    data-upc=\"%s\"
                    data-cost=\"%s\" 
                    data-margin=\"%.2f\"
                    data-target=\"%s\"
                    data-diff=\"%s\"
                    data-change=\"<span class='$changeClassA'>%s %s on %s</span>\"
                    data-description=\"%s\"
                    data-brand=\"%s\"
                    data-reviewed=\"<span class='$changeClassB'>%s</span>\"
                    data-basecost=\"%s\" 
                    data-round=\"%s\"
                    data-raw=\"%s\"
                    data-srp=\"%s\"
                    data-srpvisual=\"<span class='$srpClassA'>%s</span>\"
                    data-pricerule=\"<span class='white'>%s</span>\"
                    data-inbatch=\"<span class='white'>%s</span>\"
                    data-normalprice=\"%s\"
                    id=\"id%s\"
                    ><a href=\"%sitem/ItemEditorPage.php?searchupc=%s\" target=\"_blank\">%s</a></td>
                <td class=\"sub sku\">%s</td>
                <td class=\"sub\">%s</td>
                <td class=\"sub\">%s</td>
                <td class=\"sub vendorName\">%s</td>
                <td class=\"sub SuperName\">%s</td>
                </tr>",
                $row['upc'],
                $background, $mtp = ($multipleVendors == '') ? '' : 'multiple',
                // DATASETS datasets
                $row['upc'], $row['adjusted_cost'], 100*$row['current_margin'], 100*$row['margin'], 
                    round(100*$row['current_margin'] - 100*$row['margin'], 2),
                    /*
                        This is where I'm working datasets
                    */
                    $symb, $row['difference'], $cleanDate,
                    $row['description'], $brand, $row['reviewed'], $row['cost'],
                    $rounder->round($row['rawSRP']),
                    round($row['rawSRP'],3), $row['srp'], $direction, $row['prtDesc'], $row['inbatch'], $row['normal_price'], $row['upc'],
                    /*
                        My work area end's here
                    */
                $this->config->URL, $row['upc'], $row['upc'], 
                $row['sku'],
                $temp = (strlen($brand) == 10) ? "$brand~" : $brand,
                $row['description'] . ' ' . $multipleVendors,
                //$row['adjusted_cost'],
                $row['vendorName'],
                $row['superName'],
                $row['normal_price']
            );
        }
        $td.= "<tr></tr><tr></tr><tr></tr></tbody>";

        return $td . $addHTML;
    }
}

FannieDispatch::conditionalExec();
