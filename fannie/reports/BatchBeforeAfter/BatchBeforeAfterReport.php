<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

class BatchBeforeAfterReport extends FannieReportPage 
{
    protected $header = "Select batch";
    protected $title = "Fannie :: Batch Report";
    protected $report_cache = 'none';
    protected $report_headers = array('UPC','Description','$','Qty','Location');
    protected $required_fields = array('batchID');

    public $description = '[Batch Before After Report] lists sales for items in a sales batch before, during, and after the promotional period.';
    public $themed = true;
    public $report_set = 'Batches';
    protected $new_tablesorter = true;

    function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $bStart = FormLib::get_form_value('date1','');
        $bEnd = FormLib::get_form_value('date2','');
        $model = new BatchesModel($dbc);

        /**
          Assemble argument array and appropriate string
          for an IN clause in a prepared statement
        */
        $batchID = $this->form->batchID;
        if (!is_array($batchID)) {
            $batchID = array($batchID);
        }
        $inArgs = array();
        $inClause = '(';
        $upcs = array();
        foreach ($batchID as $bID) {
            $inClause .= '?,';
            $inArgs[] = $bID;
            $upcs = array_merge($upcs, $model->getUPCs($bID));
        }
        $upcs = array_unique($upcs);
        $inClause = rtrim($inClause,',').')';

        $batchInfoQ = '
            SELECT batchName,
                year(startDate) as sy, 
                month(startDate) as sm, 
                day(startDate) as sd,
                year(endDate) as ey, 
                month(endDate) as em, 
                day(endDate) as ed
            FROM batches 
            WHERE batchID IN '.$inClause;
        $batchInfoP = $dbc->prepare($batchInfoQ);
        $batchInfoR = $dbc->execute($batchInfoP, $inArgs);

        $bName = "";
        while ($batchInfoW = $dbc->fetchRow($batchInfoR)) {
            $bName .= $batchInfoW['batchName']." ";
            if (empty($bStart)) {
                $bStart = sprintf("%d-%02d-%02d",$batchInfoW['sy'],
                    $batchInfoW['sm'],$batchInfoW['sd']);
            }
            if (empty($bEnd)){ 
                $bEnd = sprintf("%d-%02d-%02d",$batchInfoW['ey'],
                    $batchInfoW['em'],$batchInfoW['ed']);
            }
        }
        
        $dlog = DTransactionsModel::selectDlog($bStart,$bEnd);
        $bStart .= ' 00:00:00';
        $bEnd .= ' 23:59:59';
        $reportArgs = array($bStart, $bEnd);
        $in_sql = '';
        list($in_sql, $reportArgs) = $dbc->safeInClause($upcs, $reportArgs);

        $salesBatchQ ="
            SELECT d.upc, 
                p.description, 
                l.floorSectionID,
                f.name AS location,
                SUM(d.total) AS sales, "
                . DTrans::sumQuantity('d') . " AS quantity 
            FROM $dlog AS d "
                . DTrans::joinProducts('d', 'p', 'INNER') . "
            LEFT JOIN prodPhysicalLocation AS l ON l.upc=p.upc
            LEFT JOIN FloorSections as f ON f.floorSectionID=l.floorSectionID
            WHERE d.tdate BETWEEN ? AND ?
                AND d.upc IN ($in_sql)
            GROUP BY d.upc, 
                p.description
            ORDER BY d.upc";
        $salesBatchP = $dbc->prepare($salesBatchQ);
        $inArgs[] = $bStart;
        $inArgs[] = $bEnd;
        $salesBatchR = $dbc->execute($salesBatchP, $reportArgs);

        /**
          Simple report
        
          Issue a query, build array of results
        */
        $ret = array();
        while ($row = $dbc->fetchRow($salesBatchR)) {
            $record = array();
            $record[] = $row['upc'];
            $record[] = $row['description'];
            $record[] = $row['sales'];
            $record[] = $row['quantity'];
            $record[] = $row['location'] === null ? '' : $row['location'];
            $ret[] = $record;
        }
        return $ret;
    }
    
    /**
      Sum the quantity and total columns
    */
    function calculate_footers($data)
    {
        $sumQty = 0.0;
        $sumSales = 0.0;
        foreach ($data as $row) {
            $sumQty += $row[3];
            $sumSales += $row[2];
        }

        return array('Total',null,$sumSales,$sumQty);
    }

    function form_content()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $filter1 = FormLib::get('btype','');
        $filter2 = FormLib::get('owner','');

        $ownerQ = $dbc->prepare("
            SELECT super_name 
            FROM superDeptNames 
            WHERE superID > 0
            ORDER BY superID");
        $ownerR = $dbc->execute($ownerQ);
        $o_opts = "<option value=\"\">Select owner</option>";
        while ($ownerW = $dbc->fetchRow($ownerR)) {
            $o_opts .= sprintf("<option %s>%s</option>",
                (($filter2==$ownerW[0])?'selected':''),
                $ownerW[0]);
        }

        $typeQ = $dbc->prepare("
            SELECT batchTypeID,
                typeDesc 
            FROM batchType 
            ORDER BY batchTypeID");
        $typeR = $dbc->execute($typeQ);
        $t_opts = "<option value=\"\">Select type</option>";
        while ($typeW = $dbc->fetchRow($typeR)) {
            $t_opts .= sprintf("<option %s value=%d>%s</option>",
                (($filter1==$typeW[0])?'selected':''),
                $typeW[0],$typeW[1]);
        }

        ob_start();
        echo '<div class="form-inline">';
        echo "<label>Filters</label> ";
        echo '<select id="typef" class="form-control"
            onchange="location=\'BatchReport.php?btype=\'+$(\'#typef\').val()+\'&owner=\'+escape($(\'#ownerf\').val());">';
        echo $t_opts;
        echo '</select>';
        echo '&nbsp;&nbsp;&nbsp;&nbsp;';
        echo '<select id="ownerf" class="form-control"
            onchange="location=\'BatchReport.php?btype=\'+$(\'#typef\').val()+\'&owner=\'+escape($(\'#ownerf\').val());">';
        echo $o_opts;
        echo '</select>';
        echo '</div>';

        echo '<hr />';

        $batchQ = "
            SELECT b.batchID,
                batchName 
            FROM batches AS b
            WHERE 1=1 ";
        $args = array();
        if ($filter1 !== "") {
            $batchQ .= " AND batchType=? ";
            $args[] = $filter1;
        }
        if ($filter2 !== "") {
            $batchQ .= " AND owner=? ";
            $args[] = $filter2;
        }
        $batchQ .= "ORDER BY b.batchID desc";
        $batchP = $dbc->prepare($batchQ);
        $batchR = $dbc->execute($batchP, $args);

        echo '<form action="BatchReport.php" method="get">';
        echo '<div class="row">';
        echo '<div class="col-sm-5">';
        echo '<select size="15" multiple name=batchID[] class="form-control" required>';
        while ($batchW = $dbc->fetchRow($batchR)) {
            printf('<option value="%d">%s</option>',
                $batchW['batchID'],$batchW['batchName']);
        }
        echo '</select>';
        echo '</div>';
        echo '<div class="col-sm-7">';
        echo '<label>Start Date</label>';
        echo '<input class="form-control date-field" name="date1" id="date1" />';
        echo '<label>End Date</label>';
        echo '<input class="form-control date-field" name="date2" id="date2" />';
        echo '<p><label>Excel ';
        echo '<input type="checkbox" name="excel" value="xls" /></label></p>';
        echo '<p><button type="submit" class="btn btn-default">Run Report</button></p>';
        echo '</div>';
        echo '</div>';

        echo '</form>';

        return ob_get_clean();
    }

    function report_description_content()
    {
        $FANNIE_URL = $this->config->get('URL');
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $ret = array();
        $bStart = FormLib::get('date1','');
        $bEnd = FormLib::get('date2','');
        $batchID = FormLib::get('batchID','0');
        if (!is_array($batchID)) {
            $batchID = array($batchID);
        }
        list($inClause, $inArgs) = $dbc->safeInClause($batchID);
        $batchInfoQ = $dbc->prepare("
            SELECT batchName,
                startDate AS startDate,
                endDate AS endDate 
            FROM batches 
            WHERE batchID IN ($inClause)");
        $batchInfoR = $dbc->execute($batchInfoQ,$inArgs);
        $bName = "";
        while ($batchInfoW = $dbc->fetchRow($batchInfoR)) {
            $bName .= $batchInfoW['batchName']." ";
            if (empty($bStart)) {
                $bStart = $batchInfoW['startDate'];
            }
            if (empty($bEnd)) {
                $bEnd = $batchInfoW['endDate'];
            }
        }
        $ret[] = '<br /><span style="font-size:150%;">'.$bName.'</span>';
        if ($this->report_format == 'html') {
            if (!$this->new_tablesorter) {
                $this->add_script($FANNIE_URL.'src/javascript/jquery.js');
                $this->add_script($FANNIE_URL.'src/javascript/jquery-ui.js');
                $this->add_css_file($FANNIE_URL.'src/javascript/jquery-ui.css');
            }
            $ret[] = '<p><form action="BatchReport.php" method="get">';
            $ret[] = "<span style=\"color:black; display:inline;\">From: 
                    <input type=\"text\" name=\"date1\" size=\"10\" value=\"$bStart\" id=\"date1\" />
                    to: 
                    <input type=\"text\" name=\"date2\" size=\"10\" value=\"$bEnd\" id=\"date2\" />
                    </span><input type=\"submit\" value=\"Change Dates\" />";
            $this->add_onload_command("\$('#date1').datepicker({dateFormat:'yy-mm-dd'});");
            $this->add_onload_command("\$('#date2').datepicker({dateFormat:'yy-mm-dd'});");
            foreach($batchID as $bID) {
                $ret[] = sprintf('<input type="hidden" name="batchID[]" value="%d" />', $bID);
            }
            $ret[] = '</form></p>';
        } else {
            $ret[] = "<span style=\"color:black\">From: $bStart to: $bEnd</span>";
        }

        return $ret;
    }

    public function helpContent()
    {
        return '<p>Show per-item sales data for items in a batch or set
            of batches over the given date range. The filters just narrow
            down the list of batches. You still have to make selections in
            the list.</p>';
    }
}

FannieDispatch::conditionalExec();

