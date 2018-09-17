<?php
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class RawPTrans extends FannieReportPage 
{
    public $description = '[Raw PaycardTransactions] show a POS card transaction\'s underlying database records.';
    protected $title = 'Raw Paycrds';
    protected $header = 'Raw Paycards';
    protected $required_fields = array('date', 'trans');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));

        $ptrans = new PaycardTransactionsModel($dbc);
        $columns = $ptrans->getColumns();
        foreach ($columns as $name => $info) {
            $this->report_headers[] = $name;
        }

        try {
            list($emp, $reg, $trans) = explode('-', $this->form->trans, 3);
            $date = date('Ymd', strtotime($this->form->date));
        } catch (Exception $ex) {
            return array();
        }

        $query = $dbc->prepare('
            SELECT *
            FROM PaycardTransactions
            WHERE dateID=?
                AND empNo = ?
                AND registerNo = ?
                AND transNo = ?
            ORDER BY transID
        ');
        $card = false;
        $args = array($date, $emp, $reg, $trans);
        if (FormLib::get('card')) {
            $card = FormLib::get('card');
            $stamp = strtotime($this->form->date);
            $start = date('Ymd', mktime(0,0,0, date('n',$stamp), date('j',$stamp)-31, date('Y',$stamp)));
            $end = date('Ymd', mktime(0,0,0, date('n',$stamp), date('j',$stamp)+31, date('Y',$stamp)));
            $query = $dbc->prepare('
                SELECT *
                FROM PaycardTransactions
                WHERE dateID BETWEEN ? AND ?
                    AND PAN LIKE ?
                ORDER BY transID
            ');
            $args = array($start, $end, '%' . $card);
        }
        $result = $dbc->execute($query, $args);
        $data = array();
        while ($w = $dbc->fetchRow($result)) {
            $record = array();
            foreach ($columns as $c => $info) {
                if (!$card && $c == 'PAN' && is_numeric(substr($w[$c], -4))) {
                    $pan = substr($w[$c], -4);
                    $record[] = sprintf('<a href="RawPTrans.php?date=%s&trans=%s&card=%d">%s</a>',
                        $this->form->date, $this->form->trans, $pan, $w[$c]);
                } else if (isset($w[$c])) {
                    $record[] = $w[$c];
                } else {
                    $record[] = '';
                }
            }
            $data[] = $record;
        }

        return $data;
    }

    public function unitTest($phpunit)
    {
        $this->form = new COREPOS\common\mvc\ValueContainer();
        $this->form->date = date('Y-m-d');
        $this->form->trans = '1-1-1';
        $phpunit->assertInternalType('array', $this->fetch_report_data());
    }
}

FannieDispatch::conditionalExec();

