<?php
/*******************************************************************************

    Copyright 2009,2010 Whole Foods Co-op

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
if (!function_exists('checkLogin')) {
    include_once($FANNIE_ROOT . 'auth/login.php');
}

class BatchListPage extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $auth_classes = array('batches','batches_audited');
    protected $title = 'Sales Batches Tool';
    protected $header = '';

    public $description = '[Sales Batches] is the primary tool for creating, editing, and managing 
    sale and price change batches.';

    private $audited = 1;
    private $con = null;

    function preprocess()
    {
        global $FANNIE_OP_DB;
        // maintain user logins longer
        refreshSession();
        if (validateUserQuiet('batches')) {
            $this->audited = 0;
        }

        $this->con = FannieDB::get($FANNIE_OP_DB);

        // autoclear old data from clipboard table on intial page load
        $clipboard = $this->con->tableDefinition('batchCutPaste');
        if (isset($clipboard['tdate'])) {
            $this->con->query('DELETE FROM batchCutPaste WHERE tdate < ' . $this->con->curdate());
        }

        $this->__routes[] = 'post<newType><newName><newStart><newEnd><newOwner>';
        $this->__routes[] = 'post<id><batchName><batchType><startDate><endDate><owner>';
        $this->__routes[] = 'get<mode><filter><max>';
        $this->__routes[] = 'post<delete><id>';

        return parent::preprocess();
    }

    protected function post_newType_newName_newStart_newEnd_newOwner_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $json = array('error'=>0, 'msg'=>'Created batch ' . $this->newName);

        $infoQ = $dbc->prepare("select discType from batchType where batchTypeID=?");
        $infoR = $dbc->execute($infoQ,array($this->newType));
        $discounttype = 1; // if no match, assuming sale is probably safer
                           // than assuming price change
        if ($infoR && ($infoW = $dbc->fetch_row($infoR))) {
            $discounttype = $infoW['discType'];
        }
        
        $b = new BatchesModel($dbc);
        $b->startDate($this->newStart);
        $b->endDate($this->newEnd);
        $b->batchName($this->newName);
        $b->batchType($this->newType);
        $b->discountType($discounttype);
        $b->priority(0);
        $b->owner($this->newOwner);
        $id = $b->save();

        if ($this->config->get('STORE_MODE') === 'HQ') {
            StoreBatchMapModel::initBatch($id);
        }

        if ($dbc->tableExists('batchowner')) {
            $insQ = $dbc->prepare("insert batchowner values (?,?)");
            $insR = $dbc->execute($insQ,array($id,$b->owner()));
        }
        
        if ($id === false) {
            $json['error'] = 1;
            $json['msg'] = 'An error occured creating the batch ' . $this->newName;
        } else {
            $json['new_list'] = $this->batchListDisplay();
        }
        echo json_encode($json);

        return false;
    }

    protected function post_id_batchName_batchType_startDate_endDate_owner_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        
        $infoQ = $dbc->prepare("SELECT discType 
                                FROM batchType 
                                WHERE batchTypeID=?");
        $infoR = $dbc->execute($infoQ,array($this->batchType));
        $infoW = $dbc->fetch_row($infoR);
        $discounttype = $infoW['discType'];

        $model = new BatchesModel($dbc);
        $model->batchID($this->id);
        $model->batchType($this->batchType);
        $model->batchName($this->batchName);
        $model->startDate($this->startDate);
        $model->endDate($this->endDate);
        $model->discountType($discounttype);
        $model->owner($this->owner);
        $saved = $model->save();
        
        if ($dbc->tableExists('batchowner')) {
            $checkQ = $dbc->prepare("select batchID from batchowner where batchID=?");
            $checkR = $dbc->execute($checkQ,array($this->id));
            if($dbc->num_rows($checkR) == 0) {
                $insQ = $dbc->prepare("insert batchowner values (?,?)");
                $insR = $dbc->execute($insQ,array($this->id,$this->owner));
            } else {
                $upQ = $dbc->prepare("update batchowner set owner=? where batchID=?");
                $upR = $dbc->execute($upQ,array($this->owner,$this->id));
            }
        }

        $json = array('error'=>0, 'msg'=>'Saved batch ' . $this->batchName);
        if ($saved === false) {
            $json['error'] = 1;
            $json['msg'] = 'Error saving batch ' . $this->batchName;
        }
        echo json_encode($json);

        return false;
    }

    protected function get_mode_filter_max_handler()
    {
        echo $this->batchListDisplay($this->filter, $this->mode, $this->max);

        return false;
    }

    protected function post_delete_id_handler()
    {
        global $FANNIE_OP_DB;
        $json = array('error'=>0,'msg'=>'Deleted batch #' . $this->id);
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $batch = new BatchesModel($dbc);
        $batch->forceStopBatch($this->id);

        $delQ = $dbc->prepare("delete from batches where batchID=?");
        $batchR = $dbc->execute($delQ,array($this->id));
    
        $delQ = $dbc->prepare("delete from batchList where batchID=?");
        $itemR = $dbc->execute($delQ,array($this->id));
        if ($itemR !== false && $batchR === false) {
            $json['error'] = 1;
            $json['msg'] = 'Items were unsaled and removed from the batch, but the batch could not be deleted';
        } elseif ($itemR === false && $batchR !== false) {
            $json['error'] = 1;
            $json['msg'] = 'Items were unsaled and the batch was deleted, but some orphaned items remain in the batchList table.'
                . ' This probably is not a big deal unless it happens often.';
        } elseif ($itemR === false && $batchR === false) {
            $json['error'] = 1;
            $json['msg'] = 'Items were unsaled but an error occurred deleting the batch.';
        }

        echo json_encode($json);

        return false;
    }

    /* input functions
     * functions for generating content that goes in the
     * inputarea div
     */
    function newBatchInput()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $url = $this->config->get('URL');

        $ownersQ = $dbc->prepare("SELECT super_name 
                                  FROM MasterSuperDepts 
                                  GROUP BY super_name 
                                  ORDER BY super_name");
        $ownersR = $dbc->execute($ownersQ);
        $owners = array();
        $oOpts = '';
        while ($ownersW = $this->con->fetchRow($ownersR)) {
            $owners[] = $ownersW[0];
            $oOpts .= '<option>' . $ownersW[0] . '</option>';
        }
        $owners[] = 'MULTIPLE DEPTS.';
        $oOpts .= '<option>MULTIPLE DEPTS.</option>';
        $oJSON = json_encode($owners);

        $typesQ = $dbc->prepare("SELECT batchTypeID,
                                    typeDesc 
                                 FROM batchType 
                                 ORDER BY batchTypeID");
        $typesR = $dbc->execute($typesQ);
        $types = array();
        $tOpts = '';
        while ($typesW = $dbc->fetchRow($typesR)) {
            $types[$typesW['batchTypeID']] = $typesW['typeDesc'];
            $tOpts .= sprintf('<option value="%d">%s</option>', $typesW['batchTypeID'], $typesW['typeDesc']);
        }
        $tJSON = json_encode($types);
        $stores = new StoresModel($dbc);
        $stores->hasOwnItems(1);
        $storeOpts = '';
        foreach ($stores->find() as $obj) {
            $storeOpts .= sprintf('<option value="%d">%s</option>',
                $obj->storeID(), $obj->description());
        }

        return <<<HTML
<form id="newBatchForm" onsubmit="newBatch(); return false;">
    <h3>Create a Batch</h3>
        <div class="row">
            <label class="col-sm-2">Batch/Sale Type</label>
            <label class="col-sm-2">Name</label>
            <label class="col-sm-2">Start date</label>
            <label class="col-sm-2">End date</label>
            <label class="col-sm-3">Owner/Super Dept.</label>
        </div>
        <div class="row">
            <div class="col-sm-2"><select class="form-control" id=newBatchType name="newType">
                {$tOpts}
            </select></div>
            <div class="col-sm-2"><input class="form-control" type=text placeholder="Batch Name" id=newBatchName name="newName" /></div>
            <div class="col-sm-2"><input class="form-control date-field" placeholder="Start Date" type=text id=newBatchStartDate name="newStart" /></div>
            <div class="col-sm-2"><input class="form-control date-field" placeholder="End Date" type=text id=newBatchEndDate name="newEnd" /></div>
            <div class="col-sm-2"><select class="form-control" id=newBatchOwner name="newOwner">
                <option value=""></option>
                {$oOpts}
            </select></div>
            <div class="col-sm-1"><button type=submit class="btn btn-default">Create Batch</button></div>
        </div>
</form>
        <p></p> <!-- spacer -->
        <div class="row">
            <div class="col-sm-4">
                <select class="form-control" id=filterOwner onchange="reFilter();">
                    <option value="">Filter list by batch owner / super dept.</option>
                    {$oOpts}
                </select>
            </div>
            <div class="col-sm-2">
                <select class="form-control" id="filterStore" onchange="reFilter();">
                    <option value="">Store...</option>
                    {$storeOpts}
                </select>
            </div>
            <div class="col-sm-2">
                <input type="text" class="form-control" id="filterName" 
                    placeholder="Batch name..." onchange="reFilter();" />
            </div>
            <div class="col-sm-2">
                <input type="text" class="form-control date-field" id="filterDate"
                    placeholder="Batch date..." onchange="reFilter();" />
            </div>
            <a href="{$url}admin/labels/BatchShelfTags.php">Print shelf tags</a>
        </div>
        <input type="hidden" id="ownerJSON" value='{$oJSON}' />
        <input type="hidden" id="typeJSON" value='{$tJSON}' />
HTML;
    }

    /* display functions
     * functions for generating content that goes in the
     * displayarea div
     */
    function batchListDisplay($filter='', $mode='', $maxBatchID='')
    {
        global $FANNIE_URL;
        $dbc = $this->con;

        $filters = json_decode($filter, true);
        if ($filters === null) {
            $filters = array();
        }

        if ($mode === '') {
            $mode = $this->config->get('BATCH_VIEW', 'all');
        }

        $colors = array('#ffffff','#ffffcc');
        $c = 0;
        $ret = "";
        $ret .= "<b>Display</b>: ";
        if ($mode != 'pending') {
            $ret .= "<a href=\"\" onclick=\"changeTimeSlice('pending'); return false;\">Pending</a> | ";
        } else {
            $ret .= "Pending | ";
        }
        if ($mode != 'current') {
            $ret .= "<a href=\"\" onclick=\"changeTimeSlice('current'); return false;\">Current</a> | ";
        } else {
            $ret .= "Current | ";
        }
        if ($mode != 'historical') {
            $ret .= "<a href=\"\" onclick=\"changeTimeSlice('historical'); return false;\">Historical</a> | ";
        } else {
            $ret .= "Historical | ";
        }
        if ($mode != 'all') {
            $ret .= "<a href=\"\" onclick=\"changeTimeSlice('all'); return false;\">All</a>";
        } else {
            $ret .= "All<br />";
        }

        $sort = \COREPOS\Fannie\API\lib\FannieUI::tableSortIcons();

        $ret .= '<table class="table tablesorter tablesorter-core"><thead>';
        $ret .= "<tr><th bgcolor=$colors[$c]>Batch Name$sort</th>";
        $ret .= "<th bgcolor=$colors[$c]>Type$sort</th>";
        $ret .= "<th bgcolor=$colors[$c]>Items$sort</th>";
        $ret .= "<th bgcolor=$colors[$c]>Start date$sort</th>";
        $ret .= "<th bgcolor=$colors[$c]>End date$sort</th>";
        $ret .= "<th bgcolor=$colors[$c]>Owner/Super Dept.$sort</th>";
        $ret .= "<th colspan=\"3\">&nbsp;</th></tr></thead><tbody>";
        
        // owner column might be in different places
        // depending if schema is up to date
        $ownerclause = "'' as owner FROM batches AS b";
        $batchesTable = $dbc->tableDefinition('batches');
        $owneralias = '';
        if (isset($batchesTable['owner'])) {
            $ownerclause = 'b.owner FROM batches AS b';
            $owneralias = 'b';
        } else if ($dbc->tableExists('batchowner')) {
            $ownerclause = 'o.owner FROM batches AS b LEFT JOIN
                            batchowner AS o ON b.batchID=o.batchID';
            $owneralias = 'o';
        }

        // the 'all' query
        // where clause is for str_ireplace below
        $fetchQ = "SELECT b.batchName,
                        b.batchType,
                        b.startDate,
                        b.endDate,
                        b.batchID,
                        t.typeDesc,
                        COUNT(l.upc) AS items,
                   $ownerclause
                    LEFT JOIN batchType AS t ON b.batchType = t.batchTypeID
                    LEFT JOIN batchList AS l ON b.batchID=l.batchID ";
        $args = array();
        if (isset($filters['store']) && $filters['store'] != '') {
            $fetchQ .= ' INNER JOIN StoreBatchMap AS m ON b.batchID=m.batchID AND m.storeID=? ';
            $args[] = $filters['store'];
        }
        $fetchQ .= " WHERE 1=1 ";
        switch($mode) {
            case 'pending':
                $fetchQ .= ' AND '. $dbc->datediff("b.startDate",$dbc->now()) . ' > 0 ';
                break;
            case 'current':
                $fetchQ .= '
                    AND ' . $dbc->datediff("b.startDate",$dbc->now()) . ' <= 0
                    AND ' . $dbc->datediff("b.endDate",$dbc->now()) . ' >= 0 ';
                break;
            case 'historical':
                $fetchQ .= ' AND '. $dbc->datediff("b.startDate",$dbc->now()) . ' <= 0 ';
                break;    
        }
        // use a filter - only works in 'all' mode
        if (isset($filters['owner']) && $filters['owner'] != '') {
            $fetchQ .= ' AND ' . $owneralias . '.owner = ? ';
            $args[] = $filters['owner'];
        }
        if (isset($filters['name']) && $filters['name'] != '') {
            $fetchQ .= ' AND b.batchName LIKE ? ';
            $args[] = '%' . $filters['name'] . '%';
        }
        if (isset($filters['date']) && $filters['date'] != '') {
            $fetchQ .= ' AND ? BETWEEN b.startDate AND b.endDate ';
            $args[] = $filters['date'];
        }
        $fetchQ .= ' GROUP BY b.batchName, b.batchType, b.startDate, b.endDate, b.batchID,
                        t.typeDesc, ' . $owneralias . '.owner ';
        $fetchQ .= ' ORDER BY b.batchID DESC';
        $fetchQ = $dbc->addSelectLimit($fetchQ,50);
        if (is_numeric($maxBatchID)) {
            $fetchQ = str_replace("WHERE ","WHERE b.batchID < ? AND ",$fetchQ);
            array_unshift($args,$maxBatchID);
        }
        $fetchR = $dbc->execute($fetchQ,$args);
        
        $count = 0;
        $lastBatchID = 0;
        while ($fetchW = $dbc->fetchRow($fetchR)) {
            /**
              strtotime() and date() are not reciprocal functions
              date('Y-m-d', strtotime('0000-00-00')) results in
              -0001-11-30 instead of the expected 0000-00-00
            */
            if ($fetchW['startDate'] == '0000-00-00 00:00:00') {
                $fetchW['startDate'] = '';
            }
            if ($fetchW['endDate'] == '0000-00-00 00:00:00') {
                $fetchW['endDate'] = '';
            }
            $c = ($c + 1) % 2;
            $id = $fetchW['batchID'];
            $ret .= '<tr id="batchRow' . $fetchW['batchID'] . '" class="batchRow">';
            $ret .= "<td bgcolor=$colors[$c] id=name{$id}><a id=namelink{$id} 
                href=\"EditBatchPage.php?id={$id}\">{$fetchW['batchName']}</a></td>";
            $ret .= "<td bgcolor=$colors[$c] id=type{$id}>" . $fetchW['typeDesc'] . "</td>";
            $ret .= "<td bgcolor=$colors[$c]>" . $fetchW['items'] . "</td>";
            $ret .= "<td bgcolor=$colors[$c] id=startdate{$id}>" 
                . (strtotime($fetchW['startDate']) ? date('Y-m-d', strtotime($fetchW['startDate'])) : '')
                . "</td>";
            $ret .= "<td bgcolor=$colors[$c] id=enddate{$id}>" 
                . (strtotime($fetchW['endDate']) ? date('Y-m-d', strtotime($fetchW['endDate'])) : '')
                . "</td>";
            $ret .= "<td bgcolor=$colors[$c] id=owner{$id}>{$fetchW['owner']}</td>";
            $ret .= "<td bgcolor=$colors[$c] id=edit{$id}>
                <a href=\"\" onclick=\"editBatchLine({$id}); return false;\" class=\"batchEditLink btn btn-default btn-xs\">
                    " . \COREPOS\Fannie\API\lib\FannieUI::editIcon() . "
                </a>
                <a href=\"\" onclick=\"saveBatchLine({$id}); return false;\" class=\"batchSaveLink btn btn-default btn-xs collapse\">
                    " . \COREPOS\Fannie\API\lib\FannieUI::saveIcon() . "
                </a>
                </td>";
            $ret .= "<td bgcolor=$colors[$c]><a href=\"\" class=\"btn btn-danger btn-xs\"
                onclick=\"deleteBatch({$id}," . htmlspecialchars(json_encode($fetchW['batchName'])) . "); return false;\">"
                . \COREPOS\Fannie\API\lib\FannieUI::deleteIcon() . '</a></td>';
            $ret .= "<td bgcolor=$colors[$c]><a href=\"batchReport.php?batchID={$id}\">Report</a></td>";
            $ret .= "</tr>";
            $count++;
            $lastBatchID = $fetchW[4];
        }
        
        $ret .= "</tbody></table>";

        if (is_numeric($maxBatchID)) {
            $ret .= sprintf("<a href=\"\" 
                    onclick=\"scroll(0,0); batchListPager('%s','%s',''); return false;\">First page</a>
                     | ",
                    $filter,$mode);
        }
        if ($count >= 50) {
            $ret .= sprintf("<a href=\"\" 
                    onclick=\"scroll(0,0); batchListPager('%s','%s',%d); return false;\">Next page</a>",
                    $filter,$mode,$lastBatchID);                
        } else {
            $ret .= "Next page";
        }

        return $ret;
    }

    function get_view()
    {
        $url = $this->config->get('URL');
        $inputForm = $this->newBatchInput();
        $batchList = $this->batchListDisplay();
        $this->addScript('list.js');
        $this->addScript('../../src/javascript/tablesorter/jquery.tablesorter.min.js');
        $this->add_css_file('index.css');
        $this->addOnloadCommand("\$('.tablesorter').tablesorter();");

        return <<<HTML
<div id="inputarea">
    {$inputForm}
</div>
<div id="displayarea">
    {$batchList}
</div>
<input type=hidden id=uid value="{$this->current_user}" />
<input type=hidden id=isAudited value="{$this->audited}" />
<input type=hidden id=buttonimgpath value="{$url}src/img/buttons/" />
HTML;
    }

    public function helpContent()
    {
        return '<p>Batches apply changes to items on a specified date. They are
            used to place items on sale for a defined period of time as well as
            to change retail prices on a set of items in a coordinated manner.</p>
            <p>Batch Type controls whether it is a sale or price change. There may
            be several different type of sales. Sale prices will first apply on
            the start date and will stop the day <em>after</em> the end date (i.e.,
            the end date is inclusive). Price change batches apply on the start
            date but end date has no meaning for price changes. Name and owner are
            for organization of the list.</p>
            <p>An item may only be in one active sale batch.</p>
            ';
    }

    /**
      Create, update, and delete a batch
      Try each mode with and without an owner filter
    */
    public function unitTest($phpunit)
    {
        $get = $this->get_view();
        $phpunit->assertNotEquals(0, strlen($get));

        $this->connection->selectDB($this->config->get('OP_DB'));
        $model = new BatchesModel($this->connection);

        $this->newType = 1;
        $this->newName = 'Test BatchListPage';
        $this->newStart = date('Y-m-d 00:00:00');
        $this->newEnd = date('Y-m-d 00:00:00');
        $this->newOwner = 'MULTIPLE DEPTS.';
        ob_start();
        $this->post_newType_newName_newStart_newEnd_newOwner_handler();
        ob_end_clean();
        $model->batchName($this->newName);
        $matches = $model->find();
        $phpunit->assertEquals(1, count($matches));
        $model->reset();
        $model->batchID($matches[0]->batchID());
        $phpunit->assertEquals(true, $model->load());
        $phpunit->assertEquals($this->newType, $model->batchType());
        $phpunit->assertEquals($this->newName, $model->batchName());
        $phpunit->assertEquals($this->newStart, $model->startDate());
        $phpunit->assertEquals($this->newEnd, $model->endDate());
        $phpunit->assertEquals($this->newOwner, $model->owner());

        $this->id = $model->batchID();
        $this->batchName = 'Change BatchListPage';
        $this->batchType = 2;
        $this->startDate = date('Y-m-d 00:00:00', strtotime('yesterday'));
        $this->endDate = $this->startDate;
        $this->owner = 'Admin';
        ob_start();
        $this->post_id_batchName_batchType_startDate_endDate_owner_handler();
        ob_end_clean();
        $model->reset();
        $model->batchID($this->id);
        $phpunit->assertEquals(true, $model->load());
        $phpunit->assertEquals($this->batchType, $model->batchType());
        $phpunit->assertEquals($this->batchName, $model->batchName());
        $phpunit->assertEquals($this->startDate, $model->startDate());
        $phpunit->assertEquals($this->endDate, $model->endDate());
        $phpunit->assertEquals($this->owner, $model->owner());

        $this->delete = 1;
        ob_start();
        $this->post_delete_id_handler();
        ob_end_clean();
        $model->reset();
        $model->batchID($this->id); 
        $phpunit->assertEquals(false, $model->load());

        $modes = array('pending', 'current', 'historical', 'all');
        foreach ($modes as $m) {
            $get = $this->batchListDisplay('', $m, rand(0, 50));
            $phpunit->assertNotEquals(0, strlen($get));
            $get = $this->batchListDisplay('MULTIPLE DEPTS.', $m, rand(0, 50));
            $phpunit->assertNotEquals(0, strlen($get));
        }
    }
}

FannieDispatch::conditionalExec();

