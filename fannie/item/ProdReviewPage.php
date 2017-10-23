<?php
/*******************************************************************************

    Copyright 2017 Whole Foods Community Co-op

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

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ProdReviewPage extends FannieRESTfulPage
{
    protected $header = 'Vendor Product Info Review';
    protected $title = 'Vendor Product Info Review';

    public $description = '[Vendor Prodct Info Review] keep a record of the
        last time product info was verified/updated for individual products.';

    function preprocess()
    {
        $this->__routes[] = 'get<upc>';
        $this->__routes[] = 'get<upc><save>';
        $this->__routes[] = 'get<list>';
        $this->__routes[] = 'get<list><save>';
        $this->__routes[] = 'get<vendor>';
        $this->__routes[] = 'get<vendor><checked>';
        $this->__routes[] = 'get<batchLog>';
        $this->__routes[] = 'get<batchLog><add>';
        $this->__routes[] = 'get<batchLog><force>';
        $this->__routes[] = 'get<batchLog><print>';
        $this->__routes[] = 'get<batchLog><printAll>';
        $this->__routes[] = 'get<schedule>';
        return parent::preprocess();
    }

    public function get_schedule_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $args = array();
        $prep = $dbc->prepare("
            SELECT vendorID, vendorName, p.upc, s.rate, r.reviewed
            FROM vendors AS v
                LEFT JOIN products AS p ON v.vendorID=p.default_vendor_id
                LEFT JOIN prodReview AS r ON p.upc=r.upc
                LEFT JOIN vendorReviewSchedule AS s ON v.vendorID=s.vid
            WHERE p.inUse = 1
                AND r.reviewed is not NULL
            GROUP BY p.upc
            limit 5;
        ");
        $res = $dbc->execute($prep);
        $vendors = array();
        while ($row = $dbc->fetchRow($res)) {
            $vid = $row['vendorID'];
            $vendors[$vid]['name'] = $row['vendorName']; 
            $curDate = date('Y-m-d');
            /*
                Maybe just grab once the rate for each vendor, 
                figure out how many months to look back, then 
                go through each vendor and add up the good / total.
            */
        }

        foreach($vendors as $vid => $v) {
            echo $vid . '<br/>';
        }
        return <<<HTML
hi!
HTML;
    }

    public function get_batchLog_printAll_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $prep = $dbc->prepare("UPDATE batchReviewLog SET printed = 1 WHERE printed = 0");
        $dbc->execute($prep);

        return header('location: '.$_SERVER['PHP_SELF'].'?batchLog=1');
    }

    public function get_batchLog_add_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $bid = FormLib::get('bid');

        $getA = array($bid);
        $getP = $dbc->prepare("
            SELECT bl.*, p.default_vendor_id AS vid,
                v.vendorName, b.batchName
            FROM batchList AS bl
                LEFT JOIN products AS p ON bl.upc=p.upc
                LEFT JOIN vendors AS v ON p.default_vendor_id=v.vendorID
                LEFT JOIN batches AS b ON bl.batchID=b.batchID
            WHERE bl.batchID = ?;");
        $getR = $dbc->execute($getP,$getA);
        $firstItr = 0;
        while ($row = $dbc->fetchRow($getR)) {
            if ($firstItr === 0) {
                $first = $row['vid'];
                $vid = $row['vid'];
            }
            $temp = $row['vid'];
            if ($temp != $first) {
                $vid = 0;
            }
        }
        if ($vid == 0) {
            $vid  = "n/a";
        }
        $setA = array($bid,$vid,$user);
        $setP = $dbc->prepare("
            INSERT INTO batchReviewLog (bid, vid, printed, user, created, forced)
            VALUES (?, ?, 0, ?, NOW(), 0);
        ");
        $dbc->execute($setP,$setA);

        return header('location: '.$_SERVER['PHP_SELF'].'?batchLog=1');
    }

    public function get_batchLog_force_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $bid = FormLib::get('bid');
        $user = FannieAuth::getUID($this->current_user);
        if ($user == 0 || empty($user)) {
            $user = 'unknown';
        }

        $b = new BatchesModel($dbc);
        $b->forceStartBatch($bid);

        $args = array($user,$bid);
        $prep = $dbc->prepare("UPDATE batchReviewLog SET forced = NOW(), user = ? WHERE bid = ?");
        $dbc->execute($prep,$args);

        return header('location: '.$_SERVER['PHP_SELF'].'?batchLog=1');
    }

    public function get_batchLog_print_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $bid = FormLib::get('bid');

        $args = array($bid);
        $prep = $dbc->prepare("UPDATE batchReviewLog SET printed = 1 WHERE bid = ?");
        $dbc->execute($prep,$args);

        return header('location: '.$_SERVER['PHP_SELF'].'?batchLog=1');
    }

    public function get_batchLog_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $bid = FormLib::get('bid');
        $bidLn = "../batches/newbatch/EditBatchPage.php?id=".$bid;

        if ($bid) {
            $bData = '';
            $pArgs = array($bid);
            $pPrep = $dbc->prepare("SELECT bid FROM batchReviewLog WHERE bid = ?");
            $pRes = $dbc->execute($pPrep,$pArgs);
            $rows = $dbc->numRows($pRes);

            $args = array($bid);
            $prep = $dbc->prepare("
                SELECT b.batchName, b.batchID, b.owner, t.typeDesc
                FROM batches AS b
                    LEFT JOIN batchType AS t ON b.batchType=t.batchTypeID
                WHERE b.batchID = ?
            ");
            $res = $dbc->execute($prep,$args);
            while ($row = $dbc->fetchRow($res)) {
                $name = $row['batchName'];
                $owner = $row['owner'];
                $type = $row['typeDesc'];
                if ($type != 'Price Change') {
                    $type = "<span class='text-danger'>!".$type."</span>";
                }
            }
            if ($rows != 0) {
                $action = '<td class="alert alert-warning" align="center">Batch found in log</td>';
            } else {
                $action = "<td class='btn btn-default btn-sm' style='width:100%;
                    border: 1px solid lightgreen;' onClick='addBatch({$bid}); return false;'>
                    + Add to log</td>";
            }
            $bData .= "
                <div class='panel panel-default batchTable'>
                <table class='table table-condensed table-striped alert-success'>
                    <thead><th>Name</th><th>BatchID</th><th>Owner</th><th>BatchType</th><th></td></thead>
                    <tbody>
                        <tr><td><a href=\"{$bidLn}\" target=\"_blank\">{$bid}</a></td><td>{$name}</td><td>{$owner}</td><td>{$type}</td>
                        {$action}
                        </tr>
                    </tbody>
                </table>
                </div>
            ";
        }
        /*
            tableA = unforced batches | tableB = forced.
        */
        $pAllBtn = "<button class='btn btn-default btn-xs' style='border: 3px solid lightgreen;'
            onClick='printAll(); return false;'>Print All</button>";
        $tableA = "<table class='table table-condensed table-striped alert-warning'><thead><tr>
            <th>BatchID</th><th>Batch Name</th><th>VID</th><th>Vendor</th><th>Uploaded</th>
            <th>Comments</th><th>{$pAllBtn}</th><tr></thead><tbody>";
        $tableB = "<table class='table table-condensed table-striped small alert-info'><thead><tr>
            <th>BatchID</th><th>Batch Name</th><th>VID</th><th>Vendor</th><th>Forced On</th>
            <th>user</th><th>Comments</th><tr></thead><tbody>";
        $args = array();
        $prep = $dbc->prepare("
            SELECT l.vid, l.printed, l.user, l.created, l.forced,
                b.batchName, b.batchID, v.vendorName, l.comments
            FROM batchReviewLog AS l
                LEFT JOIN batches AS b ON l.bid=b.batchID
                LEFT JOIN vendors AS v ON l.vid=v.vendorID
        ");
        $res = $dbc->execute($prep,$args);
        while ($row = $dbc->fetchRow($res)) {
            $curBid = $row['batchID'];
            $curBidLn = "../batches/newbatch/EditBatchPage.php?id=".$curBid;
            if ($row['forced'] == '0000-00-00 00:00:00') {
                $tableA .= "<tr>";
                $tableA .= "<td class='bid'><a href=\"{$curBidLn}\" target=\"_blank\">{$curBid}</a></td>";
                $batchName = substr($row['batchName'],0,25);
                $tableA .= "<td>{$batchName}</td>";
                if ($row['vid'] == 0) {
                    $vid = "n/a";
                } else {
                    $vid = $row['vid'];
                }
                $tableA .= "<td>{$vid}</td>";
                $tableA .= "<td>{$row['vendorName']}</td>";
                $uploaded = substr($row['created'],0,10);
                $tableA .= "<td>{$uploaded}</td>";
                $tableA .= "<td><textarea name='comments' class='batchLogInput editable'
                    '/>{$row['comments']}</textarea></td>";
                $action = '';
                if ($row['printed'] == 0) {
                    $action = "<td class='btn btn-default btn-wide' style='border: 1px solid lightgreen;'
                        onClick='printBatch($curBid); return false;'>Print</td>";
                } else {
                    $action = "<td class='btn btn-default btn-wide' style='border: 1px solid tomato;'
                        onClick='forceBatch($curBid); return false;'>Force</td>";
                }
                $tableA .= $action;
                $tableA .= "</tr>";
            } else {
                $tableB .= "<tr>";
                $tableB .= "<td class='bid'><a href=\"{$curBidLn}\" target=\"_blank\">{$curBid}</a></td>";
                $batchName = substr($row['batchName'],0,25);
                $tableB .= "<td>{$batchName}</td>";
                $tableB .= "<td>{$row['vid']}</td>";
                $tableB .= "<td>{$row['vendorName']}</td>";
                $tableB .= "<td>{$row['forced']}</td>";
                $tableB .= "<td>{$row['user']}</td>";
                $tableB .= "<td>{$row['comments']}</td>";
                $tableB .= "</tr>";
            }
        }
        $tableA .= '</tbody></table>';
        $tableB .= '</tbody></table>';

        return <<<HTML
<div align="center">
    <div class="panel panel-info" style="max-width: 800px;">
        <div class="panel-heading">
            <span class="panel-header">Batch Review Log</span>
            <div style="position:relative;"><div style="position: absolute; top:-20px; left: 0px">
                <button class="btn btn-xs btn-primary backBtn"><span class="glyphicon glyphicon-chevron-left"></span></button>
            </div></div>
        </div>
        <div class="panel-body">
            <form method="get" class="form-inline">
                <div class="form-group">
                    <input type="number" class="form-control" name="bid" value="{$bid}"
                        autofocus placeholder="Enter Batch ID" style="max-width: 150px;"/>
                    <input type="hidden" name="batchLog" value="1"/>
                    <button type="submit" class="btn btn-default" value="1" name="getBatch">Load Batch</button>
                </div>
                <div id="alert"><div id="resp"></div></div>
            </form>
        </div>
        {$bData}
        <label class="text-warning">Un-Forced Batches</label>
        <div class="panel panel-default batchTable" style="overflow-x: auto;">
            {$tableA}
        </div>
        <label class="text-info">Forced Batches</label>
        <div class="panel panel-default batchTable" style="max-height: 600px; overflow-y: auto;">
            {$tableB}
        </div>
    </div>
</div>
HTML;
    }

    public function get_vendor_checked_handler()
    {

        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upcs = FormLib::get('checked');
        $user = FannieAuth::getUID($this->current_user);
        $pr = new ProdReviewModel($dbc);
        $data = array();
        $error = 0;
        foreach ($upcs as $upc) {
            $pr->reset();
            $pr->upc($upc);
            $pr->user($user);
            $pr->reviewed(date('Y-m-d'));
            if (!$pr->save()) {
                $error = 1;
            }
        }
        if (!$error) {
            header('Location: '.$_SERVER['PHP_SELF'].'?saved=true');
        } else {
            header('Location: '.$_SERVER['PHP_SELF'].'?saved=false');
        }

        return false;
    }

    public function get_vendor_view()
    {
        $vid = FormLib::get('vendor');

        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $p = new ProductsModel($dbc);
        $p->default_vendor_id($vid);
        $p->store_id(1);
        $p->inUse(1);

        $table = '<table class="table table-condensed small">';
        $table .= '<thead><th>UPC</th><th>Brand</th><th>Description</th>
            <th></th></thead><tbody><td></td><td></td><td></td><td></td><td>
            <input type="checkbox" id="checkAll" style="border: 1px solid red;"></td>';

        $pr = new ProdReviewModel($dbc);
        foreach ($p->find() as $obj) {
            $table .= '<tr>';
            $table .= '<td>'.$obj->upc().'</td>';
            $table .= '<td>'.$obj->brand().'</td>';
            $table .= '<td>'.$obj->description().'</td>';
            $pr->reset();
            $pr->upc($obj->upc());
            if ($pr->load()) {
                $table .= '<td>'.$pr->reviewed().'</td>';
            } else {
                $table .= '<td><i>no review date</i></td>';
            }
            $table .= '<td><input type="checkbox"class="chk" name="checked[]" value="'.$obj->upc().'"></td>';
            $table .= '</tr>';
        }
        $table .= '</tbody></table>';

        return <<<HTML
<form class="form-inline" method="get">
    {$table}
    <input type="hidden" name="vendor" value="1">
    <input type="submit" class="btn btn-warning" value="Mark checked items as Reviewed" />
</form><br />
HTML;
    }

    public function draw_table($data,$dbc)
    {
        $table = '<table class="table table-condensed small">';
        $table .= '<thead><th>UPC</th><th>Brand</th><th>Description</th>
            <th>Last Reviewed</th></thead><tbody>';
        $pr = new ProdReviewModel($dbc);
        $table .= '<tr>';
        foreach ($data as $upc => $arr) {
            foreach ($arr as $k => $v) {
                if ($k == 'upc') {
                    $pr->reset();
                    $pr->upc($v);
                    $table .= '<td>'.$v.'</td>';
                } elseif ($k == 'brand') {
                    $table .= '<td>'.$v.'</td>';
                } elseif ($k == 'description') {
                    $table .= '<td>'.$v.'</td>';
                    if ($pr->load()) {
                        $table .= '<td>'.$pr->reviewed().'</td>';
                    } else {
                        $table .= '<td><i>no review date</i></td>';
                    }
                    $table .= '</tr><tr>';
                }
            }

        }
        $table .= '</tr>';
        $table .= '</tbody></table>';

        return $table;
    }

    public function get_upc_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upc = BarcodeLib::padUPC(FormLib::get('upc'));
        $p = new ProductsModel($dbc);
        $p->upc($upc);
        $p->store_id(1);
        $data = array();
        foreach ($p->find() as $obj) {
            $data[$upc]['upc'] = $obj->upc();
            $data[$upc]['brand'] = $obj->brand();
            $data[$upc]['description'] = $obj->description();
        }
        $table = $this->draw_table($data,$dbc);
        return <<<HTML
<form class="form-inline" method="get">
    {$table}
    <input type="hidden" name="upc" value="{$upc}">
    <input type="hidden" name="save" value="1">
    <input type="submit" class="btn btn-warning" value="Mark as Reviewed" />
</form><br />
HTML;

    }

    public function get_upc_save_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upc = BarcodeLib::padUPC(FormLib::get('upc'));
        $user = FannieAuth::getUID($this->current_user);
        $pr = new ProdReviewModel($dbc);
        $pr->upc($upc);
        $pr->user($user);
        $pr->reviewed(date('Y-m-d'));
        if ($pr->save()) {
            header('Location: '.$_SERVER['PHP_SELF'].'?saved=true');
        } else {
            header('Location: '.$_SERVER['PHP_SELF'].'?saved=false');
        }

        return false;
    }

    public function get_list_save_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upcs = FormLib::get('upcs');
        $user = FannieAuth::getUID($this->current_user);
        $pr = new ProdReviewModel($dbc);
        $data = array();
        $error = 0;
        foreach ($upcs as $upc) {
            $pr->reset();
            $pr->upc($upc);
            $pr->user($user);
            $pr->reviewed(date('Y-m-d'));
            if (!$pr->save()) {
                $error = 1;
            }
        }
        if (!$error) {
            header('Location: '.$_SERVER['PHP_SELF'].'?saved=true');
        } else {
            header('Location: '.$_SERVER['PHP_SELF'].'?saved=false');
        }


        return false;
    }

    public function get_list_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $list = FormLib::get('list');
        $upcs = array();
        $plus = array();
        $chunks = explode("\r\n", $list);
        foreach ($chunks as $key => $str) {
            $upcs[] = BarcodeLib::padUPC($str);
        }
        $data = array();
        $p = new ProductsModel($dbc);
        $input = '';
        foreach ($upcs as $upc) {
            $p->reset();
            $p->upc($upc);
            foreach($p->find() as $obj) {
                $data[$upc]['upc'] = $obj->upc();
                $data[$upc]['brand'] = $obj->brand();
                $data[$upc]['description'] = $obj->description();
            }
            $input .= '<input type="hidden" name="upcs[]" value="'.$upc.'">';
        }
        $table = $this->draw_table($data,$dbc);

        return <<<HTML
<form class="form-inline" method="get">
    {$table}
    {$input}
    <input type="hidden" name="list" value="1">
    <input type="hidden" name="save" value="1">
    <input type="submit" class="btn btn-warning" value="Mark as Reviewed" />
</form><br />
HTML;

    }

    function get_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $model = new VendorsModel($dbc);
        $vselect = '';
        $exclude = array(-1,1,2);
        foreach ($model->find() as $obj) {
            if (!in_array($obj->vendorID(),$exclude)) {
                $vid = $obj->vendorID();
                $vname = $obj->vendorName();
                $vselect .= '<option value="'.$vid.'">'.$vname.'</option>';
            }
        }

        if ($saved = FormLib::get('saved')) {
            $alert = '';
            if ($saved == 'false') {
                $alert = '<div class="alert alert-danger">Save Unsuccessful</div>';
            } else {
                $alert = '<div class="alert alert-success">Save Successful</div>';
            }
        }

        return <<<HTML
<div align="center">
    <div class="panel panel-default " style="max-width: 500px;">
    <div class="panel-heading">Product Review</div>
    <div class="panel-body" >
        {$alert}
        <div class="row">
            <div class="col-md-6">
                <form class="form" method="get">
                    <div class="form-group input-group-sm">
                        <label>Review a single UPC</label>
                        <input type="text" class="form-control" name="upc" value="" autofocus>
                    </div>
                    <div class="form-group">
                        <button class="btn btn-default" type="submit">Update Reviewed Date</button>
                    </div>
                 </form>
                 <div class="divider"></div>
                <form class="form" method="get">
                    <div class="form-group">
                        <label>Review Products by Vendor</label>
                        <select class="form-control" name="vendor">
                            <option value="1">Select a Vendor</option>
                            {$vselect}
                        </select>
                    </div>
                    <div class="form-group">
                        <button class="btn btn-default" type="submit">View Items by Vendor</button>
                    </div>
                    <br/>
                    <ul>
                        <li><a href="ProdReviewPage.php?schedule=1">Vendor Review Schedule</a></li>
                        <li><a href="ProdReviewPage.php?batchLog=1">Review Batch Log</a></li>
                    </ul>
                </form>
                <div class="divider hidden-md hidden-lg"></div>
            </div>
        <form class="form" method="get">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Review a list of UPCs</label>
                    <textarea class="form-control" rows="10" rows="25"
                        name="list"></textarea>
                </div>
                <div class="form-group">
                    <button class="btn btn-default" type="submit">Update List as Reviewed</button>
                </div>
            </div>
        </form>
    </div>
</div></div></div>
HTML;

    }

   public function javascript_content()
   {
       ob_start();
       ?>
$(document).ready( function() {
    $('#checkAll').click( function () {
       checkAll();
    });
    editable();
    backBtn();
});

function printAll()
{
    var signUrl = "http://key/git/IS4C/fannie/admin/labels/SignFromSearch.php";
    var bids = [];
    $c = confirm("Print All Batches?");
    if ($c == true) {
        $('.bid').each(function(){
            var bid = $(this).closest('tr').find('.bid').text();
            bids.push(bid);
        });
        var data = '?';
        bids.forEach(function(element) {
            data = data.concat("batch[]="+element+"&");
        });
        data = data.slice(0,-1);
            //need to replace url with FannieAPI
        window.open(signUrl+data, '_blank');

        var path = window.location.pathname;
        window.location.href = path + "?batchLog=1&printAll=1";
    }
}

function checkAll()
{
    if ( $('#checkAll').prop("checked", true) ) {
        $c = confirm('Mark all as Reviewed?');
        if ($c == true) {
            $('.chk').each( function() {
                this.checked = true;
            });
        }
    } else {
        $('.chk').each( function() {
            this.checked = false;
        });
    }
}

function backBtn()
{
    $('.backBtn').click(function(){
        var path = window.location.pathname;
        window.location.href = path;
    });
}

function editable()
{
    $('.editable').change(function() {
        var path = window.location.pathname;
        var bid = $(this).closest('tr').find('.bid').text();
        var comment = $(this).closest('tr').find('.editable').val();
        $.ajax({
            type: 'post',
            url: 'ProdReviewRequest.php',
            data: 'bid='+bid+"&comment="+comment,
            success: function(resp) {
                $('#resp').html(resp);
                fadeAlerts();
            }
        })
    });
}

function forceBatch(bid)
{
    var conf = confirm("Mark batch "+bid+" as forced?");
    if (conf) {
        var path = window.location.pathname;
        window.location.href=path+"?bid="+bid+"&batchLog=1&force=1";
    }
}
function printBatch(bid)
{
    var conf = confirm("Print batch "+bid+"?");
    if (conf) {
        var path = window.location.pathname;
        window.location.href=path+"?bid="+bid+"&batchLog=1&print=1";
    }
}
function addBatch(bid)
{
    var path = window.location.pathname;
    window.location.href=path+"?bid="+bid+"&batchLog=1&add=1";
}

function fadeAlerts()
{
    $('.alert').each(function() {
        $(this).fadeOut(1500);
    });
}
       <?php
       return ob_get_clean();

    }

    public function css_content()
    {
        return <<<HTML
textarea {
    height: 25px;
}
#alert {
    position: relative;
}
#resp {
    position: absolute;
    top: -45px;
    right: 10px;
}
.batchLogInput {
    border: 1px transparent;
    background-color: transparent;
    font-size: 10px;
}
.btn-wide {
    width: 100%;
    //margin-top: 4px;
}
.batchTable {
    max-width: 750px;
}
span.panel-header {
    font-weight: bold;
    //color: darkblue;
}
div.main {
    max-width: 600px;
    text-align: left;
    padding:15px;
}
#checkAll {
    border: 5px solid blue;
    background-color: red;
    padding: 55px;
}
.divider {
    background-color: lightgrey;
    height: 2px;
    margin: 10px;
    border-radius: 2px;
}
HTML;
    }

    public function helpContent()
    {
        return <<<HTML
        <p>Mark a product as reviewed once the following
            information has been verified as current:
            <ul>
                <li>Cost</li>
                <li>Sku</li>
                <li>Default Vendor</li>
            </ul>
        </p>
HTML;
    }

}

FannieDispatch::conditionalExec();

