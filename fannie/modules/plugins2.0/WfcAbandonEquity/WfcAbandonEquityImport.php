<?php
include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class WfcAbandonEquityImport extends \COREPOS\Fannie\API\FannieUploadPage 
{
    public $page_set = 'Plugin :: WfcAbandonEquity';
    public $description = '[Import Abandoned Equity] to debit balances and mark members inactive.';

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

    protected $header = 'Abandoned Equity';
    protected $title = 'Abandoned Equity';

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

    private function suspendAccount($dbc, $custdata, $meminfo)
    {
        $now = date('Y-m-d H:i:s');
        $susp = new SuspensionsModel($dbc);
        $susp->cardno($custdata->CardNo());
        $susp->load();
        $susp->type('T');
        $susp->memtype1($custdata->memType());
        $susp->memtype2($custdata->Type());
        $susp->suspDate($now);
        $susp->discount($custdata->Discount());
        $susp->chargelimit($custdata->ChargeLimit());
        $susp->mailflag($meminfo->ads_OK());
        $susp->reasoncode(64);
        $susp->save();

        $suspHistory = new SuspensionHistoryModel($dbc);
        $suspHistory->username('abandon-import');
        $suspHistory->postdate($now);
        $suspHistory->cardno($custdata->CardNo());
        $suspHistory->reasoncode(64);
        $suspHistory->save();
    }

    private function termAccount($dbc, $card_no, $custdata, $meminfo)
    {
        $meminfo->ads_OK(0);
        $meminfo->save();

        $custdata->reset();
        $custdata->CardNo($card_no);
        foreach ($custdata->find() as $obj) {
            $obj->Type('TERM');
            $obj->memType(0);
            $obj->Discount(0);
            $obj->ChargeLimit(0);
            $obj->MemDiscountLimit(0);
            $obj->save();
        }
    }

    private function saveNote($dbc, $card_no, $note)
    {
        $now = date('Y-m-d H:i:s');
        $memNote = new MemberNotesModel($dbc);
        $memNote->cardno($card_no);
        $memNote->note($note);
        $memNote->stamp($now);
        $memNote->username('abandon-import');
        $memNote->save();
    }

    public function process_file($linedata, $indexes)
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $EMP_NO = $this->config->get('EMP_NO');
        $LANE_NO = $this->config->get('REGISTER_NO');
        $OFFSET_DEPT = $this->config->get('MISC_DEPT');

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $dtrans_table = $FANNIE_TRANS_DB.$dbc->sep().'dtransactions';
        $trans = DTrans::getTransNo($dbc, $EMP_NO, $LANE_NO);
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

            list($custdata, $meminfo) = $this->getMember($dbc, $card_no);
            $this->suspendAccount($dbc, $custdata, $meminfo);
            $this->termAccount($dbc, $card_no, $custdata, $meminfo);

            if (isset($data[$indexes['note']]) && !empty($data[$indexes['note']])) {
                $this->saveNote($dbc, $card_no, $data[$indexes['note']]);
            }

            $trans_id = 1;
            if ($a_amt > 0) {
                $record = DTrans::defaults();
                $record['register_no'] = $LANE_NO;
                $record['emp_no'] = $EMP_NO;
                $record['trans_no'] = $trans;
                $record['upc'] = $a_amt.'DP992';
                $record['description'] = 'Class A Equity';
                $record['trans_type'] = 'D';
                $record['department'] = 992;
                $record['unitPrice'] = -1*$a_amt;
                $record['total'] = -1*$a_amt;
                $record['regPrice'] = -1*$a_amt;
                $record['card_no'] = $card_no;
                $record['trans_id'] = $trans_id;
                $trans_id++;

                $info = DTrans::parameterize($record, 'datetime', $dbc->now());
                $prep = $dbc->prepare("INSERT INTO $dtrans_table ({$info['columnString']}) VALUES ({$info['valueString']})");
                $dbc->execute($prep, $info['arguments']);
            }
            if ($b_amt > 0) {
                $record = DTrans::defaults();
                $record['register_no'] = $LANE_NO;
                $record['emp_no'] = $EMP_NO;
                $record['trans_no'] = $trans;
                $record['upc'] = $b_amt.'DP991';
                $record['description'] = 'Class B Equity';
                $record['trans_type'] = 'D';
                $record['department'] = 991;
                $record['unitPrice'] = -1*$b_amt;
                $record['total'] = -1*$b_amt;
                $record['regPrice'] = -1*$b_amt;
                $record['card_no'] = $card_no;
                $record['trans_id'] = $trans_id;
                $trans_id++;

                $info = DTrans::parameterize($record, 'datetime', $dbc->now());
                $prep = $dbc->prepare("INSERT INTO $dtrans_table ({$info['columnString']}) VALUES ({$info['valueString']})");
                $dbc->execute($prep, $info['arguments']);
            }

            $record = DTrans::defaults();
            $record['register_no'] = $LANE_NO;
            $record['emp_no'] = $EMP_NO;
            $record['trans_no'] = $trans;
            $record['upc'] = $offset_amt.'DP'.$OFFSET_DEPT;
            $record['description'] = 'Abandon Equity';
            $record['trans_type'] = 'D';
            $record['department'] = $OFFSET_DEPT;
            $record['unitPrice'] = $offset_amt;
            $record['total'] = $offset_amt;
            $record['regPrice'] = $offset_amt;
            $record['card_no'] = $card_no;
            $record['trans_id'] = $trans_id;
            $trans_id++;

            $info = DTrans::parameterize($record, 'datetime', $dbc->now());
            $prep = $dbc->prepare("INSERT INTO $dtrans_table ({$info['columnString']}) VALUES ({$info['valueString']})");
            $dbc->execute($prep, $info['arguments']);

            $record = DTrans::defaults();
            $record['register_no'] = $LANE_NO;
            $record['emp_no'] = $EMP_NO;
            $record['trans_no'] = $trans;
            $record['upc'] = '0';
            $record['description'] = '63350';
            $record['trans_type'] = 'C';
            $record['trans_subtype'] = 'CM';
            $record['card_no'] = $card_no;
            $record['trans_id'] = $trans_id;

            $info = DTrans::parameterize($record, 'datetime', $dbc->now());
            $prep = $dbc->prepare("INSERT INTO $dtrans_table ({$info['columnString']}) VALUES ({$info['valueString']})");
            $dbc->execute($prep, $info['arguments']);

            $trans++;
        }

        return true;
    }

    function results_content()
    {
        return '<p>Import complete</p>';
    }

    function form_content()
    {
        return '<p>Upload abandoned equity spreadsheet</p>';
    }
}

FannieDispatch::conditionalExec();

