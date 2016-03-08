<?php
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class CCReport extends FannieReportPage
{
    public $description = '[Integrated Card Detail] lists integrated transactions for a day.';
    public $themed = true;
    public $report_set = 'Tenders';
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
        return "<form action=CCReport.php method=get>
            <b>Date</b>: <input type=text name=date /> <input type=submit value=Submit />
            </form>";
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));

        try {
            $date = $this->form->date;
        } catch (Exception $ex) {
            $date = date('Y-m-d', strtotime('yesterday'));
        }

        $seconds = strtotime($date);
        $start = date('Y-m-d 00:00:00',$seconds);
        $end = date('Y-m-d 23:59:59',$seconds);
        $query = $dbc->prepare("
            SELECT requestDatetime AS datetime,
                registerNo AS laneno,
                empNo AS cashierno,
                transNo AS transno,
                amount,
                PAN, 
                year(requestDatetime) AS year,
                day(requestDatetime) AS day,
                month(requestDatetime) AS month,
                xResultMessage
            FROM PaycardTransactions
            WHERE requestDatetime between ? AND ?
                and registerNo <> 99 and empNo <> 9999
                and transID is not null
            order by requestDatetime,registerNo,transNo,empNo");
        $result = $dbc->execute($query,array($start,$end));

        $sum = 0;
        $htable = array();
        $data = array();
        while ($row = $dbc->fetch_row($result)) {
            $data[] = $this->rowToRecord($row, $htable, $sum);
        }

        return $data;
    }

    private function rowToRecord($row, &$htable, &$sum)
    {
        $record = array(
            $row['datetime'],
            $row['PAN'],
            sprintf('%.2f', $row['amount']),
            $row['xResultMessage'],
            sprintf('<a href="%sadmin/LookupReceipt/RenderReceiptPage.php?month=%d&year=%d&day=%d&receipt=%d-%d-%d">POS Receipt</a>',
                $this->config->get('URL'), $row['month'], $row['year'], $row['day'],
                $row['cashierno'], $row['laneno'], $row['transno']),
        );
        if (isset($htable[$row['amount']."+".$row['PAN']])) {
            $record['meta'] = FannieReportPage::META_COLOR;
            $record['meta_background'] = '#ffffcc';
        }
        if (strstr($row['xResultMessage'],"APPROVED") || $row['xResultMessage'] == "" || strstr($row['xResultMessage'],"PENDING")){
            $sum += $row['amount'];
            $htable[$row['amount']."+".$row['PAN']] = 1;
        }

        return $record;
    }

    public function helpContent()
    {
        return '<p>
            Lists information about integrated card transactions
            for a given date range. The <strong>Integrated Card
            Report</strong> is newer and probably better but
            this has not been retired yet.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $data = array('datetime'=>'2000-01-01', 'PAN'=>'1111', 'amount'=>1,
            'xResultMessage'=>'APPROVED', 'month'=>1, 'day'=>1, 'year'=>2000,
            'cashierno'=>1,'laneno'=>1,'transno'=>1);
        $htable = array('1+1111'=>1);
        $sum = 0;
        $phpunit->assertInternalType('array', $this->rowToRecord($data, $htable, $sum));
    }
}

FannieDispatch::conditionalExec();

