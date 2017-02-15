<?php
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class RawReceipt extends FannieReportPage 
{
    public $description = '[Raw Receipt] show a POS transaction\'s underlying database records.';
    protected $title = 'Raw Receipt';
    protected $header = 'Raw Receipt';
    protected $required_fields = array('date', 'trans');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));

        $dtrans = new DTransactionsModel($dbc);
        $columns = $dtrans->getColumns();
        foreach ($columns as $name => $info) {
            $this->report_headers[] = $name;
        }

        try {
            list($emp, $reg, $trans) = explode('-', $this->form->trans, 3);
            $date = $this->form->date;
            $table = DTransactionsModel::selectDtrans($date);
        } catch (Exception $ex) {
            return array();
        }

        $query = $dbc->prepare('
            SELECT *
            FROM ' . $table . '
            WHERE datetime BETWEEN ? AND ?
                AND emp_no = ?
                AND register_no = ?
                AND trans_no = ?
            ORDER BY trans_id
        ');
        $result = $dbc->execute($query, array($date . ' 00:00:00', $date . ' 23:59:59', $emp, $reg, $trans));
        $data = array();
        while ($w = $dbc->fetchRow($result)) {
            $record = array();
            foreach ($columns as $c => $info) {
                if (isset($w[$c])) {
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

