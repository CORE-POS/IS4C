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

use COREPOS\Fannie\API\jobs\QueueManager;

if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../../classlib2.0/FannieAPI.php');
}

class DepartmentMovementReport extends FannieReportPage 
{
    protected $report_cache = 'none';
    protected $title = "Fannie : Department Movement";
    protected $header = "Department Movement";

    protected $required_fields = array('date1', 'date2');

    public $description = '[Department Movement] lists sales for a department or group of departments over a given date range.';
    public $report_set = 'Movement Reports';
    public $themed = true;

    protected $new_tablesorter = true;
    protected $queueable = true;

    /**
      Lots of options on this report.
    */
    function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $date1 = $this->form->date1;
        $date2 = $this->form->date2;
        $deptStart = $this->form->tryGet('deptStart', '');
        $deptEnd = $this->form->tryGet('deptEnd', '');
        $deptMulti = $this->form->tryGet('departments', array());
        $buyer = $this->form->tryGet('buyer','');
        $groupby = $this->form->tryGet('sort','PLU');
        $store = $this->form->tryGet('store', 0);
        $superP = $dbc->prepare('SELECT dept_ID FROM superdepts WHERE superID=?');

        /**
          Build a WHERE condition for later.
          Superdepartment (buyer) takes precedence over
          department and negative values have special
          meaning

          Extra lookup to write condition in terms of
          transaction.department seems to result in
          better index utilization and faster queries
        */
        $filter_condition = 't.department BETWEEN ? AND ?';
        $args = array($deptStart,$deptEnd);
        if (count($deptMulti) > 0) {
            list($inStr, $args) = $dbc->safeInClause($deptMulti);
            $filter_condition = 't.department IN (' . $inStr . ') ';
        }
        if ($buyer !== "" && $buyer > 0) {
            $filter_condition .= ' AND s.superID=? ';
            $args[] = $buyer;
        } elseif ($buyer !== "" && $buyer == -1) {
            $filter_condition = "1=1";
            $args = array();
        } elseif ($buyer !== "" && $buyer == -2){
            $superR = $dbc->execute($superP, array(0));
            $filter_condition = 't.department NOT IN (0,';
            $args = array();
            while ($superW = $dbc->fetch_row($superR)) {
                $filter_condition .= '?,';
                $args[] = $superW['dept_ID'];
            }
            $filter_condition = substr($filter_condition, 0, strlen($filter_condition)-1) . ')';
            $filter_condition .= ' AND s.superID <> 0';
        }

        /**
         * Provide more WHERE conditions to filter irrelevant
         * transaction records, as a stop-gap until this is
         * handled more uniformly across the application.
         */
        $filter_transactions = "t.trans_status NOT IN ('D','X','Z')
            AND t.emp_no <> 9999
            AND t.register_no <> 99";
        $filter_transactions = DTrans::isValid() . ' AND ' . DTrans::isNotTesting();
        
        /**
          Select a summary table. For UPC results, per-unique-ring
          summary is needed. For date/dept/weekday results the
          per-department summary is fine (and a smaller table)
        */
        $dlog = DTransactionsModel::selectDlog($date1,$date2);
        $nabs = DTrans::memTypeIgnore($dbc);

        /**
          Build an appropriate query depending on the grouping option
        */
        $query = "";
        $superTable = ($buyer !== "" && $buyer > 0) ? 'superdepts' : 'MasterSuperDepts';
        $args[] = $date1.' 00:00:00';
        $args[] = $date2.' 23:59:59';
        $args[] = $store;
        switch($groupby) {
            case 'PLU':
                $query = "SELECT t.upc,
                      p.brand,
                      CASE WHEN t.description IS NULL THEN p.description ELSE t.description END as description, 
                      SUM(CASE WHEN trans_status IN('','0','R') THEN 1 WHEN trans_status='V' THEN -1 ELSE 0 END) as rings,"
                      . DTrans::sumQuantity('t')." as qty,
                      SUM(t.total) AS total,
                      d.dept_no,d.dept_name,s.superID,
                      v.vendorName AS distributor,
                      l.likeCode,
                      l.likeCodeDesc
                      FROM $dlog as t "
                      . DTrans::joinProducts()
                      . DTrans::joinDepartments()
                      . "LEFT JOIN $superTable AS s ON t.department = s.dept_ID
                      LEFT JOIN vendors AS v ON p.default_vendor_id=v.vendorID
                      LEFT JOIN upcLike AS u ON t.upc=u.upc
                      LEFT JOIN likeCodes AS l ON u.likeCode=l.likeCode
                      WHERE $filter_condition
                      AND t.trans_type IN ('I', 'D')
                      AND tdate BETWEEN ? AND ?
                      AND $filter_transactions
                      AND " . DTrans::isStoreID($store, 't') . "
                      AND t.memType NOT IN {$nabs}
                      GROUP BY t.upc,
                          p.brand,
                          description,
                          CASE WHEN t.trans_status = 'R' THEN 'Refund' ELSE 'Sale' END,
                          d.dept_no,d.dept_name,s.superID,
                          v.vendorName,
                          l.likeCode,
                          l.likeCodeDesc
                      ORDER BY SUM(t.total) DESC";
                break;
            case 'Department':
                $query =  "SELECT t.department,d.dept_name,"
                    . DTrans::sumQuantity('t')." as qty,
                    SUM(total) as Sales 
                    FROM $dlog as t "
                    . DTrans::joinDepartments()
                    . "LEFT JOIN $superTable AS s ON s.dept_ID = t.department 
                    WHERE $filter_condition
                    AND tdate BETWEEN ? AND ?
                    AND t.trans_type IN ('I', 'D')
                    AND $filter_transactions
                    AND " . DTrans::isStoreID($store, 't') . "
                    AND t.memType NOT IN {$nabs}
                    GROUP BY t.department,d.dept_name ORDER BY SUM(total) DESC";
                break;
            case 'Date':
                $query =  "SELECT year(tdate),month(tdate),day(tdate),"
                    . DTrans::sumQuantity('t')." as qty,
                    SUM(total) as Sales ,
                    MAX(" . $dbc->dayofweek('tdate') . ") AS dow
                    FROM $dlog as t "
                    . DTrans::joinDepartments()
                    . "LEFT JOIN $superTable AS s ON s.dept_ID = t.department
                    WHERE $filter_condition
                    AND tdate BETWEEN ? AND ?
                    AND t.trans_type IN ('I', 'D')
                    AND $filter_transactions
                    AND " . DTrans::isStoreID($store, 't') . "
                    AND t.memType NOT IN {$nabs}
                    GROUP BY year(tdate),month(tdate),day(tdate) 
                    ORDER BY year(tdate),month(tdate),day(tdate)";
                break;
            case 'Weekday':
                $cols = $dbc->dayofweek("tdate").",CASE 
                    WHEN ".$dbc->dayofweek("tdate")."=1 THEN 'Sun'
                    WHEN ".$dbc->dayofweek("tdate")."=2 THEN 'Mon'
                    WHEN ".$dbc->dayofweek("tdate")."=3 THEN 'Tue'
                    WHEN ".$dbc->dayofweek("tdate")."=4 THEN 'Wed'
                    WHEN ".$dbc->dayofweek("tdate")."=5 THEN 'Thu'
                    WHEN ".$dbc->dayofweek("tdate")."=6 THEN 'Fri'
                    WHEN ".$dbc->dayofweek("tdate")."=7 THEN 'Sat'
                    ELSE 'Err' END";
                $query =  "SELECT $cols,"
                    . DTrans::sumQuantity('t') . " as qty,
                    SUM(total) as Sales 
                    FROM $dlog as t "
                    . DTrans::joinDepartments()
                    . "LEFT JOIN $superTable AS s ON s.dept_ID = t.department 
                    WHERE $filter_condition
                    AND tdate BETWEEN ? AND ?
                    AND t.trans_type IN ('I', 'D')
                    AND $filter_transactions
                    AND " . DTrans::isStoreID($store, 't') . "
                    AND t.memType NOT IN {$nabs}
                    GROUP BY $cols
                    ORDER BY ".$dbc->dayofweek('tdate');
                break;
        }

        /**
          Copy the results into an array. Date requires a
          special case to combine year, month, and day into
          a single field
        */
        try {
            $prep = $dbc->prepare($query);
            $result = $dbc->execute($prep,$args);
        } catch (Exception $ex) {
            // MySQL 5.6 doesn't handle correctly
            return array();
        }
        try {
            $likeCodes = $this->form->lc;
            $likeCodes = array();
        } catch (Exception $ex) {
            $likeCodes = false;
        }
        $ret = array();
        $dateSum = 0;
        while ($row = $dbc->fetchRow($result)) {
            $record = array();
            if ($groupby == "Date") {
                $record[] = $row[1]."/".$row[2]."/".$row[0];
                $record[] = date('l', strtotime($record[0]));
                $record[] = sprintf('%.2f', $row[3]);
                $record[] = sprintf('%.2f', $row[4]);
                $record[] = 0; // percent placeholder
                $dateSum += $row[4];
            } elseif ($groupby == 'PLU') {
                if ($likeCodes !== false && $row['likeCode']) {
                    $lc = $row['likeCode'];
                    if (isset($likeCodes[$lc])) {
                        $likeCodes[$lc][3] += $row['rings'];
                        $likeCodes[$lc][4] += $row['qty'];
                        $likeCodes[$lc][5] += $row['total'];
                    } else {
                        $likeCodes[$lc] = array(
                            'LC' . $row['likeCode'],
                            '', // brand
                            $row['likeCodeDesc'],
                            $row['rings'],
                            $row['qty'],
                            $row['total'],
                            $row['dept_no'],
                            $row['dept_name'],
                            $row['superID'],
                            $row['distributor'] == null ? '' : $row['distributor'],
                        );
                    }
                    continue;
                } else {
                    $record[] = $row['upc'];
                    $record[] = $row['brand'] ? $row['brand'] : '';
                    $record[] = $row['description'];
                    $record[] = sprintf('%.2f', $row['rings']);
                    $record[] = sprintf('%.2f', $row['qty']);
                    $record[] = sprintf('%.2f', $row['total']);
                    $record[] = $row['dept_no'];
                    $record[] = $row['dept_name'];
                    $record[] = $row['superID'];
                    $record[] = $row['distributor'] == null ? '' : $row['distributor'];
                }
            } else {
                for($i=0;$i<$dbc->numFields($result);$i++) {
                    if (preg_match('/^\d+\.\d+$/', $row[$i])) {
                        $row[$i] = sprintf('%.2f', $row[$i]);
                    }
                    $record[] .= $row[$i];
                }
            }
            $ret[] = $record;
        }
        $likeCodes = $this->dekey_array($likeCodes);
        foreach ($likeCodes as $row) {
            $row[3] = sprintf('%.2f', $row[3]);
            $row[4] = sprintf('%.2f', $row[4]);
            $row[5] = sprintf('%.2f', $row[5]);
            $ret[] = $row;
        }
        if ($groupby == 'Date') {
            for ($i=0; $i<count($ret); $i++) {
                $ret[$i][4] = sprintf('%.2f', $ret[$i][3] / $dateSum * 100);
            }
        }

        return $ret;
    }
    
    /**
      Sum the quantity and total columns for a footer,
      but also set up headers and sorting.

      The number of columns varies depending on which
      data grouping the user selected. 
    */
    function calculate_footers($data)
    {
        // no data; don't bother
        if (empty($data)) {
            return array();
        }

        /**
          Use the width of the first record to determine
          how the data is grouped
        */
        switch(count($data[0])) {
            case 10:
                return $this->upcFooter($data);
            case 5:
                $this->nonUpcHeaders();
                $ret = $this->nonUpcFooter($data);
                $ret[] = '';
                return $ret;
            case 4:
                /**
                  The Department and Weekday datasets are both four
                  columns wide so I have to resort to form parameters
                */
                $this->nonUpcHeaders();
                return $this->nonUpcFooter($data);
        }
    }

    private function upcFooter($data)
    {
        $this->report_headers = array('UPC','Brand','Description','Rings','Qty','$',
            'Dept#','Department','Super#','Vendor');
        $this->sort_column = 4;
        $this->sort_direction = 1;
        $sumQty = 0.0;
        $sumSales = 0.0;
        $sumRings = 0.0;
        foreach($data as $row) {
            $sumRings += $row[3];
            $sumQty += $row[4];
            $sumSales += $row[5];
        }

        return array('Total',null,null,$sumRings,$sumQty,$sumSales,'',null,null,null);
    }

    private function nonUpcHeaders()
    {
        if ($this->form->tryGet('sort')=='Weekday') {
            $this->report_headers = array('Day','Day','Qty','$');
            $this->sort_column = 0;
            $this->sort_direction = 0;
        } elseif ($this->form->tryGet('sort')=='Date') {
            $this->report_headers = array('Date','Day','Qty','$', '%');
            $this->sort_column = 0;
            $this->sort_direction = 0;
        } else {
            $this->report_headers = array('Dept#','Department','Qty','$');
            $this->sort_column = 3;
            $this->sort_direction = 1;
        }
    }

    private function nonUpcFooter($data)
    {
        $sumQty = 0.0;
        $sumSales = 0.0;
        foreach($data as $row) {
            $sumQty += $row[2];
            $sumSales += $row[3];
        }

        return array('Total',null,$sumQty,$sumSales);
    }

    function report_description_content()
    {
        $ret = array();
        $ret[] = "Summed by ".$this->form->tryGet('sort','');
        $buyer = $this->form->tryGet('buyer','');
        if ($buyer === '0') {
            $ret[] = "Department ".$this->form->tryGet('deptStart','').' to '.$this->form->tryGet('deptEnd','');
        }

        return $ret;
    }

    function form_content()
    {
        $queue = FannieAuth::hasEmail(FannieAuth::getUID()) && QueueManager::available() ? '' : 'disabled';
        ob_start();
?>
<form method = "get" action="DepartmentMovementReport.php" class="form-horizontal">
<div class="row">
    <div class="col-sm-6">
        <?php echo FormLib::standardDepartmentFields('buyer', 'departments', 'deptStart', 'deptEnd'); ?>
        <div class="form-group">
            <label class="col-sm-4 control-label">Sum movement by?</label>
            <div class="col-sm-8">
                <select name="sort" class="form-control"
                    onchange="if (this.value=='PLU') $('#rollup').show(); else $('#rollup').hide();">
                    <option>PLU</option>
                    <option>Date</option>
                    <option>Department</option>
                <option>Weekday</option>
                </select> 
                <label class="control-label" id="rollup">Rollup Likecodes
                    <input type=checkbox name=lc id=lc value=1>
                </label>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-sm-4">Save to Excel
                <input type=checkbox name=excel id=excel value=1>
            </label>
            <label class="col-sm-4 control-label">Store</label>
            <div class="col-sm-4">
                <?php $ret=FormLib::storePicker();echo $ret['html']; ?>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-sm-4"> Email it to me
                <input type=checkbox <?php echo $queue; ?> name=queued value=1>
            </label>
        </div>
    </div>
    <?php echo FormLib::standardDateFields(); ?>
</div>
    <p>
        <button type=submit name=submit value="Submit" class="btn btn-default btn-core">Submit</button>
        <button type=reset name=reset class="btn btn-default btn-reset"
            onclick="$('#super-id').val('').trigger('change');">Start Over</button>
    </p>
</form>
<?php
        $this->addOnloadCommand("\$('#subdepts').closest('.form-group').hide();");

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>View sales for given departments by date.
            The <em>Buyer/Dept</em> setting will be used if specified,
            otherwise the <em>Department Start</em> to <em>Department
            End</em> range will be used. The <em>Sum movement by</em>
            setting has the largest impact on results.
            <ul>
                <li><em>PLU</em> shows a row for each item. Sales totals
                are for the entire date range.</li>
                <li><em>Date</em> show a row for each days. Sales totals
                are all sales in the department(s) that day.</li>
                <li><em>Department</em> shows a row for each POS department.
                Sales totals are all sales in that particular department
                for the entire date range.</li>
                <li><em>Weekday</em> will show at most seven rows for
                Monday, Tuesday, etc. Sales totals are all sales in
                the department(s) for Mondays in the date range, Tuesdays
                in the date range, etc.</li>
            </ul>';
    }
}

FannieDispatch::conditionalExec();

