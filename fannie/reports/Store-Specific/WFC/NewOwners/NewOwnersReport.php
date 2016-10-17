<?php
include('../../../../config.php');
if (!class_exists('FannieAPI.php')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

class NewOwnersReport extends FannieReportPage
{
    protected $report_headers = array('Date', '# of New Owners');
    protected $required_fields = array('date1', 'date2');
    protected $title = 'New Owners Report';
    protected $header = 'New Owners Report';

    public function preprocess()
    {
        $this->addScript($this->config->get('URL').'src/javascript/d3.js/d3.v3.min.js');
        $this->addScript($this->config->get('URL') . 'src/javascript/d3.js/charts/singleline/singleline.js');
        $this->addCssFile($this->config->get('URL') . 'src/javascript/d3.js/charts/singleline/singleline.css');

        return parent::preprocess();
    }

    public function report_content() 
    {
        $default = parent::report_content();

        if ($this->report_format == 'html') {
            $default .= '<div id="chartDiv"></div>';
            $this->add_onload_command('showGraph()');
        }

        return $default;
    }

    public function javascriptContent()
    {
        if ($this->report_format != 'html') {
            return;
        }

        ob_start();
        ?>
function showGraph() {
    var ymin = 999999999;
    var ymax = 0;

    var ydata = Array();
    $('td.reportColumn1').each(function(){
        var y = Number($(this).html());
        ydata.push(y);
        if (y > ymax) {
            ymax = y;
        }
        if (y < ymin) {
            ymin = y;
        }
    });

    var xmin = new Date();
    var xmax = new Date(1900, 01, 01); 
    var xdata = Array();
    $('td.reportColumn0').each(function(){
        var x = new Date( Date.parse($(this).html().trim()) );
        xdata.push(x);
        if (x > xmax) {
            xmax = x;
        }
        if (x < xmin) {
            xmin = x;
        }
    });

    var data = Array();
    for (var i=0; i < xdata.length; i++) {
        data.push(Array(xdata[i], ydata[i]));
    }

    singleline(data, Array(xmin, xmax), Array(ymin, ymax), '#chartDiv');
}
        <?php
        return ob_get_clean();
    }

    public function fetch_report_data()
    {
        try {
            $date1 = $this->form->date1;
            $date2 = $this->form->date2;
        } catch (Exception $ex) {
            return array();
        }
        
        $dlog = DTransactionsModel::selectDlog($date1, $date2);
        $union = (strtotime($date2) >= strtotime(date('Y-m-d')) && strpos($dlog, 'dlog_15') === false);

        $query = "
            SELECT YEAR(tdate),
                MONTH(tdate),
                DAY(tdate),
                SUM(total)
            FROM __DLOG__
            WHERE tdate BETWEEN ? AND ?
                AND department=992
                AND register_no <> 30
            GROUP BY YEAR(tdate),
                MONTH(tdate),
                DAY(tdate)";
        if ($union) {
            $realQuery = str_replace('__DLOG__', $dlog, $query)
                . ' UNION ALL '
                . str_replace('__DLOG__', $this->config->get('TRANS_DB') . $this->connection->sep() . 'dlog', $query);
        } else {
            $realQuery = str_replace('__DLOG__', $dlog, $query);
        }

        $prep = $this->connection->prepare($realQuery);
        $args = array($date1 . ' 00:00:00', $date2 . ' 23:59:59');
        if ($union) {
            $args[] = $date1 . ' 00:00:00';
            $args[] = $date2 . ' 23:59:59';
        }
        $data = array();
        $res = $this->connection->execute($prep, $args);
        while ($row = $this->connection->fetchRow($res)) {
            $ts = mktime(0,0,0,$row[1],$row[2],$row[0]);
            $data[] = array(
                date('Y-m-d', $ts),
                sprintf('%d', $row[3]/20),
            );
        }

        return $data;
    }

    function calculate_footers($data)
    {
        $sum = array_reduce($data, function($c, $i) { return $c + $i[1]; }, 0);
        return array('Total', $sum);
    }

    public function form_content()
    {
        $dates = FormLib::standardDateFields();
        return <<<HTML
<form method="get">
    {$dates}
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Get Report</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

