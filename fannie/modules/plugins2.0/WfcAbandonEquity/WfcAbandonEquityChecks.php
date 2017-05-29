<?php
include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class WfcAbandonEquityChecks extends \COREPOS\Fannie\API\FannieUploadPage 
{
    public $page_set = 'Plugin :: WfcAbandonEquity';
    public $description = '[Abandoned Equity Checks] to refund equity as checks';

    protected $preview_opts = array(
        'card_no' => array(
            'display_name' => 'Mem#',
            'default' => 0,
            'required' => true
        ),
        'classA' => array(
            'display_name' => 'Class A',
            'default' => 1,
            'required' => true
        ),
        'classB' => array(
            'display_name' => 'Class B',
            'default' => 2,
            'required' => true
        ),
        'note' => array(
            'display_name' => 'Note',
            'default' => 4,
        )
    );

    protected $header = 'Refund Equity Checks';
    protected $title = 'Refund Equity Checks';

    private function getMember($dbc, $card_no)
    {
        $custdata = new CustdataModel($dbc);
        $custdata->CardNo($card_no);
        $custdata->personNum(1);
        $custdata->load();

        $meminfo = new MeminfoModel($dbc);
        $meminfo->card_no($card_no);
        $meminfo->load();

        return array($custdata, $meminfo);
    }

    public function process_file($linedata, $indexes)
    {
        $dbc = $this->connection;

        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->SetMargins(6.35, 6.35, 6.35); // quarter-inch margins
        $pdf->SetAutoPageBreak(false);

        $plugin_settings = $this->config->get('PLUGIN_SETTINGS');
        $checkDB = $plugin_settings['GiveUsMoneyDB'];
        $numberP = $dbc->prepare("
            SELECT checkNumber
            FROM " . $checkDB . $dbc->sep() . "GumPayoffs
            WHERE alternateKey=?");

        $checkCount = 0;
        foreach ($linedata as $data) {

            if (!isset($data[$indexes['card_no']])) {
                continue;
            } elseif (!is_numeric($data[$indexes['card_no']])) {
                continue;
            }

            $card_no = trim($data[$indexes['card_no']]);
            $a_amt = trim($data[$indexes['classA']], '$ ');
            $b_amt = trim($data[$indexes['classB']], '$ ');
            $offset_amt = $a_amt + $b_amt;
            if ($offset_amt == 0) {
                continue;
            }

            $dbc->selectDB($this->config->get('OP_DB'));
            list($custdata, $meminfo) = $this->getMember($dbc, $card_no);
            $payoffKey = 'eqr' . $card_no;
            $number = $dbc->getValue($numberP, array($payoffKey));
            if ($number === false) {
                $number = GumLib::allocateCheck($custdata, false, 'EQ REFUND', 'eqr' . $card_no);
            }
            if ($checkCount % 3 == 0) {
                $pdf->AddPage();
            }
            $check = new GumCheckTemplate($custdata, $meminfo, $offset_amt, 'Equity Refund', $number);
            $pos = ($checkCount % 3) + 2;
            $check->setPosition($pos);
            $check->renderAsPDF($pdf);
            $checkCount++;
        }

        $pdf->Output('Equity Refunds.pdf', 'I');

        return true;
    }

    function results_content()
    {
        return '<p>Import complete</p>';
    }

    function form_content()
    {
        return '<p>Upload equity refund spreadsheet</p>';
    }
}

FannieDispatch::conditionalExec();

