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
    include(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

class ProdReviewPage extends FannieRESTfulPage
{
    protected $must_authenticate = True;
    protected $auth_classes = array('admin');

    protected $header = 'Vendor Product Info Review';
    protected $title = 'Vendor Product Info Review';

    public $description = '[Vendor Product Info Review] keep a record of the
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
        $this->__routes[] = 'get<batchLog><deleteRow><id>';
        $this->__routes[] = 'get<schedule>';
        $this->__routes[] = 'get<star>';
        $this->__routes[] = 'get<schedule><setup>';
        $this->__routes[] = 'get<schedule><setup><save>';
        return parent::preprocess();
    }

    public function get_schedule_setup_save_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $vid = FormLib::get('vid');
        $rate = FormLib::get('rate');

        if ($rate) {
            $args = array($rate,$vid);
            $prep = $dbc->prepare("UPDATE vendorReviewSchedule SET rate = ?, exclude = 0 WHERE vid = ?");
        } else {
            $args = array($vid);
            $prep = $dbc->prepare("UPDATE vendorReviewSchedule SET rate = 0, exclude = 1 WHERE vid = ?");
        }
        $dbc->execute($prep,$args);

        header('location: ProdReviewPage.php?schedule=1&setup=1');
    }

    public function backBtn()
    {
        return <<<HTML
<button class="btn btn-xs btn-primary backBtn"><span class="glyphicon glyphicon-chevron-left"></span></button>
HTML;
    }

    public function get_schedule_setup_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $ret = $this->backBtn()."<br/><br/>";
        $tableA = "<table class=\"table table-striped table-bordered table-condensed\">
            <thead><th>VID</th><th>Vendor</th><th>Rate</th><th>Excluded</th></thead><tbody>";
        $prep = $dbc->prepare("SELECT s.vid,s.rate,v.vendorName,s.exclude
            FROM vendorReviewSchedule AS s
            LEFT JOIN vendors AS v ON s.vid=v.vendorID WHERE v.inactive = 0
            ORDER BY exclude DESC, rate DESC, vendorID ASC,vendorName;");
        $res = $dbc->execute($prep);
        $tableA .= "<form class=\"form-inline\">";
        while ($row = $dbc->fetchRow($res)) {
            $vid = $row['vid'];
            $rate = $row['rate'];
            $tableA .= "<tr><td>{$vid}</td>";
            $tableA .= "<td>{$row['vendorName']}</td>";
            $tableA .= "<td>
                <a href=\"#\" onClick=\"setRate({$vid},{$rate});\" id=\"{$vid}vid\">{$rate}</a>
                </td>";
            $tableA .= "<td>{$row['exclude']}</td></tr>";
        }
        $tableA .= "</tbody></table></form>";

        $modRate = "
            <div class='modal' id='rateModal'>
                <div class='modal-dialog vertical-alignment-helper'>
                    <div class='vertical-align-center'>
                        <div class='modal-content input-content'>
                                <div style='padding: 25px; padding-bottom: 5px'>
                                    <b>Rate</b> is the number of months in a year a vendor
                                        should be reviewed. Set a vendor's rate to 0 to exclude it from
                                        the Vendor Review Schedule.<br/><br/>
                                </div>
                            <div align='center'>
                                <form class='form-inline'>
                                    <input type='hidden' name='schedule' value='1'>
                                    <input type='hidden' name='setup' value='1'>
                                    <input type='hidden' name='save' value='1'>
                                    <input type='hidden' name='vid' id='vid' value='NULL'>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Rate (review every x month)</span>
                                        <input type='number' class='form-control' name='rate' id='rate'
                                            min='0' max='12' value='NULL'>
                                    </div>
                                    <div class='input-group'>
                                        <button type='submit' class='btn btn-default'><span class='glyphicon glyphicon-floppy-disk'></span></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        ";

        $ret .= $tableA;
        return <<<HTML
{$modRate}
{$ret}
HTML;
    }

    public function get_star_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $vid = FormLib::get('star');
        $action = FormLib::get('action');
        $action = $action ? "1" : "0";
        $args = array($action,$vid);
        $prep = $dbc->prepare("UPDATE vendorReviewSchedule SET priority=? WHERE vid=?");
        $dbc->execute($prep,$args);

        return header('location: ProdReviewPage.php?schedule=1');
    }

    public function get_schedule_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $prep = $dbc->prepare("
            SELECT p.upc, p.default_vendor_id, r.reviewed
            FROM products AS p
                LEFT JOIN prodReview AS r ON p.upc=r.upc
                INNER JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE p.inUse = 1
                AND m.superID in (1,4,5,8,9,13,17)
            GROUP BY p.upc
        ");
        $res = $dbc->execute($prep);
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[$row['default_vendor_id']][$row['upc']] = $row['reviewed'];
        }

        $vprep = $dbc->prepare("
            SELECT v.vendorID, v.vendorName, s.rate, s.priority
            FROM vendors AS v
                LEFT JOIN vendorItems AS p ON v.vendorID=p.vendorID
                LEFT JOIN prodReview AS r ON r.upc=p.upc
                LEFT JOIN vendorReviewSchedule AS s ON v.vendorID=s.vid
            WHERE inactive = 0
            GROUP BY p.vendorID
        ");
        $vres = $dbc->execute($vprep);
        while ($row = $dbc->fetchRow($vres)) {
            $rate = $row['rate'] == 0 ? 0 : 12 / $row['rate'];
            $y = date('Y');
            $m = date('m');
            $m -= $rate;
            if ($m <= 0) {
                $m = 12 + $m;
                $y--;
            }
            $d = date('d');
            $data[$row['vendorID']]['rate'] = $y."-".$m."-".$d;
            $data[$row['vendorID']]['name'] = $row['vendorName'];
            $data[$row['vendorID']]['vid'] = $row['vendorID'];
            $data[$row['vendorID']]['star'] = $row['priority'];
        }

        $vendorinfo = '';
        foreach ($data as $vid => $row) {
            if (empty($data[$vid]['good'])) {
                $data[$vid]['good'] = 0;
            }
            if (empty($data[$vid]['total'])) {
                $data[$vid]['total'] = 0;
            }
        }
        foreach ($data as $vid => $row) {
            foreach ($row as $upc => $review) {
                if (is_numeric($upc)) {
                    $data[$vid]['total']++;
                    if (strtotime($review) ) {
                        $reviewUTS = strtotime($review);
                        $rateUTS = !isset($data[$vid]['rate']) ? 0 : strtotime($data[$vid]['rate']);
                        if ($reviewUTS > $rateUTS) {
                            $data[$vid]['good']++;
                        }
                    }
                }
            }
        }
        foreach ($data as $vid => $row) {
            $data[$vid]['priority'] = $row['good'] / $row['total'];
            $data[$vid]['score'] = $row['good'] / $row['total'];
            if ($data[$vid]['priority'] == 0 && $data[$vid]['star'] == 0) {
                $data[$vid]['priority'] = 1.01;
            } elseif($data[$vid]['star'] == 1) {
                $data[$vid]['priority'] = 0;
            }
        }
        usort($data, function($a, $b) {
            if ($a["priority"] == $b["priority"]) return 0;
            return $a["priority"] > $b["priority"] ? 1 : -1;
        });
        foreach ($data as $vid => $v) {
            if ($data[$vid]["star"] == 1) {
                array_unshift($data,$data[$vid]['priority']);
            }
        }

        $table = "<table class='table table-condensed table-bordered table-striped small'>
            <thead><th>VID</th><th>Vendor</th><th>Priority</th><th>ProdCount</th><th>% Reviewed</th></thead><tbody>";
        $vExclude = array(NULL,-1,1,2,242,70);
        foreach ($data as $k => $v) {
            if (!in_array($v["vid"],$vExclude)) {
                if ($v["priority"] == 1.01) {
                    $v["score"] = 0;
                    $r = 155;
                    $g = 155;
                    $b = 155;
                } else {
                    $n = $v["priority"];
                    $r = (255 * $n);
                    $g = (255 - (100 * $n));
                    $b = 0;
                }
                $star = $v["star"] ?
                    "<span class='glyphicon glyphicon-star'></span>" :
                    "<span class='glyphicon glyphicon-star-empty'></span>";
                if ($g > 190) {
                    $color = "white";
                } else {
                    $color = "black";
                }
                $grade = "<span style='font-size: 10px; height: 15px; width: 15px;
                    color: {$color}; float: left; text-align: center;
                    background-color: rgba({$g},{$r},{$b},1);); border-radius: 100%;'>
                    </span>
                    {$star}
                    ";
                $score = sprintf("%d%%",$v['score']*100);
                $vid = $v['vid'];
                $table .= "<tr><td class='vid'>{$vid}</td>";
                $table .= "<td>{$v['name']}</td>";
                $table .= "<td>{$grade}</td>";
                $table .= "<td>{$v['total']}</td>";
                $table .= "<td>{$score}</td>";
            }
        }
        $table .= "</tbody></table>";


        return <<<HTML
{$this->backBtn()} &nbsp;|&nbsp; <a href="ProdReviewPage.php?schedule=1&setup=1">Vendor Setup</a>
<br/><br/>
{$table}
HTML;
    }

    public function get_batchLog_deleteRow_id_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $bid = FormLib::get('id');
        $args = array($bid);
        $prep = $dbc->prepare("DELETE FROM batchReviewLog WHERE bid= ?");
        $dbc->execute($prep,$args);

        return header('location: '.$ERVER['PHP_SELF'].'?batchLog=1');
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

        $bData = '';
        if ($bid) {
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
                <label class='text-success'>New Batch</label>
                <div class='batchTable'>
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
            <th>Comments</th><th></th><th>{$pAllBtn}</th><tr></thead><tbody>";
        $tableB = "<table class='table table-condensed table-striped small alert-info'><thead><tr>
            <th>BatchID</th><th>Batch Name</th><th>VID</th><th>Vendor</th><th>Forced On</th>
            <th>user</th><tr></thead><tbody>";
        $args = array();
        $prep = $dbc->prepare("
            SELECT l.vid, l.printed, l.user, l.created, l.forced,
                b.batchName, l.bid, v.vendorName, l.comments, u.name
            FROM batchReviewLog AS l
                LEFT JOIN batches AS b ON l.bid=b.batchID
                LEFT JOIN vendors AS v ON l.vid=v.vendorID
                LEFT JOIN Users AS u ON l.user=u.uid 
            GROUP BY l.bid
            ORDER BY l.bid DESC
        ");
        $res = $dbc->execute($prep,$args);
        while ($row = $dbc->fetchRow($res)) {
            $curBid = $row['bid'];
            $curBidLn = "../batches/newbatch/EditBatchPage.php?id=".$curBid;
            if ($row['forced'] == '0000-00-00 00:00:00') {
                $tableA .= "<tr>";
                $tableA .= "<td class='biduf'><a href=\"{$curBidLn}\" target=\"_blank\">{$curBid}</a></td>";
                $batchName = $row['batchName'];
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
                $tableA .= "<td><span class='glyphicon glyphicon-trash' onClick='deleteRow($curBid)'></span></td>";
                $tableA .= $action;
                $tableA .= "</tr>";
            } else {
                $tableB .= "<tr>";
                $tableB .= "<td class='bid'><a href=\"{$curBidLn}\" target=\"_blank\">{$curBid}</a></td>";
                $batchName = $row['batchName'];
                $tableB .= "<td>{$batchName}</td>";
                $tableB .= "<td>{$row['vid']}</td>";
                $tableB .= "<td>{$row['vendorName']}</td>";
                $tableB .= "<td>{$row['forced']}</td>";
                $tableB .= "<td>{$row['user']} | {$row['name']}</td>";
                $tableB .= "</tr>";
            }
        }
        $tableA .= '</tbody></table>';
        $tableB .= '</tbody></table>';

        return <<<HTML
<div align="center">
    <div class="panel panel-default">
        <div class="panel-heading">
            <span class="panel-header">Batch Review Log</span>
            <div style="position:relative;"><div style="position: absolute; top:-20px; left: 0px">
                {$this->backBtn()}
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
        <div class="batchTable">
            {$tableA}
        </div>
        <label class="text-info">Forced Batches</label>
        <div class="batchTable">
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
        $vid = $this->vendor;

        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $p = new ProductsModel($dbc);
        $p->default_vendor_id($vid);
        $p->inUse(1);

        $masterDepts = array(1,4,5,8,9,13,17);
        $curDepts = array();
        $m = new MasterSuperDeptsModel($dbc);
        foreach ($masterDepts as $mDept) {
            $m->reset();
            $m->superID($mDept);
            foreach ($m->find() as $obj) {
                $curDepts[] = $obj->dept_ID();
            }
        }

        $table = '<table class="table table-condensed small" id="reviewtable">';
        $table .= '<thead><th>UPC</th><th>Brand</th><th>Description</th>
            <th>Reviewed On</th></thead><tbody><td></td><td></td><td></td><td></td><td>
            <input type="checkbox" id="checkAll" style="border: 1px solid red;"></td>';

        $pr = new ProdReviewModel($dbc);
        $in = array();
        foreach ($p->find() as $obj) {
            if (!in_array($obj->upc(),$in) && in_array($obj->department(),$curDepts)) {
                $table .= '<tr>';
                $table .= '<td>'.$obj->upc().'</td>';
                $table .= '<td>'.$obj->brand().'</td>';
                $table .= '<td>'.$obj->description().'</td>';
                $pr->reset();
                $pr->upc($obj->upc());
                if ($pr->load()) {
                    $table .= '<td>'.$pr->reviewed().'</td>';
                } else {
                    $table .= '<td><i class="text-danger">no review date</i></td>';
                }
                $table .= '<td><input type="checkbox"class="chk" name="checked[]" value="'.$obj->upc().'"></td>';
                $table .= '</tr>';
            }
            $in[] = $obj->upc();
        }
        $table .= '</tbody></table>';

        return <<<HTML
{$this->backBtn()}<br/><br/>
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
        $upc = BarcodeLib::padUPC($this->upc);
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
{$this->backBtn()}<br/><br/>
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

        $upcs = array();
        $plus = array();
        $chunks = explode("\r\n", $this->list);
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
{$this->backBtn()}<br/><br/>
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
        foreach ($model->find('vendorName') as $obj) {
            if (!in_array($obj->vendorID(),$exclude)) {
                $vid = $obj->vendorID();
                $vname = $obj->vendorName();
                $vselect .= '<option value="'.$vid.'">'.$vname.'</option>';
            }
        }

        $alert = '';
        if ($saved = FormLib::get('saved')) {
            if ($saved == 'false') {
                $alert = '<div class="alert alert-danger">Save Unsuccessful</div>';
            } else {
                $alert = '<div class="alert alert-success">Save Successful</div>';
            }
        }

        return <<<HTML
<div align="center">
    <div class="panel panel-default " style="max-width: 800px;">
    <div class="panel-heading">Product Review</div>
    <div class="panel-body" style="text-align: left;">
        {$alert}
        <div class="row">
            <div class="col-md-6">
                <form class="form" method="get">
                    <div class="form-group input-group-sm">
                        <label>Review a single UPC</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="upc" value="" autofocus>
                            <span class="input-group-addon">
                                <button class="input-addon-btn" type="submit">
                                    <span class="glyphicon glyphicon-chevron-right"></span>
                                </button>
                            </span>
                        </div>
                    </div>
                 </form>
                 <div class="divider"></div>
                <form class="form" method="get">
                    <div class="form-group">
                        <label>Review Products by Vendor</label>
                        <div class="input-group">
                            <select class="form-control" name="vendor">
                                <option value="1">Select a Vendor</option>
                                {$vselect}
                            </select>
                            <div class="input-group-addon">
                                <button class="input-addon-btn" type="submit">
                                    <span class="glyphicon glyphicon-chevron-right"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="divider"></div>
                    <label>Other Pages</label>
                    <ul>
                        <li><a href="ProdLocationEditor.php">Product Location Editor</a></li>
                        <li><a href="ProdReviewPage.php?batchLog=1">Review Batch Log</a></li>
                        <li><a href="ProdReviewPage.php?schedule=1">Vendor Review Schedule</a></li>
                        <li><a href="ProdReviewPage.php?schedule=1&setup=1">Vendor Schedule Setup</a></li>
                    </ul>
                </form>
                <div class="divider hidden-md hidden-lg"></div>
            </div>
        <form class="form" method="get">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Review a list of UPCs</label>
                    <div class="input-group">
                    <textarea class="form-control" rows="10" rows="25" name="list"></textarea>
                    <span class="input-group-addon">
                    <button class="btn btn-default" type="submit"><span class="glyphicon glyphicon-chevron-right"></span></button>
                    </span>
                    </div>
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
    clickStar();
});

function deleteRow(id)
{
    var url = "ProdReviewPage.php?batchLog=1&deleteRow=1&id="+id;
    var c = confirm('Remove Batch From Table?');
    if (c == true) {
        window.open(url, '_self'); 
    }
}

function setRate(vid,rate)
{
    //alert(vid);
    //alert(rate);
    $('#vid').val(vid);
    $('#rate').val(rate);
    $('#rateModal').modal('show');

}

function clickStar()
{
    $('.glyphicon-star').click(function() {
        var vid = $(this).closest('tr').find('.vid').text();
        var url = "ProdReviewPage.php?star="+vid+"&action=0";
        window.open(url, '_self');
    });
    $('.glyphicon-star-empty').click(function() {
        var vid = $(this).closest('tr').find('.vid').text();
        var url = "ProdReviewPage.php?star="+vid+"&action=1";
        window.open(url, '_self');
    });
}

function printAll()
{
    var signUrl = "../admin/labels/SignFromSearch.php";
    var bids = [];
    $c = confirm("Print All Batches?");
    if ($c == true) {
        var data = '?';
        $('td').each(function() {

            if ($(this).hasClass('biduf')) {
                var bid = $(this).text();
                bids.push(bid);
            }
        });
        $.each(bids, function(k,v) {
            data = data.concat("batch[]="+v+"&");
        });
        data = data.slice(0,-1);
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
    var conf = confirm("Force Batch "+bid+"?");
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
textarea { resize: vertical }
.input-addon-btn {
    width: 100%;
    height: 100%;
    border: none;
}
.vertical-alignment-helper {
    display:table;
    height: 100%;
    width: 100%;
    pointer-events:none; /* This makes sure that we can still click outside of the modal to close it */
}
.vertical-align-center {
    /* To center vertically */
    display: table-cell;
    vertical-align: middle;
    pointer-events:none;
}
.input-content {
    /* Bootstrap sets the size of the modal in the modal-dialog class, we need to inherit it */
    width:inherit;
    height:inherit;
    /* To center horizontally */
    margin: 0 auto;
    pointer-events: all;
    width: 600px;
    height: 150px;
}
.glyphicon-star {
    color: orange;
    margin-left: 5px;
}
.glyphicon-star-empty {
    color: orange;
    opacity: 0.5;
    margin-left: 5px;
}
.glyphicon-star, .glyphicon-star-empty {
    cursor: pointer;
}
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
    width: 100%;
}
.btn-wide {
    width: 100%;
    margin-top: 3px;
}
.batchTable {
    border: 2px transparent;
    border-radius: 0;
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
    border-radius: 2px;
    margin-bottom: 10px;
}
h4 {
    font-weight: bold   font-weight: bold;;
}
HTML;
    }

    public function helpContent()
    {
        return <<<HTML
        <h4>Product Review</h4>
        <p>Mark a product as reviewed once the following
            information has been verified as current:
            <ul>
                <li>Cost</li>
                <li>Sku</li>
                <li>Default Vendor</li>
            </ul>
        </p>
        <h4>Vendor Review Schedule</h4>
        <p>Lists active vendors (excluding vendors
            updated every month) in order of the number
            of products that have been reviewed within
            the given timeframe.
        </p>
            <ul>
                <li>Click the <b>star</b> icon to increase the
                priority of a vendor. Starred vendors will
                appear at the top of the list</li>
            </ul>
        <h4>Batch Review Log</h4>
        <p>Is a record of price change batches. Batches are
            uploaded to this page manually by auditor.
            <ul>
                <li><b>Print</b> mark one batch as printed / open Signs From Search
                    to print tags/signs.</li>
                <li><b>Print All</b> mark all batches currently in the "Un-Forced Batches"
                    table as printed / open Signs From Search to print tags/signs.</li>
                <li><b>Force</b> force a batch in POS and mark it as forced.</li>
            </ul>
        </p>
HTML;
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertInternalType('string', $this->css_content());
        $phpunit->assertInternalType('string', $this->javascript_content());
        $phpunit->assertInternalType('string', $this->get_view());
        $phpunit->list = "4011\r\n111";
        $phpunit->assertInternalType('string', $this->get_list_view());
        $phpunit->upc = '4011';
        $phpunit->assertInternalType('string', $this->get_upc_view());
        $phpunit->vendor = 1;
        $phpunit->assertInternalType('string', $this->get_vendor_view());
        $phpunit->assertInternalType('string', $this->get_schedule_setup_view());
    }
}

FannieDispatch::conditionalExec();

