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

class LikeCodeDailyMovementReport extends FannieReportPage 
{
    protected $report_cache = 'none';
    protected $title = "Fannie : Like Code Daily Movement";
    protected $header = "Like Code Daily Movement";

    protected $queueable = true;

    protected $required_fields = array('date1', 'date2', 'start');

    public $description = '[Like Code Daily Movement] lists sales by day for a likecode over a given date range.';
    public $report_set = 'Movement Reports';
    protected $report_headers = array('Date', 'LC#', 'Like Code', 'Qty', '$');

    function preprocess()
    {
        $ret = parent::preprocess();
        // custom: needs graphing JS/CSS
        if ($this->content_function == 'report_content' && $this->report_format == 'html') {
            $this->addScript('../../src/javascript/Chart.min.js');
            $this->addScript('../../src/javascript/CoreChart.js');
        }

        return $ret;
    }

    public function report_content() 
    {
        $default = parent::report_content();

        if ($this->report_format == 'html') {
            $default .= '<div class="col-sm-10 col-sm-offset-1"><canvas id="chartCanvas"></canvas></div>';

            $this->addOnloadCommand('showGraph()');
        }

        return $default;
    }

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
        try {
            $store = $this->form->store;
        } catch (Exception $ex) {
            $store = 0;
        }

        $lcP = $dbc->prepare('SELECT upc FROM upcLike WHERE likeCode BETWEEN ? AND ?');
        $lcR = $dbc->execute($lcP, array($lc1, $lc1));
        $args = array($lc1, $lc1, $date1 . ' 00:00:00', $date2 . ' 23:59:59');
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

        $nabs = DTrans::memTypeIgnore($dbc);
        $query = "select
                YEAR(tdate), month(tdate), DAY(tdate),
              u.likeCode,l.likeCodeDesc,"
              . DTrans::sumQuantity('t')." as qty,
              sum(t.total) AS ttl
              FROM $dlog as t 
                inner join upcLike as u on u.upc = t.upc
                left join likeCodes as l on u.likeCode = l.likeCode  "
                . DTrans::joinProducts() . "
              where u.likeCode between ? AND ?
                  AND t.trans_type = 'I'
                  AND t.tdate BETWEEN ? AND ?
                  AND t.upc IN ($inStr)
                  AND " . DTrans::isStoreID($store, 't') . "
                  AND t.memType NOT IN {$nabs}
              group by YEAR(tdate), MONTH(tdate), DAY(tdate), u.likeCode,l.likeCodeDesc";
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
            date('Y-m-d', mktime(0, 0, 0, $row[1], $row[2], $row[0])),
            $row['likeCode'],
            $row['likeCodeDesc'],
            sprintf('%.2f', $row['qty']),
            sprintf('%.2f', $row['ttl']),
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

    public function javascriptContent()
    {
        if ($this->report_format != 'html') {
            return;
        }

        ob_start();
        ?>
function showGraph() {
    var xLabels = $('td.reportColumn0').toArray().map(x => x.innerHTML.trim());
    var yData = $('td.reportColumn3').toArray().map(x => Number(x.innerHTML.trim()));
    CoreChart.lineChart('chartCanvas', xLabels, [yData], ["Daily Qty Sold"]);
}
        <?php
        return ob_get_clean();
    }

    function form_content()
    {
        ob_start();
        $codes = new LikeCodesModel($this->connection);
        $opts = $codes->toOptions();
?>
<form method = "get" action="LikeCodeDailyMovementReport.php" class="form-horizontal">
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
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <label><input type="checkbox" name="queued" value="1" /> Email it to me</label>
    </p>
</form>
<?php
        $this->addOnloadCommand("\$('#subdepts').closest('.form-group').hide();");

        return ob_get_clean();
    }

}

FannieDispatch::conditionalExec();

