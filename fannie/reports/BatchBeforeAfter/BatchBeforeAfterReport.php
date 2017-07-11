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
    protected $report_headers = array('UPC','Description','Pre $','Pre Qty', 'Promo $', 'Promo Qty', 'Post $', 'Post Qty', 'Location');
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

        $batchID = $this->form->batchID;
        $upcs = $model->getUPCs($batchID);
        $upcs = array_unique($upcs);

        $batchInfoP = $dbc->prepare("SELECT startDate, endDate FROM batches WHERE batchID=?");
        $batchInfo = $dbc->getRow($batchInfoP, array($batchID));

        $bStart = new DateTimeImmutable($batchInfo['startDate']);
        $bEnd = new DateTimeImmutable($batchInfo['endDate']);
        $diff = $bEnd->diff($bStart, true);
        $rStart = $bStart->sub($diff);
        $rEnd = $bEnd->add($diff);

        $dlog = DTransactionsModel::selectDlog($rStart->format('Y-m-d'), $rEnd->format('Y-m-d'));
        $reportArgs = array(
            $bStart->format('Y-m-d 00:00:00'), // pre sum
            $bStart->format('Y-m-d 00:00:00'),
            $bStart->format('Y-m-d 00:00:00'), // promo sum
            $bEnd->format('Y-m-d 23:59:59'),
            $bStart->format('Y-m-d 00:00:00'),
            $bEnd->format('Y-m-d 23:59:59'),
            $bEnd->format('Y-m-d 23:59:59'), // post sum
            $bEnd->format('Y-m-d 23:59:59'),
            $rStart->format('Y-m-d 00:00:00'), // report dates
            $rEnd->format('Y-m-d 23:59:59'),
        );
        $in_sql = '';
        list($in_sql, $reportArgs) = $dbc->safeInClause($upcs, $reportArgs);

        $salesBatchQ ="
            SELECT d.upc, 
                p.description, 
                f.sections AS location,
                SUM(CASE WHEN d.tdate < ? THEN total ELSE 0 END) AS preTTL,
                SUM(CASE WHEN d.tdate < ? THEN d.quantity ELSE 0 END) AS preQty,
                SUM(CASE WHEN d.tdate BETWEEN ? AND ? THEN total ELSE 0 END) AS promoTTL,
                SUM(CASE WHEN d.tdate BETWEEN ? AND ? THEN d.quantity ELSE 0 END) AS promoQty,
                SUM(CASE WHEN d.tdate > ? THEN total ELSE 0 END) AS postTTL,
                SUM(CASE WHEN d.tdate > ? THEN d.quantity ELSE 0 END) AS postQty
            FROM $dlog AS d "
                . DTrans::joinProducts('d', 'p', 'INNER') . "
                LEFT JOIN FloorSectionsListView AS f ON d.upc=f.upc AND d.store_id=f.storeID
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
            $record[] = sprintf('%.2f', $row['preTTL']);
            $record[] = sprintf('%.2f', $row['preQty']);
            $record[] = sprintf('%.2f', $row['promoTTL']);
            $record[] = sprintf('%.2f', $row['promoQty']);
            $record[] = sprintf('%.2f', $row['postTTL']);
            $record[] = sprintf('%.2f', $row['postQty']);
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
        $sums = array(0, 0, 0, 0, 0, 0);
        foreach ($data as $row) {
            for ($i=0; $i<6; $i++) {
                $sums[$i] += $row[$i+2];
            }
        }
        $sums[] = '';
        array_unshift($sums, null);
        array_unshift($sums, 'Total');

        return $sums;
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
            onchange="location=\'BatchBeforeAfterReport.php?btype=\'+$(\'#typef\').val()+\'&owner=\'+escape($(\'#ownerf\').val());">';
        echo $t_opts;
        echo '</select>';
        echo '&nbsp;&nbsp;&nbsp;&nbsp;';
        echo '<select id="ownerf" class="form-control"
            onchange="location=\'BatchBeforeAfterReport.php?btype=\'+$(\'#typef\').val()+\'&owner=\'+escape($(\'#ownerf\').val());">';
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

        echo '<form action="BatchBeforeAfterReport.php" method="get">';
        echo '<div class="row">';
        echo '<div class="col-sm-5">';
        echo '<select size="15" name=batchID class="form-control" required>';
        while ($batchW = $dbc->fetchRow($batchR)) {
            printf('<option value="%d">%s</option>',
                $batchW['batchID'],$batchW['batchName']);
        }
        echo '</select>';
        echo '</div>';
        echo '<div class="col-sm-7">';
        echo '<p><label>Excel ';
        echo '<input type="checkbox" name="excel" value="xls" /></label></p>';
        echo '<p><button type="submit" class="btn btn-default">Run Report</button></p>';
        echo '</div>';
        echo '</div>';

        echo '</form>';

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

