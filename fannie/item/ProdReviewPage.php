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

use COREPOS\Fannie\API\lib\Operators as Op;

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
        $this->__routes[] = 'post<batchLog><force>';
        $this->__routes[] = 'get<batchLog><print>';
        $this->__routes[] = 'get<batchLog><printAll>';
        $this->__routes[] = 'get<batchLog><deleteRow><id>';
        $this->__routes[] = 'get<schedule>';
        $this->__routes[] = 'get<schedule><setup>';
        $this->__routes[] = 'get<schedule><setup><save>';
        $this->__routes[] = 'get<stagedReview>';
        return parent::preprocess();
    }

    public function get_stagedReview_view()
    {

        return <<<HTML
<form action=VendorPricingBatchPage.php method="get" target="_blank">
<input name="id" class="hidden" value="1">
<label>Forced date begin</label>
<input type="date" class="form-control">
<label>Forced date end</label>
<input type="date" class="form-control">
<br />
<p>
<button type=submit class="btn btn-default">Continue</button>
</p>
</form>
HTML;
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
<form name="backbutton">
<button class="btn btn-xs btn-primary backBtn" type="submit">
    <span class="fas fa-chevron-left"></span></button>
</form>
HTML;
    }

    public function get_schedule_setup_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $ret = $this->backBtn()."<br/><br/>";
        $tableA = "<table class=\"table table-bordered table-condensed\">
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
            $class = ($row['exclude'])? 'alert-danger' : '';
            $tableA .= "<tr class='$class'><td>{$vid}</td>";
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
                                        <button type='submit' class='btn btn-default'>"
                                        . COREPOS\Fannie\API\lib\FannieUI::saveIcon() .
                                        "</span></button>
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

    public function get_schedule_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $prep = $dbc->prepare("
            SELECT p.upc, p.default_vendor_id, r.reviewed
            FROM products AS p
                LEFT JOIN prodReview AS r ON p.upc=r.upc
                INNER JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                LEFT JOIN vendorReviewSchedule AS v ON p.default_vendor_id=v.vid
            WHERE p.inUse = 1
                AND m.superID in (1,4,5,8,9,13,17,3,18)
                AND v.exclude <> 1
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
            $data[$vid]['priority'] = Op::div($row['good'], $row['total']);
            $data[$vid]['score'] = Op::div($row['good'], $row['total']);
            if ($data[$vid]['priority'] == 0) {
                $data[$vid]['priority'] = 1.01;
            }
        }
        usort($data, function($a, $b) {
            if ($a["priority"] == $b["priority"]) return 0;
            return $a["priority"] > $b["priority"] ? 1 : -1;
        });

        $table = "<table class='table table-condensed table-bordered table-striped small tablesorter tablesorter-bootstrap' id='reviewtable'>
            <thead><th>VID</th><th>Vendor</th><th>Priority</th><th>ProdCount</th><th>% Reviewed</th></thead><tbody>";
        $vExclude = array(NULL,-1,1,2,242,70);
        foreach ($data as $k => $v) {
            if (isset($v['vid']) && !in_array($v['vid'],$vExclude)) {
                if ($v["priority"] == 1.01) {
                    $v["score"] = 0;
                    $n = 0;
                    $re = 0;
                } else {
                    $n = $v["priority"];
                    $re = 1 - $n;
                    $n *= 100;
                    $re *= 100;
                    $n = round($n);
                    $re = round($re);
                    $r = (255 * $n);
                    $g = (255 - (100 * $n));
                    $b = 0;
                }
                if (!isset($color)) {
                    $color = '';
                }
                $grade = "<span style='font-size: 10px; height: 15px; width: 90%;
                    color: {$color}; float: left; text-align: center;
                    background: linear-gradient(to right, lightgreen $n%, 1%, tomato $re%);'>
                    </span>
                    ";
                $score = sprintf("%d%%",$v['score']*100);
                $vid = $v['vid'];
                if ($v['total'] > 0) {
                    $table .= "<tr><td class='vid'>{$vid}</td>";
                    $table .= "<td>{$v['name']}</td>";
                    $table .= "<td>{$grade}</td>";
                    $table .= "<td>{$v['total']}</td>";
                    $table .= "<td>{$score}</td>";
                }
            }
        }
        $table .= "</tbody></table>";

        $this->addScript('../src/javascript/tablesorter-2.22.1/js/jquery.tablesorter.js');
        $this->addScript('../src/javascript/tablesorter-2.22.1/js/jquery.tablesorter.widgets.js');
        $this->addOnloadCommand("$('#reviewtable').tablesorter({theme:'bootstrap', headerTemplate: '{content} {icon}', widgets: ['uitheme','zebra']});");

        return <<<HTML
{$this->backBtn()}<br/>
<a href="ProdReviewPage.php?schedule=1&setup=1">Vendor Setup</a>
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

        return header('location: '.$_SERVER['PHP_SELF'].'?batchLog=1');
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
        $user = FannieAuth::getUID($this->current_user);
        $model = new BatchReviewLogModel($dbc);
        $model->bid($bid);
        $model->vid($vid);
        $model->printed(0);
        $model->user($user);
        $model->created(date('Y-m-d H:i:s'));
        $model->forced(0);
        $model->save();

        return header('location: '.$_SERVER['PHP_SELF'].'?batchLog=1');
    }

    public function post_batchLog_force_handler()
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
        $json = array('error'=>false, 'success'=>false);
        if ($er = $dbc->error()) {
            $json['error'] = $er;
        } else {
            $json['success'] = true;
        }
        echo json_encode($json);

        return false;
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

    private function getBatchItemDiscrep($dbc)
    {
        $found = array();
        $tmp = array();
        $prep = $dbc->prepare("SELECT * from batchReviewLog AS l
            INNER JOIN batchList AS b ON b.batchID=l.bid WHERE forced = 0;");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            $upc = $row['upc'];
            $batchID = $row['batchID'];
            $tmp[$upc][] = $batchID;
        }
        foreach ($tmp as $upc => $array) {
            if (count($array) > 1) {
                foreach ($array as $batchID) {
                    $found[$batchID][] = $upc;
                }
            }
        }

        return $found;
    }

    private function getBatchScaleItemsOnSale($dbc)
    {
        $found = array();
        $prep = $dbc->prepare("
            SELECT p.upc, l.bid AS batchID
            FROM batchReviewLog AS l
                INNER JOIN batchList AS b ON b.batchID=l.bid
                INNER JOIN products AS p ON p.upc=b.upc
            WHERE l.forced = 0
                    AND p.upc LIKE '002%'
                    AND p.special_price <> 0
                ;");
            $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            $upc = $row['upc'];
            $batchID = $row['batchID'];
            $found[$batchID][] = $upc;
        }

        return $found;
    }

    public function get_batchLog_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        /*
            tableA = unforced batches | tableB = forced.
        */
        $bids = "0";
        $pAllBtn = "<button class='btn btn-default btn-xs'
            onClick='printAll(); return false;'><span class='fas fa-print'></span></button>";
        $tableA = "<div class='table-responsive'><table class='table table-condensed table-striped small'><thead><tr class=\"thead\">
            <th>{$pAllBtn}</th>
            <th>BatchID</th><th>Batch Name</th><th>Super Depts +</th><th>VID</th><th>Vendor</th><th>Uploaded</th>
            <th>Comments</th><th></th><th></th><tr></thead><tbody>";
        $tableB = "<div class='table-responsive'><table class='table table-condensed table-striped small' id='forcedBatchesTable'><thead><tr class=\"thead\">
            <th>BatchID</th><th>Batch Name</th><th>Super Depts +</th><th>VID</th><th>Vendor</th><th>Forced On</th>
            <th>user</th><tr></thead><tbody>";
        $args = array();
        $prep = $dbc->prepare("
            SELECT l.vid, l.printed, l.user, l.created, l.forced,
                b.batchName, l.bid, v.vendorName, l.comments, u.name, m.super_name, s.plu
            FROM batchReviewLog AS l
                LEFT JOIN batches AS b ON l.bid=b.batchID
                LEFT JOIN batchList AS bl ON b.batchID=bl.batchID
                LEFT JOIN products AS p ON p.upc=bl.upc
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                LEFT JOIN vendors AS v ON l.vid=v.vendorID
                LEFT JOIN Users AS u ON l.user=u.uid
                LEFT JOIN scaleItems AS s ON bl.upc=s.plu
            WHERE forced > NOW() - INTERVAL 3 MONTH 
                OR forced = 0
            GROUP BY l.bid, m.super_name
            ORDER BY l.forced DESC, l.bid DESC
        ");
        $res = $dbc->execute($prep,$args);
        $batches = array();
        while ($row = $dbc->fetchRow($res)) {
            if (!isset($batches[$row['bid']])) {
                $batches[$row['bid']] = array();
            }
            if (!in_array($row['super_name'], $batches[$row['bid']])) {
                $batches[$row['bid']][] = $row['super_name'];
            }
        };

        $discr = $this->getBatchItemDiscrep($dbc);
        $scaleOnSale = $this->getBatchScaleItemsOnSale($dbc);
        
        $res = $dbc->execute($prep,$args);
        $curBid = -999;
        while ($row = $dbc->fetchRow($res)) {
            $scale = '';
            $superDepts = '';
            if ($row['bid'] == $curBid)
                continue;
            $curBid = $row['bid'];
            foreach ($batches[$curBid] as $k => $superName) {
                $rgb = '#'.substr(md5($superName), 0, 6);
                $superDepts .= "<span class=\"supertab\" title=\"$superName\" style=\"color: $rgb;\">".substr($superName,0,4)."</span>";
            }
            if ($scale == '' && $row['plu'] != NULL)
                $superDepts .= "<span class=\"\" style=\"border: 1px solid black; border-radius: 50%; padding-left: 3px; padding-right: 3px; cursor: default;\" title=\"Scale Items in batch\">S</span>";
            $curDiscr = '';
            if (array_key_exists($curBid, $discr)) {
                $title = '';
                foreach ($discr[$curBid] as $upc) {
                    $title .= <<<HTML
<div>$upc</div>
HTML;
                }
                $curDiscr = ' <a href="#" class="btn btn-xs btn-danger showDiscrFound" title="'.$title.'"><span class="fas fa-exclamation-circle"></span></a>';
            }

            $curDeliWarn = '';
            if (in_array('DELI', $batches[$curBid]) && count($batches[$curBid]) > 1 ) {
                $curDeliWarn = ' <a href="#" class="btn btn-xs btn-info" title="Batch contains both DELI and MERCH dept. items"><span class="fas fa-exclamation-circle"></span></a>';
            }

            $curBidLn = "../batches/newbatch/EditBatchPage.php?id=".$curBid;
            if ($row['forced'] == '0000-00-00 00:00:00') {
                $bids .= ",".$curBid;
                $tableA .= "<tr>";
                $tableA .= "<td><input type='checkbox' id='check$curBid' class='upcCheckBox'></td>";
                $tableA .= "<td class='biduf'><a href=\"{$curBidLn}\" target=\"_blank\">{$curBid}</a>$curDiscr &nbsp; $curDeliWarn</td>";
                $batchName = $row['batchName'];
                $tableA .= "<td>{$batchName}</td>";
                $tableA .= "<td class=\"super-depts\">$superDepts</td>";
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
                    ' rows=2>{$row['comments']}</textarea></td>";
                $action = '';
                $noPunctBatchName = str_replace("'", "", $batchName);
                $action = "<td><button class='btn btn-default ' style='border: 1px solid tomato;'
                    onClick='forceBatch($curBid, \"$noPunctBatchName\"); return false;' id='force$curBid'>Force</button></td>";
                $tableA .= "<td><span class='fas fa-trash' onClick='deleteRow($curBid)' title='Remove from staging table'></span></td>";
                $tableA .= $action;
                $tableA .= "</tr>";
            } else {
                $tableB .= "<tr>";
                $tableB .= "<td class='bid'><a href=\"{$curBidLn}\" target=\"_blank\">{$curBid}</a></td>";
                $batchName = $row['batchName'];
                $tableB .= "<td>{$batchName}</td>";
                $tableB .= "<td>$superDepts</td>";
                $tableB .= "<td>{$row['vid']}</td>";
                $tableB .= "<td>{$row['vendorName']}</td>";
                $tableB .= "<td>{$row['forced']}</td>";
                $tableB .= "<td>{$row['user']} | {$row['name']}</td>";
                $tableB .= "</tr>";
            }
        }
        $tableA .= '</tbody></table></div>';
        $tableB .= '</tbody></table></div>';

        return <<<HTML
<div id="ajax-processing" style="text-align: center; position: fixed; top: 48vh; left: 40vw; font-weight: bold;
    background: rgba(255, 100, 100, 0.8); border: 3px solid pink; display: none; padding: 25px;
    border-radius: 4px;">BATCH FORCE IN PROGRESS, PLEASE WAIT</div>
<div align="">
        <div class="">
            <div>{$this->backBtn()}</div>
        </div>
        <div class="row">
            <div class="col-lg-4">
                <form method="get" class="form-inline">
                    <div id="alert"><div id="resp"></div></div>
                </form>
            </div>
            <div class="col-lg-4"></div>
            <div class="col-lg-4">
                <label for="hide-deli-check">Hide Deli&nbsp;</label>
                <input type="checkbox" id="hide-deli-check" /> | 
                <label for="show-deli-check">Show Only Deli&nbsp;</label>
                <input type="checkbox" id="show-deli-check" /> |
                <label for="hide-wait-check">Hide Wait&nbsp;</label>
                <input type="checkbox" id="hide-wait-check" />
            </div>
        </div>
        <h4 align="center">Batches Staged for Price Changes</h4>
        <div class="batchTable">
            <div id="discrFound" style="padding: 15px;"></div>
            <div id="saleScaleFound" style="padding: 15px;"></div>
            {$tableA}
        </div>
        <h4 align="center">History of Price Change Batches</h4>
        <div class="batchTable" >
            {$tableB}
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
        $vendorID = FormLib::get('vendorID');
        $pr = new ProdReviewModel($dbc);
        $data = array();
        $error = 0;
        foreach ($upcs as $upc) {
            $pr->reset();
            $pr->upc($upc);
            $pr->vendorID($vendorID);
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
        $vendorID = FormLib::get('vendor');

        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $p = new ProductsModel($dbc);
        $p->default_vendor_id($vid);
        $p->inUse(1);

        $masterDepts = array(1,3,4,5,8,9,13,17,18);
        $curDepts = array();
        $m = new MasterSuperDeptsModel($dbc);
        foreach ($masterDepts as $mDept) {
            $m->reset();
            $m->superID($mDept);
            foreach ($m->find() as $obj) {
                $curDepts[] = $obj->dept_ID();
            }
        }

        $superNames = array();
        $superStrings = array();
        $prep = $dbc->prepare("
            SELECT super_name, dept_ID FROM MasterSuperDepts
        ");
        $r = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($r)) {
            $superNames[$row['dept_ID']] = $row['super_name'];
        }

        foreach ($p->find() as $obj) {
            $dept = $obj->department();
            $name = $superNames[$dept];
            if (!in_array($name, $superStrings)) $superStrings[] = $name;
        }
        $vdepts = '<label>Super Departments</label><ul>';
        foreach ($superStrings as $name) {
            $vdepts .= "<li>$name</li>";
        }
        $vdepts .= '</ul>';

        $table = '<table class="table table-condensed small tablesorter tablesorter-bootstrap" id="reviewtable">';
        $table .= '<thead><th>UPC</th><th>Brand</th><th>Description</th>
            <th>Reviewed On</th></thead><tbody><td></td><td></td><td></td><td></td><td>
            <input type="checkbox" id="checkAll" class="upcCheckBox" style="border: 1px solid red;"></td>';

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
                $pr->vendorID($obj->default_vendor_id());
                if ($pr->load()) {
                    $table .= '<td class="reviewed">'.$pr->reviewed().'</td>';
                } else {
                    $table .= '<td><i class="text-danger">no review date</i></td>';
                }
                $table .= '<td><input type="checkbox"class="chk upcCheckBox" name="checked[]" value="'.$obj->upc().'"></td>';
                $table .= '</tr>';
            }
            $in[] = $obj->upc();
        }
        $table .= '</tbody></table>';
        $this->addScript('../src/javascript/tablesorter-2.22.1/js/jquery.tablesorter.js');
        $this->addScript('../src/javascript/tablesorter-2.22.1/js/jquery.tablesorter.widgets.js');
        $this->addOnloadCommand("$('#reviewtable').tablesorter({theme:'bootstrap', headerTemplate: '{content} {icon}', widgets: ['uitheme','zebra']});");

        $model = new VendorsModel($dbc);
        $vselect = '';
        $exclude = array(-1,1,2);
        $curVendor = FormLib::get('vendor');
        foreach ($model->find('vendorName') as $obj) {
            if (!in_array($obj->vendorID(),$exclude)) {
                $vid = $obj->vendorID();
                $vname = $obj->vendorName();
                $sel = ($curVendor == $vid) ? ' selected' : '' ;
                $vselect .= '<option value="'.$vid.'" '.$sel.'>'.$vname.' - '.$vid.'</option>';
            }
        }
        $this->addOnloadCommand("
            $('#vselect').on('change', function(){
                document.forms['vform'].submit();
            });
        ");

        $vendor = new VendorsModel($dbc);
        $vendor->vendorID($curVendor);
        $vendor->load();
        $shipping = $vendor->shippingMarkup();
        if ($shipping > 0) {
            $shipping = $vendor->shippingMarkup() * 100;
            $shipping = "<label>Shipping Markup</label> $shipping%";
        } else {
            $shipping = '';
        }

        return <<<HTML
{$this->backBtn()}
<form class="form" method="get" name="vform">
    <div class="form-group">
    </div>
    <div class="form-group">
        <label>Review Products by Vendor</label>
        <select class="form-control" name="vendor" id="vselect">
            <option value="1">Select a Vendor</option>
            {$vselect}
        </select>
    </div>
</form>
<div>$shipping</div>
$vdepts
<div class="form-group">
    <span class="btn btn-default" onclick="$('tr').each(function() { $(this).show(); });">Show All</span>
</div>
<div class="form-group">
    <span class="btn btn-default" onclick="let date = new Date(); let month = date.getMonth() + 1; let today = date.getFullYear() + '-' + month.toString().padStart(2, '0') + '-' + date.getDate().toString().padStart(2, '0'); $('tbody tr').each(function() { let text = $(this).find('td:eq(3)').text(); if (text != today) { console.log(today+ ', ' + text); $(this).hide(); } });">Show Only Today</span>
</div>
<form class="form-inline" id="review-form" name="review-form" method="get">
    {$table}
    <input type="hidden" name="vendor" value="1" />
    <input type="hidden" name="vendorID" value="$vendorID" />
    <a href="#" class="btn btn-warning" 
        onclick=" if ($('#review-form input:checkbox:checked').length > 0) { document.forms['review-form'].submit(); } else { return false; }">Set Checked Items As Reviewed</a>
</form><br />
HTML;
    }

    public function draw_table($data,$dbc)
    {
        $table = '<table class="table table-condensed table-striped small">';
        $table .= '<thead><th>UPC</th><th>Brand</th><th>Description</th>
            <th>Last Reviewed</th></thead><tbody>';

        $pP = $dbc->prepare("SELECT default_vendor_id FROM products WHERE upc = ?");
        $rP = $dbc->prepare("SELECT reviewed FROM prodReview WHERE upc = ? AND vendorID = ?"); 

        $p = new ProductsModel($dbc);
        $pr = new ProdReviewModel($dbc);
        $table .= '<tr>';
        foreach ($data as $upc => $arr) {
            foreach ($arr as $k => $v) {
                if ($k == 'upc') {
                    $vendorID = $dbc->getValue($pP, array($v));
                    $reviewed = $dbc->getValue($rP, array($v, $vendorID));
                    $table .= '<td>'.$v.'</td>';
                } elseif ($k == 'brand') {
                    $table .= '<td>'.$v.'</td>';
                } elseif ($k == 'description') {
                    $table .= '<td>'.$v.'</td>';
                    if ($reviewed != NULL) {
                        $table .= '<td>'.$reviewed.'</td>';
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
        $vendor = FormLib::get('vendorToUpdate');
        $pm = new ProductsModel($dbc);
        //$pr = new ProdReviewModel($dbc);
        $data = array();
        $error = 0;

        $defP = $dbc->prepare("INSERT INTO prodReview (upc, user, reviewed, vendorID) values (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE reviewed = ?");

        foreach ($upcs as $upc) {
            //$pr->reset();
            //$pr->upc($upc);
            //$pr->user($user);
            //$pr->reviewed(date('Y-m-d'));
            $now = date('Y-m-d');

            if ($vendor == 'default') {
                $pm->reset();
                $pm->upc($upc);
                $pm->load();
                $vid = $pm->default_vendor_id();
                //$pr->vendorID($vid);
                $defA = array($upc, $user, $now, $vid, $now);
                $defR = $dbc->execute($defP, $defA);
            } else {
                //$pr->vendorID($vendor);
                $defA = array($upc, $user, $now, $vendor, $now);
                $defR = $dbc->execute($defP, $defA);
            }

            //if (!$pr->save()) {
            //    $error = 1;
            //}
            $error = 0;
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

        $model = new VendorsModel($dbc);
        $vselect = '';
        $exclude = array(-1);
        foreach ($model->find('vendorName') as $obj) {
            if (!in_array($obj->vendorID(),$exclude)) {
                $vid = $obj->vendorID();
                $vname = $obj->vendorName();
                $vselect .= "<option value=\"$vid\">$vname - $vid</option>";
            }
        }

        $FANNIE_URL = $this->config->get('URL');
        $this->addScript($FANNIE_URL . 'src/javascript/chosen/chosen.jquery.min.js');
        $this->addCssFile($FANNIE_URL . 'src/javascript/chosen/bootstrap-chosen.css');

        $this->add_onload_command('$(\'.chosen-select:visible\').chosen();');
        $this->add_onload_command('$(\'#store-tabs a\').on(\'shown.bs.tab\', function(){$(\'.chosen-select:visible\').chosen();});');

        return <<<HTML
{$this->backBtn()}<br/><br/>
<form class="form-inline" method="get">
    <div class="form-group">
        <select class="form-control chosen-select" name="vendorToUpdate">
            <option value="default">Default Vendor ID</option> 
            $vselect
        </select>
    </div>
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
                                <button class="input-addon-btn fas fa-chevron-right" type="submit">
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
                                    <span class="fas fa-chevron-right"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="divider hidden-md hidden-lg"></div>
            </div>
            <div class="col-md-6">
        <form class="form" method="get">
                <div class="form-group" style="z-index: -1">
                    <label>Review a list of UPCs</label>
                    <div class="input-group">
                    <textarea class="form-control" rows="5" name="list" style="z-index: 0"></textarea>
                    <span class="input-group-addon">
                    <button class="btn btn-default" type="submit"><span class="fas fa-chevron-right"></span></button>
                    </span>
                    </div>
                </div>
        </form>


            </div>

    </div>
</div>
</div>
    <div class="panel panel-default " style="max-width: 800px;">
        <div class="panel-body" style="text-align: left;">
            <div class="row">
                <div class="col-lg-4">
                    <label>Related Pages</label>
                    <ul>
                        <li><a href="ProdLocationEditor.php">Product <strong>Location</strong> Editor</a></li>
                        <li><a href="ProdReviewPage.php?batchLog=1">Review <strong>Batch Log</strong></a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <ul>
                        <li><a href="MultiMerchEditor.php">Products with <strong>Multiple Locations</strong></a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <ul>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

HTML;

    }

   public function javascript_content()
   {
    return <<<JAVASCRIPT
var lastChecked = null;
var i = 0;
var indexCheckboxes = function(){
    // 1. unset all data-index
    $('.upcCheckBox').each(function(){
        $(this).attr('data-index', null);
    });

    // 2. set data-index if checkbox is visible
    $('.upcCheckBox').each(function(){
        if ($(this).is(":visible")) {
            $(this).attr('data-index', i);
            i++;
        }
    });
};
indexCheckboxes();
$('.upcCheckBox').on("click", function(e){
    if(lastChecked && e.shiftKey) {
        var i = parseInt(lastChecked.attr('data-index'));
        var j = parseInt($(this).attr('data-index'));
        var checked = $(this).is(":checked");

        var low = i;
        var high = j;
        if (i>j){
            var low = j;
            var high = i;
        }

        for(var c = low; c < high; c++) {
            if (c != low && c!= high) {
                var check = checked ? true : false;
                $('input[data-index="'+c+'"').prop("checked", check);
            }
        }
    }
    lastChecked = $(this);
});

$(document).ready( function() {
    $('#checkAll').click( function () {
       checkAll();
    });
    editable();
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
    $('#vid').val(vid);
    $('#rate').val(rate);
    $('#rateModal').modal('show');

}

function printAll()
{
    var signUrl = "../admin/labels/SignFromSearch.php";
    var bids = [];
    var data = '?';
    var i = 0;
    $('input[type=checkbox]').each(function(){
        if ($(this).prop('checked')) {
            id = $(this).attr('id').substr(5);
            bids.push(id);
            console.log('checked: '+id);
        }
    });
    $.each(bids, function(k,v) {
        data = data.concat("batch[]="+v+"&");
    });
    data = data.slice(0,-1);
    window.open(signUrl+data, '_blank');

}

function checkAll()
{
    if ( $('#checkAll').prop("checked", true) ) {
        $('.chk').each( function() {
            this.checked = true;
        });
    } else {
        $('.chk').each( function() {
            this.checked = false;
        });
    }
}

function editable()
{
    $('.editable').change(function() {
        var path = window.location.pathname;
        var bid = $(this).closest('tr').find('a').text();
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

function forceBatch(bid, bname)
{
    buttonid = 'force'+bid;
    html = $('#'+buttonid).closest('tr').html();
    var conf = confirm("Force Batch "+bid+"?");
    if (conf) {
        $.ajax({
            type: 'post',
            data: 'bid='+bid+'&batchLog=1&force=1',
            dataType: 'json',
            beforeSend: function()
            {
                console.log('beforeSend successful');
                $('#ajax-processing').show();
            },
            success: function(json)
            {
                if (json.success == true) {
                    $('#'+buttonid).closest('tr').hide();
                    $('#forcedBatchesTable > tbody')
                        .prepend('<tr><td>'+bid+'</td><td>'+bname+'</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>');
                } else {
                    alert('Error: '+json.error);
                }
            },
            error: function(e)
            {
                alert('Request unsuccessful, see console.log for details.');
                console.log(e);
            },
            complete: function()
            {
                $('#ajax-processing').hide();
            }
        });
    }
}

function printBatch(bid)
{
    var signUrl = "../admin/labels/SignFromSearch.php";
    var data = "?batch[]="+bid;
    var conf = confirm("Print batch "+bid+"?");
    if (conf) {
        var path = window.location.pathname;
        window.open(signUrl+data, '_blank');
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

$('#hide-deli-check').change(function(){
    let c = $(this).is(":checked");
    if (c == true) {
        $('tr').each(function(){
            let depts = $(this).find('td.super-depts').text();
            //if (depts.includes('DELI')) {
            if (depts.indexOf('DELI') != -1) {
                $(this).hide();
            }
        });
    } else {
        $('tr').each(function(){
            let depts = $(this).find('td.super-depts').text();
            console.log(depts);
            if (depts.includes('DELI')) {
                $(this).show();
            }
        });

    }
    indexCheckboxes();
});

$('#hide-wait-check').change(function(){
    let c = $(this).is(":checked");
    if (c == true) {
        $('tr').each(function(){
            let comments = $(this).find('td:eq(7)').text();
            console.log(comments);
            if (comments.indexOf('WAIT') != -1) {
                $(this).hide();
            }
        });
    } else {
        $('tr').each(function(){
            let comments = $(this).find('td:eq(7)').text();
            console.log(comments);
            if (comments.indexOf('WAIT') != -1) {
                $(this).show();
            }
        });

    }
    indexCheckboxes();
});

$('#show-deli-check').change(function(){
    let c = $(this).is(":checked");
    if (c == true) {
        $('tr').each(function(){
            let depts = $(this).find('td.super-depts').text();
            if (!depts.includes('DELI') && !$(this).hasClass("thead")) {
                $(this).hide();
            }
        });
    } else {
        $('tr').each(function(){
            let depts = $(this).find('td.super-depts').text();
            if (!depts.includes('DELI')) {
                $(this).show();
            }
        });

    }
    indexCheckboxes();
});
var showDiscrFound = $('.showDiscrFound').click(function(){
    $('#discrFound').text('');
    let text = $(this).attr('title');
    $('#discrFound').append("<div>Item(s) found in multiple batches:</div>");
    $('#discrFound').append(text);
});
var showScaleSaleFound = $('.showScaleSaleFound').click(function(){
    $('#saleScaleFound').text('');
    let text = $(this).attr('title');
    $('#saleScaleFound').append(text);
});
JAVASCRIPT;

    }

    public function css_content()
    {
        return <<<HTML
span.supertab {
    padding: 2px;
    margin: 1px;
    cursor: default;
    font-weight: bold;
    text-shadow: 1px 1px lightgrey;
}
button:focus {
    color: lightblue;
}
.panel-default {
    //border: none;
}
table.alert-info,
table.alert-primary,
table.alert-success {
    background: #e0e0e0;
}
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
        <h4>Rolling Price Changes</h4>
        <p>Enter a date range to pull up a list of items in the Vendor Pricing
            Batch page from vendors who had products in price change batches
            that were forced within the span of time requested.</p>
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

