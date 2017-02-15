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

if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../../classlib2.0/FannieAPI.php');
}

class LikeCodeMovementReport extends FannieReportPage 
{
    protected $report_cache = 'none';
    protected $title = "Fannie : Like Code Movement";
    protected $header = "Like Code Movement";

    protected $required_fields = array('date1', 'date2', 'start', 'end');

    public $description = '[Like Code Movement] lists sales for likecoded items over a given date range.';
    public $report_set = 'Movement Reports';
    protected $report_headers = array('LC#', 'Like Code', 'Department', 'Qty', '$');

    /**
      Lots of options on this report.
    */
    function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $date1 = $this->form->date1;
        $date2 = $this->form->date2;
        $dlog = DTransactionsModel::selectDlog($date1, $date2);
        $lc1 = $this->form->start;
        $lc2 = $this->form->end;
        $store = FormLib::get('store', 0);

        $lcP = $dbc->prepare('SELECT upc FROM upcLike WHERE likeCode BETWEEN ? AND ?');
        $lcR = $dbc->execute($lcP, array($lc1, $lc2));
        $args = array($lc1, $lc2, $date1 . ' 00:00:00', $date2 . ' 23:59:59');
        $inStr = '';
        while ($lcW = $dbc->fetchRow($lcR)) {
            $args[] = $lcW['upc'];
            $inStr .= '?,';
        }
        if ($inStr === '') {
            $inStr = '-999';
        } else {
            $inStr = substr($inStr, 0, strlen($inStr)-1);
        }

        $query = "select
              u.likeCode,l.likeCodeDesc,max(p.department), "
              . DTrans::sumQuantity('t')." as qty,
              sum(t.total) 
              FROM $dlog as t 
                inner join upcLike as u on u.upc = t.upc
                left join likeCodes as l on u.likeCode = l.likeCode  "
                . DTrans::joinProducts() . "
              where u.likeCode between ? AND ?
                  AND t.trans_type = 'I'
                  AND t.tdate BETWEEN ? AND ?
                  AND t.upc IN ($inStr)
                  AND " . DTrans::isStoreID($store, 't') . "
              group by u.likeCode,l.likeCodeDesc";
        $args[] = $store;
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = $this->rowToRecord($row);
        }

        return $data;
    }

    private function rowToRecord($row)
    {
        return array(
            $row[0],
            $row[1],
            $row[2],
            sprintf('%.2f', $row[3]),
            sprintf('%.2f', $row[4]),
        );
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

        $sums = array(0, 0);
        foreach ($data as $row) {
            $sums[0] += $row[3];
            $sums[1] += $row[4];
        }

        return array('Total', null, null, $sums[0], $sums[1]);
    }

    function form_content()
    {
        ob_start();
        $codes = new LikeCodesModel($this->connection);
        $opts = $codes->toOptions();
?>
<form method = "get" action="LikeCodeMovementReport.php" class="form-horizontal">
<div class="row">
    <div class="col-sm-6">
        <div class="form-group">
            <label class="col-sm-1">Start</label>
            <div class="col-sm-8">
                <select onchange="$('#lcstart').val(this.value);" class="form-control"
                    id="startselect">
                <?php echo $opts; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <input type="text" class="form-control" name="start" value="1" 
                    id="lcstart" onchange="$('#startselect').val(this.value);" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-1">End</label>
            <div class="col-sm-8">
                <select onchange="$('#lcend').val(this.value);" class="form-control"
                    id="endselect">
                <?php echo $opts; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <input type="text" class="form-control" name="end" value="1" 
                    id="lcend" onchange="$('#endselect').val(this.value);" />
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

    public function unitTest($phpunit)
    {
        $data = array(1, 'test', 1, 1, 1);
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
        $phpunit->assertInternalType('array', $this->calculate_footers(array($data)));
    }
}

FannieDispatch::conditionalExec();

