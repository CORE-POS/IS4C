<?php
include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

$EMP_NO = 1001;
$LANE_NO = 30;
$OFFSET_DEPT = 703;

class WfcAbandonEquityImport extends FannieUploadPage 
{

    protected $preview_opts = array(
        'card_no' => array(
            'name' => 'card_no',
            'display_name' => 'Mem#',
            'default' => 0,
            'required' => true
        ),
        'classA' => array(
            'name' => 'classA',
            'display_name' => 'Class A',
            'default' => 1,
            'required' => true
        ),
        'classB' => array(
            'name' => 'classB',
            'display_name' => 'Class B',
            'default' => 2,
            'required' => true
        ),
        'note' => array(
            'name' => 'note',
            'display_name' => 'Note',
            'default' => 4,
            'required' => false
        )
    );

    protected $header = 'Abandoned Equity';
    protected $title = 'Abandoned Equity';

    function process_file($linedata)
    {
        global $OFFSET_DEPT, $FANNIE_OP_DB, $FANNIE_TRANS_DB, $EMP_NO, $LANE_NO;
        $card_no = $this->get_column_index('card_no');
        $classA = $this->get_column_index('classA');
        $classB = $this->get_column_index('classB');
        $note = $this->get_column_index('note');

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $dtrans_table = $FANNIE_TRANS_DB.$dbc->sep().'dtransactions';
        $prep = $dbc->prepare('SELECT MAX(trans_no) as tn FROM '.$dtrans_table.' 
                            WHERE emp_no=? AND register_no=?');
        $result = $dbc->execute($prep, array($EMP_NO, $LANE_NO));
        $trans = 1;
        if ($dbc->num_rows($result) > 0) {
            $row = $dbc->fetch_row($result);
            if ($row['tn'] != '') {
                $trans = $row['tn'] + 1;
            }
        }

        foreach($linedata as $data) {

            if (!isset($data[$card_no])) {
                continue;
            } elseif (!is_numeric($data[$card_no])) {
                continue;
            }

            $cn = trim($data[$card_no]);
            $a_amt = trim($data[$classA], '$ ');
            $b_amt = trim($data[$classB], '$ ');
            $offset_amt = $a_amt + $b_amt;
            if ($offset_amt == 0) {
                continue;
            }

            $now = date('Y-m-d H:i:s');

            $custdata = new CustdataModel($dbc);
            $custdata->CardNo($cn);
            $custdata->personNum(1);
            $custdata->load();

            $meminfo = new MeminfoModel($dbc);
            $meminfo->card_no($cn);
            $meminfo->load();

            $susp = new SuspensionsModel($dbc);
            $susp->cardno($cn);
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
            $suspHistory->cardno($cn);
            $suspHistory->reasoncode(64);
            $suspHistory->save();

            $meminfo->ads_OK(0);
            $meminfo->save();

            $custdata->reset();
            $custdata->CardNo($cn);
            foreach($custdata->find() as $obj) {
                $obj->Type('TERM');
                $obj->memType(0);
                $obj->Discount(0);
                $obj->ChargeLimit(0);
                $obj->MemDiscountLimit(0);
                $obj->save();
            }

            if (isset($data[$note]) && !empty($data[$note])) {
                $memNote = new MemberNotesModel($dbc);
                $memNote->cardno($cn);
                $memNote->note($data[$note]);
                $memNote->stamp($now);
                $memNote->username('abandon-import');
                $memNote->save();
            }

            $trans_id = 1;
            if ($a_amt > 0) {
                $record = DTrans::$DEFAULTS;
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
                $record['card_no'] = $cn;
                $record['trans_id'] = $trans_id;
                $trans_id++;

                $info = DTrans::parameterize($record, 'datetime', $dbc->now());
                $prep = $dbc->prepare("INSERT INTO $dtrans_table ({$info['columnString']}) VALUES ({$info['valueString']})");
                $dbc->execute($prep, $info['arguments']);
            }
            if ($b_amt > 0) {
                $record = DTrans::$DEFAULTS;
                $record['register_no'] = $LANE_NO;
                $record['emp_no'] = $EMP_NO;
                $record['trans_no'] = $trans;
                $record['upc'] = $a_amt.'DP991';
                $record['description'] = 'Class B Equity';
                $record['trans_type'] = 'D';
                $record['department'] = 991;
                $record['unitPrice'] = -1*$b_amt;
                $record['total'] = -1*$b_amt;
                $record['regPrice'] = -1*$b_amt;
                $record['card_no'] = $cn;
                $record['trans_id'] = $trans_id;
                $trans_id++;

                $info = DTrans::parameterize($record, 'datetime', $dbc->now());
                $prep = $dbc->prepare("INSERT INTO $dtrans_table ({$info['columnString']}) VALUES ({$info['valueString']})");
                $dbc->execute($prep, $info['arguments']);
            }

            $record = DTrans::$DEFAULTS;
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
            $record['card_no'] = $cn;
            $record['trans_id'] = $trans_id;
            $trans_id++;

            $info = DTrans::parameterize($record, 'datetime', $dbc->now());
            $prep = $dbc->prepare("INSERT INTO $dtrans_table ({$info['columnString']}) VALUES ({$info['valueString']})");
            $dbc->execute($prep, $info['arguments']);

            $record = DTrans::$DEFAULTS;
            $record['register_no'] = $LANE_NO;
            $record['emp_no'] = $EMP_NO;
            $record['trans_no'] = $trans;
            $record['upc'] = '0';
            $record['description'] = '63350';
            $record['trans_type'] = 'C';
            $record['trans_subtype'] = 'CM';
            $record['card_no'] = $cn;
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
        return 'Import complete';
    }

    function form_content()
    {
        return 'Upload abandoned equity spreadsheet';
    }
}

FannieDispatch::conditionalExec();

