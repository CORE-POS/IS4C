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
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class VendorPricingBatchPage extends FannieRESTfulPage 
{
    protected $title = "Fannie - Create Price Change Batch";
    protected $header = "Create Price Change Batch";

    public $description = '[Vendor Price Change] creates a price change batch for a given
    vendor and edits it based on catalog cost information.';
    public $themed = true;

    protected $auth_classes = array('batches');
    protected $must_authenticate = true;

    private $mode = 'start';

    public function css_content()
    {
        return '
        tr.green td.sub {
            background:#ccffcc;
        }
        tr.red td.sub {
            background:#F7BABA;
        }
        tr.white td.sub {
            background:#ffffff;
        }
        tr.selection td.sub {
            background:#add8e6;
        }
        td.srp {
            text-decoration: underline;
        }';
    }

    public function javascript_content()
    {
        ob_start();
        ?>
var vid = null;
var bid = null;
var sid = null;
var qid = null;
$(document).ready(function(){
    vid = $('#vendorID').val();
    bid = $('#batchID').val();
    sid = $('#superID').val();
    qid = $('#queueID').val();
});
function addToBatch(upc)
{
    var dstr = "upc="+upc+"&vendorID="+vid+"&queueID="+qid+"&batchID="+bid;
    var price = $('#row'+upc).find('.srp').html();
    $.ajax({
        url: 'batchAjax.php',
        data: dstr + '&action=batchAdd&price='+price,
        success: function(data){
            $('#row'+upc).attr('class','selection');
            $('#row'+upc).find('.add-button').hide();
            $('#row'+upc).find('.remove-button').show();
        }
    });
}
function removeFromBatch(upc)
{
    var dstr = "upc="+upc+"&vendorID="+vid+"&queueID="+qid+"&batchID="+bid;
    $.ajax({
        url: 'batchAjax.php',
        data: dstr + '&action=batchDel',
        success: function(data){
            if ($('tr#row'+upc+' input.varp:checked').length > 0)
                $('#row'+upc).attr('class','white');
            else if ($('tr#row'+upc+' td.price').html() < $('tr#row'+upc+' td.srp').html())
                $('#row'+upc).attr('class','red');
            else
                $('#row'+upc).attr('class','green');

            $('#row'+upc).find('.add-button').show();
            $('#row'+upc).find('.remove-button').hide();
        }
    });
}
function toggleV(upc){
    var val = $('#row'+upc).find('.varp').prop('checked');
    if (val){
        $('#row'+upc).attr('class','white');
        $.ajax({
            url: 'batchAjax.php',
            data: 'action=addVarPricing&upc='+upc,
            success: function(data){

            }
        });
    }
    else {
        var m1 = $('#row'+upc).find('.cmargin').html();
        var m2 = $('#row'+upc).find('.dmargin').html();
        if (m1 >= m2)
            $('#row'+upc).attr('class','green');
        else
            $('#row'+upc).attr('class','red');
        $.ajax({
            url: 'batchAjax.php',
            data: 'action=delVarPricing&upc='+upc,
            success: function(data){

            }
        });
    }
}

function reprice(upc){
    if ($('#newprice'+upc).length > 0) return;

    var elem = $('#row'+upc).find('.srp');
    var srp = elem.html();

    var content = "<div class=\"form-inline input-group\"><span class=\"input-group-addon\">$</span>";
    content += "<input type=\"text\" id=\"newprice"+upc+"\" value=\""+srp+"\" class=\"form-control\" size=4 /></div>";
    var content2 = "<button type=\"button\" onclick=\"saveprice('"+upc+"');\" class=\"btn btn-default\">Save</button>";
    elem.html(content);
    $('#row'+upc).find('.dmargin').html(content2);
    $('#newprice'+upc).focus().select();
}

function saveprice(upc){
    var srp = parseFloat($('#newprice'+upc).val());
    var cost = parseFloat($('#row'+upc).find('.adj-cost').html());
    var newmargin = (srp - cost) / srp;
    newmargin *= 100;
    newmargin = Math.round(newmargin*100)/100;

    $('#row'+upc).find('.srp').html(srp);
    $('#row'+upc).find('.dmargin').html(newmargin+'%');

    var dstr = "upc="+upc+"&vendorID="+vid+"&queueID="+qid+"&batchID="+bid;
    $.ajax({
        url: 'batchAjax.php',
        data: dstr+'&action=newPrice&price='+srp+'&batchID='+bid,
        cache: false,
        success: function(data){}
    });
}
        <?php
        return ob_get_clean();
    }

    public function get_id_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->OP_DB);

        $superID = FormLib::get_form_value('super',99);
        $queueID = FormLib::get('queueID');
        $vendorID = $this->id;
        $filter = FormLib::get_form_value('filter') == 'Yes' ? True : False;

        /* lookup vendor and superdept names to build a batch name */
        $sn = "All";
        if ($superID != 99) {
            $s = new SuperDeptNamesModel($dbc);
            $s->superID($superID);
            $s->load();
            $sn = $s->super_name();
        }
        $vendor = new VendorsModel($dbc);
        $vendor->vendorID($vendorID);
        $vendor->load();

        $batchName = $sn." ".$vendor->vendorName()." PC ".date('m/d/y');

        /* find a price change batch type */
        $types = new BatchTypeModel($dbc);
        $types->discType(0);
        $bType = 0;
        foreach ($types->find() as $obj) {
            $bType = $obj->batchTypeID();
            break;
        }

        /* get the ID of the current batch. Create it if needed. */
        $bidQ = $dbc->prepare("
            SELECT batchID 
            FROM batches 
            WHERE batchName=? 
                AND batchType=? 
                AND discounttype=0
            ORDER BY batchID DESC");
        $bidR = $dbc->execute($bidQ,array($batchName,$bType));
        $batchID = 0;
        if ($dbc->numRows($bidR) == 0) {
            $b = new BatchesModel($dbc);
            $b->batchName($batchName);
            $b->startDate('1900-01-01');
            $b->endDate('1900-01-01');
            $b->batchType($bType);
            $b->discounttype(0);
            $b->priority(0);
            $batchID = $b->save();
        } else { 
            $bidW = $dbc->fetchRow($bidR);
            $batchID = $bidW['batchID'];
        }

        $ret = sprintf('<b>Batch</b>: 
                    <a href="%sbatches/newbatch/BatchManagementTool.php?startAt=%d">%s</a>',
                    $this->config->URL,
                    $batchID,
                    $batchName);
        $ret .= sprintf("<input type=hidden id=vendorID value=%d />
            <input type=hidden id=batchID value=%d />
            <input type=hidden id=queueID value=%d />
            <input type=hidden id=superID value=%d />",
            $vendorID,$batchID,$queueID,$superID);

        $batchUPCs = array();
        $bl = new BatchListModel($dbc);
        $bl->batchID($batchID);
        foreach ($bl->find() as $obj) {
            $batchUPCs[$obj->upc()] = true;
        }

        $costSQL = Margin::adjustedCostSQL('v.cost', 'b.discountRate', 'b.shippingMarkup');
        $marginSQL = Margin::toMarginSQL($costSQL, 'p.normal_price');
        $p_def = $dbc->tableDefinition('products');
        $srpSQL = Margin::toPriceSQL($costSQL, 'CASE WHEN s.margin IS NULL OR s.margin=0 THEN d.margin ELSE s.margin END');

        $query = "SELECT p.upc,
            p.description,
            v.cost,
            b.shippingMarkup,
            b.discountRate,
            p.normal_price,
            " . Margin::toMarginSQL($costSQL, 'p.normal_price') . " AS current_margin,
            " . Margin::toMarginSQL($costSQL, 'v.srp') . " AS desired_margin,
            " . $costSQL . " AS adjusted_cost,
            v.srp,
            " . $srpSQL . " AS rawSRP,
            v.vendorDept,
            x.variable_pricing,
            CASE WHEN s.margin IS NULL OR s.margin=0 THEN d.margin ELSE s.margin END AS margin
            FROM products AS p 
                INNER JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                INNER JOIN vendors as b ON v.vendorID=b.vendorID
                LEFT JOIN departments AS d ON p.department=d.dept_no
                LEFT JOIN vendorDepartments AS s ON v.vendorDept=s.deptID AND v.vendorID=s.vendorID
                LEFT JOIN prodExtra AS x on p.upc=x.upc ";
        $args = array($vendorID);
        if ($superID != 99){
            $query .= " LEFT JOIN MasterSuperDepts AS m
                ON p.department=m.dept_ID ";
        }
        $query .= "WHERE v.cost > 0 
                    AND v.vendorID=?
                    AND p.inUse=1 ";
        if ($superID != 99) {
            $query .= " AND m.superID=? ";
            $args[] = $superID;
        }
        if ($filter === false) {
            $query .= " AND p.normal_price <> v.srp ";
        }

        $query .= " ORDER BY p.upc";
        if (isset($p_def['price_rule_id'])) {
            $query = str_replace('x.variable_pricing', 'p.price_rule_id AS variable_pricing', $query);
        }

        $prep = $dbc->prepare_statement($query);
        $result = $dbc->exec_statement($prep,$args);

        $ret .= "<table class=\"table table-bordered small\">";
        $ret .= "<tr><td colspan=6>&nbsp;</td><th colspan=2>Current</th>
            <th colspan=3>Vendor</th></tr>";
        $ret .= "<tr><th>UPC</th><th>Our Description</th>
            <th>Base Cost</th>
            <th>Shipping</th>
            <th>Discount%</th>
            <th>Adj. Cost</th>
            <th>Price</th><th>Margin</th><th>Raw</th><th>SRP</th>
            <th>Margin</th><th>Cat</th><th>Var</th>
            <th>Batch</th></tr>";
        while ($row = $dbc->fetch_row($result)) {
            $bg = "white";
            if (isset($batchUPCs[$row['upc']])) {
                $bg = 'selection';
            } elseif ($row['variable_pricing'] == 0) {
                $bg = ($row['normal_price']<$row['rawSRP'])?'red':'green';
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
            $ret .= sprintf("<tr id=row%s class=%s>
                <td class=\"sub\"><a href=\"%sitem/ItemEditorPage.php?searchupc=%s\">%s</a></td>
                <td class=\"sub\">%s</td>
                <td class=\"sub cost\">%.2f</td>
                <td class=\"sub shipping\">%.2f%%</td>
                <td class=\"sub discount\">%.2f%%</td>
                <td class=\"sub adj-cost\">%.2f</td>
                <td class=\"sub price\">%.2f</td>
                <td class=\"sub cmargin\">%.2f%%</td>
                <td class=\"sub raw-srp\">%.2f</td>
                <td onclick=\"reprice('%s');\" class=\"sub srp\">%.2f</td>
                <td class=\"sub dmargin\">%.2f%%</td>
                <td class=\"sub\">%d</td>
                <td><input class=varp type=checkbox onclick=\"toggleV('%s');\" %s /></td>
                <td class=white>
                    <a class=\"add-button %s\" href=\"\" 
                        onclick=\"addToBatch('%s'); return false;\">
                        <span class=\"glyphicon glyphicon-plus-sign\"
                            title=\"Add item to batch\"></span>
                    </a>
                    <a class=\"remove-button %s\" href=\"\" 
                        onclick=\"removeFromBatch('%s'); return false;\">
                        <span class=\"glyphicon glyphicon-minus-sign\"
                            title=\"Remove item from batch\"></span>
                    </a>
                </td>
                </tr>",
                $row['upc'],
                $bg,
                $this->config->URL, $row['upc'], $row['upc'],
                $row['description'],
                $row['cost'],
                $row['shippingMarkup']*100,
                $row['discountRate']*100,
                $row['adjusted_cost'],
                $row['normal_price'],
                100*$row['current_margin'],
                $row['rawSRP'],
                $row['upc'],
                $row['srp'],
                100*$row['desired_margin'],
                $row['vendorDept'],
                $row['upc'],
                ($row['variable_pricing']==1?'checked':''),
                (isset($batchUPCs[$row['upc']])?'collapse':''), $row['upc'],
                (!isset($batchUPCs[$row['upc']])?'collapse':''), $row['upc']
            );
        }
        $ret .= "</table>";

        return $ret;
    }

    public function get_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->OP_DB);

        $p = $dbc->prepare("
            SELECT superID,
                super_name 
            FROM MasterSuperDepts
            WHERE superID > 0
            GROUP BY superID,
                super_name");
        $res = $dbc->execute($p);
        $opts = "<option value=99 selected>All</option>";
        while ($row = $dbc->fetch_row($res)) {
            $opts .= "<option value=$row[0]>$row[1]</option>";
        }

        $v = new VendorsModel($dbc);
        $vopts = "";
        foreach ($v->find('vendorName') as $obj) {
            $vopts .= sprintf('<option value="%d">%s</option>',
                $obj->vendorID(), $obj->vendorName());
        }

        $queues = new ShelfTagQueuesModel($dbc);
        $qopts = $queues->toOptions();

        ob_start();
        ?>
        <form action=VendorPricingBatchPage.php method="get">
        <label>Select a Vendor</label>
        <select name="id" class="form-control">
        <?php echo $vopts; ?>
        </select>
        <label>and a Super Department</label>
        <select name=super class="form-control">
        <?php echo $opts; ?>
        </select>
        <label>Show all items</label>
        <select name=filter class="form-control">
        <option>No</option>
        <option>Yes</option>
        </select>
        <label>Shelf Tag Queue</label>
        <select name="queueID" class="form-control">
        <?php echo $qopts; ?>
        </select>
        <br />
        <p>
        <button type=submit class="btn btn-default">Continue</button>
        </p>
        </form>
        <?php

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>Review products from the vendor with current vendor cost,
            retail price, and margin information. The tool creates a price
            change batch in the background. It will add items to this batch
            and automatically create shelf tags.</p>
            <p>The default <em>Show all items</em> setting, No, omits items
            whose current retail price is identical to the margin-based
            suggested retail price.</p>
            ';
    }


}

FannieDispatch::conditionalExec();

?>
