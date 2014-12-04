<?php
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class CCReport extends FannieReportPage
{
    public $description = '[Integrated Card Detail] lists integrated transactions for a day.';
    public $themed = true;
    protected $title = 'Integrated Transactions';
    protected $header = 'Integrated Transactions';
    protected $report_headers = array('Date &amp; Time', 'Card', 'Amount', 'Response', 'POS Receipt');

    public function report_description_content()
    {
        global $FANNIE_URL;
        $ret = array(''); // spacer line
        if ($this->report_format == 'html') {
            $ret[] = $this->form_content();
            $this->addScript($FANNIE_URL . 'src/javascript/jquery.js');
            $this->addScript($FANNIE_URL . 'src/javascript/jquery-ui.js');
            $this->addCssFile($FANNIE_URL . 'src/javascript/jquery-ui.css');
        }

        return $ret;
    }

    public function form_content()
    {
        $this->add_onload_command("\$('input:first').datepicker({dateFormat:'yy-mm-dd',changeYear:true});\n");
        return "<form action=index.php method=get>
            <b>Date</b>: <input type=text name=date /> <input type=submit value=Submit />
            </form>";
    }

    public function fetch_report_data()
    {
        global $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_TRANS_DB);

        $date = FormLib::getDate('date', date('Y-m-d', strtotime('yesterday')));

        $seconds = strtotime($date);
        $start = date('Y-m-d 00:00:00',$seconds);
        $end = date('Y-m-d 23:59:59',$seconds);
        $query = $dbc->prepare_statement("
            SELECT q.datetime,
                q.laneno,
                q.cashierno,
                q.transno,
                q.amount,
                q.PAN, 
                year(q.datetime) AS year,
                day(q.datetime) AS day,
                month(q.datetime) AS month,
                r.xResultMessage
            FROM efsnetRequest q LEFT JOIN efsnetResponse r
            on r.date=q.date and r.cashierNo=q.cashierNo and 
            r.transNo=q.transNo and r.laneNo=q.laneNo
            and r.transID=q.transID
            left join efsnetRequestMod m
            on m.date = q.date and m.cashierNo=q.cashierNo and
            m.transNo=q.transNo and m.laneNo=q.laneNo
            and m.transID=q.transID
            where q.datetime between ? AND ?
            and q.laneNo <> 99 and q.cashierNo <> 9999
            and m.transID is null
            order by q.datetime,q.laneNo,q.transNo,q.cashierNo");
        $result = $dbc->exec_statement($query,array($start,$end));

        $sum = 0;
        $htable = array();
        $data = array();
        while($row = $dbc->fetch_row($result)){
            $record = array(
                $row['datetime'],
                $row['PAN'],
                sprintf('%.2f', $row['amount']),
                $row['xResultMessage'],
                sprintf('<a href="%sadmin/LookupReceipt/RenderReceiptPage.php?month=%d&year=%d&day=%d&receipt=%d-%d-%d">POS Receipt</a>',
                    $FANNIE_URL, $row['month'], $row['year'], $row['day'],
                    $row['cashierno'], $row['laneno'], $row['transno']),
            );
            if (isset($htable[$row['amount']."+".$row['PAN']])) {
                $record['meta'] = FannieReportPage::META_COLOR;
                $record['meta_background'] = '#ffffcc';
            }
            if (strstr($row[9],"APPROVED") || $row[9] == "" || strstr($row[9],"PENDING")){
                $sum += $row[4];
                $htable[$row['amount']."+".$row['PAN']] = 1;
            }
            $data[] = $record;
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();

