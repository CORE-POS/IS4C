<?php
/*******************************************************************************

    Copyright 2009,2010 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
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
if (!function_exists("updateProductAllLanes")) include($FANNIE_ROOT.'item/laneUpdates.php');

class BatchListPage extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $auth_classes = array('batches','batches_audited');
    protected $title = 'Sales Batches Tool';
    protected $header = '';

    public $description = '[Sales Batches] is the primary tool for creating, editing, and managing 
    sale and price change batches.';
    public $themed = true;

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

        $infoQ = $dbc->prepare_statement("select discType from batchType where batchTypeID=?");
        $infoR = $dbc->exec_statement($infoQ,array($this->newType));
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
        $b->discounttype($discounttype);
        $b->priority(0);
        $b->owner($this->newOwner);
        $id = $b->save();
        
        if ($dbc->tableExists('batchowner')) {
            $insQ = $dbc->prepare_statement("insert batchowner values (?,?)");
            $insR = $dbc->exec_statement($insQ,array($id,$b->owner()));
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
        $infoR = $dbc->exec_statement($infoQ,array($this->batchType));
        $infoW = $dbc->fetch_row($infoR);
        $discounttype = $infoW['discType'];

        $model = new BatchesModel($dbc);
        $model->batchID($this->id);
        $model->batchType($this->batchType);
        $model->batchName($this->batchName);
        $model->startDate($this->startDate);
        $model->endDate($this->endDate);
        $model->discounttype($discounttype);
        $model->owner($this->owner);
        $saved = $model->save();
        
        if ($dbc->tableExists('batchowner')) {
            $checkQ = $dbc->prepare_statement("select batchID from batchowner where batchID=?");
            $checkR = $dbc->exec_statement($checkQ,array($this->id));
            if($dbc->num_rows($checkR) == 0) {
                $insQ = $dbc->prepare_statement("insert batchowner values (?,?)");
                $insR = $dbc->exec_statement($insQ,array($this->id,$this->owner));
            } else {
                $upQ = $dbc->prepare_statement("update batchowner set owner=? where batchID=?");
                $upR = $dbc->exec_statement($upQ,array($this->owner,$this->id));
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

        $delQ = $dbc->prepare_statement("delete from batches where batchID=?");
        $batchR = $dbc->exec_statement($delQ,array($this->id));
    
        $delQ = $dbc->prepare_statement("delete from batchList where batchID=?");
        $itemR = $dbc->exec_statement($delQ,array($this->id));
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

    /** ajax responses 
     * $out is the output sent back
     * by convention, the request name ($_GET['action'])
     * is prepended to all output so the javascript receiver
     * can handle responses differently as needed.
     * a backtick separates request name from data
     */
    function ajax_response($action)
    {
        global $FANNIE_SERVER_DBMS, $FANNIE_URL;
        $out = '';
        $dbc = $this->con;
        // prepend request name & backtick
        $out = $action."`";
        // switch on request name
        switch ($action){
        case 'newBatch':
            $type = FormLib::get_form_value('type',0);
            $name = FormLib::get_form_value('name','');
            $startdate = FormLib::get_form_value('startdate',date('Y-m-d'))." 00:00:00";
            $enddate = FormLib::get_form_value('enddate',date('Y-m-d'))." 23:59:59";
            $owner = FormLib::get_form_value('owner','');
            $priority = FormLib::get_form_value('priority',0);
            
            $infoQ = $dbc->prepare_statement("select discType from batchType where batchTypeID=?");
            $infoR = $dbc->exec_statement($infoQ,array($type));
            $discounttype = 1; // if no match, assuming sale is probably safer
                               // than assuming price change
            if ($infoR && ($infoW = $dbc->fetch_row($infoR))) {
                $discounttype = $infoW['discType'];
            }
            
            $b = new BatchesModel($dbc);
            $b->startDate($startdate);
            $b->endDate($enddate);
            $b->batchName($name);
            $b->batchType($type);
            $b->discounttype($discounttype);
            $b->priority($priority);
            $b->owner($owner);
            $id = $b->save();
            
            if ($dbc->tableExists('batchowner')) {
                $insQ = $dbc->prepare_statement("insert batchowner values (?,?)");
                $insR = $dbc->exec_statement($insQ,array($id,$owner));
            }
            
            $out = $this->batchListDisplay();
            break;
        default:
            $out .= 'bad request';
            break;
        }
        
        print $out;
        return;
    }

    /* input functions
     * functions for generating content that goes in the
     * inputarea div
     */
    function newBatchInput()
    {
        global $FANNIE_URL, $FANNIE_OP_DB;
        
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $ownersQ = $dbc->prepare("SELECT super_name 
                                  FROM MasterSuperDepts 
                                  GROUP BY super_name 
                                  ORDER BY super_name");
        $ownersR = $dbc->execute($ownersQ);
        $owners = array();
        while($ownersW = $this->con->fetch_row($ownersR)) {
            $owners[] = $ownersW[0];
        }
        $owners[] = 'IT';

        $typesQ = $dbc->prepare("SELECT batchTypeID,
                                    typeDesc 
                                 FROM batchType 
                                 ORDER BY batchTypeID");
        $typesR = $dbc->execute($typesQ);
        $types = array();
        while ($typesW = $dbc->fetch_row($typesR)) {
            $types[$typesW['batchTypeID']] = $typesW['typeDesc'];
        }

        $ret = "<form id=\"newBatchForm\" onsubmit=\"newBatch(); return false;\">";
        $ret .= '<h3>Create a Batch</h3>';
        $ret .= '<div class="row">
            <label class="col-sm-2">Batch/Sale Type</label>
            <label class="col-sm-2">Name</label>
            <label class="col-sm-2">Start date</label>
            <label class="col-sm-2">End date</label>
            <label class="col-sm-3">Owner/Super Dept.</label>
            </div>';
        $ret .= '<div class="row">';
        $ret .= '<div class="col-sm-2"><select class="form-control" id=newBatchType name="newType">';
        foreach ($types as $id=>$desc) {
            $ret .= "<option value=$id>$desc</option>";
        }
        $ret .= "</select></div>";
        $ret .= '<div class="col-sm-2"><input class="form-control" type=text id=newBatchName name="newName" /></div>';
        $ret .= '<div class="col-sm-2"><input class="form-control date-field" type=text id=newBatchStartDate name="newStart" /></div>';
        $ret .= '<div class="col-sm-2"><input class="form-control date-field" type=text id=newBatchEndDate name="newEnd" /></div>';
        $ret .= '<div class="col-sm-2"><select class="form-control" id=newBatchOwner name="newOwner">';
        $ret .= '<option value=""></option>';
        foreach ($owners as $o) {
            $ret .= "<option>$o</option>";
        }
        $ret .= "</select></div>";
        $ret .= '<div class="col-sm-1"><button type=submit class="btn btn-default">Create Batch</button></div>';
        $ret .= '</div>';
        
        $ret .= "<p></p>"; // spacer

        $ret .= '<div class="row">';
        $ret .= '<div class="col-sm-6">';
        $ret .= "<select class=\"form-control\" id=filterOwner onchange=\"changeOwnerFilter(this.value);\">";
        $ret .= '<option value="">Filter list by batch owner / super dept.</option>';
        foreach ($owners as $o) {
            $ret .= "<option>$o</option>";
        }
        $ret .= "</select>";
        $ret .= '</div>';
        
        $ret .= " <a href=\"{$FANNIE_URL}admin/labels/BatchShelfTags.php\">Print shelf tags</a>";
        $ret .= '</div>';

        $ret .= '<input type="hidden" id="ownerJSON" value=\'' . json_encode($owners) . '\' />';
        $ret .= '<input type="hidden" id="typeJSON" value=\'' . json_encode($types) . '\' />';

        return $ret;
    }

    /* display functions
     * functions for generating content that goes in the
     * displayarea div
     */
    function batchListDisplay($filter='', $mode='all', $maxBatchID='')
    {
        global $FANNIE_URL;
        $dbc = $this->con;
        
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

        $ret .= '<table class="table tablesorter"><thead>';
        $ret .= "<tr><th bgcolor=$colors[$c]>Batch Name</th>";
        $ret .= "<th bgcolor=$colors[$c]>Type</th>";
        $ret .= "<th bgcolor=$colors[$c]>Start date</th>";
        $ret .= "<th bgcolor=$colors[$c]>End date</th>";
        $ret .= "<th bgcolor=$colors[$c]>Owner/Super Dept.</th>";
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
                   $ownerclause
                    LEFT JOIN batchType AS t ON b.batchType = t.batchTypeID
                   WHERE 1=1 ";
        $args = array();
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
        if ($filter != '') {
            $fetchQ .= ' AND ' . $owneralias . '.owner = ? ';
            $args[] = $filter;
        }
        $fetchQ .= ' ORDER BY b.batchID DESC';
        $fetchQ = $dbc->add_select_limit($fetchQ,50);
        if (is_numeric($maxBatchID)) {
            $fetchQ = str_replace("WHERE ","WHERE b.batchID < ? AND ",$fetchQ);
            array_unshift($args,$maxBatchID);
        }
        $fetchR = $dbc->exec_statement($fetchQ,$args);
        
        $count = 0;
        $lastBatchID = 0;
        while ($fetchW = $dbc->fetch_array($fetchR)) {
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
            $ret .= "<td bgcolor=$colors[$c] id=startdate{$id}>" 
                . (strtotime($fetchW['startDate']) ? date('Y-m-d', strtotime($fetchW['startDate'])) : '')
                . "</td>";
            $ret .= "<td bgcolor=$colors[$c] id=enddate{$id}>" 
                . (strtotime($fetchW['endDate']) ? date('Y-m-d', strtotime($fetchW['endDate'])) : '')
                . "</td>";
            $ret .= "<td bgcolor=$colors[$c] id=owner{$id}>{$fetchW['owner']}</td>";
            $ret .= "<td bgcolor=$colors[$c] id=edit{$id}>
                <a href=\"\" onclick=\"editBatchLine({$id}); return false;\" class=\"batchEditLink\">
                    " . \COREPOS\Fannie\API\lib\FannieUI::editIcon() . "
                </a>
                <a href=\"\" onclick=\"saveBatchLine({$id}); return false;\" class=\"batchSaveLink collapse\">
                    " . \COREPOS\Fannie\API\lib\FannieUI::saveIcon() . "
                </a>
                </td>";
            $ret .= "<td bgcolor=$colors[$c]><a href=\"\" 
                onclick=\"deleteBatch({$id},'{$fetchW['batchName']}'); return false;\">"
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
        global $FANNIE_URL;
        $this->add_script('list.js');
        $this->add_script('../../src/javascript/tablesorter/jquery.tablesorter.min.js');
        $this->add_css_file('index.css');
        ob_start();
        ?>
        <div id="inputarea">
        <?php echo $this->newBatchInput(); ?>
        </div>
        <div id="displayarea">
        <?php echo $this->batchListDisplay(); ?>
        </div>
        <input type=hidden id=uid value="<?php echo $this->current_user; ?>" />
        <input type=hidden id=isAudited value="<?php echo $this->audited; ?>" />
        <?php
        $ret = ob_get_clean();
    
        $ret .= "<input type=hidden id=buttonimgpath value=\"{$FANNIE_URL}src/img/buttons/\" />";
        $this->add_onload_command("\$('.tablesorter').tablesorter();");

        return $ret;
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
}

FannieDispatch::conditionalExec(false);

