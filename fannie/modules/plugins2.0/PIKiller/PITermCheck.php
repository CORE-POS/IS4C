<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'/classlib2.0/FannieAPI.php');
}

class PITermCheck extends FannieRESTfulPage
{
    protected function get_id_handler()
    {
        if (!class_exists('GumCheckTemplate')) {
            echo 'Missing check template; enable plugin!';
            return false;
        }

        $dbc = $this->connection;

        $status = $dbc->prepare('SELECT Type FROM custdata WHERE CardNo=? AND personNum=1');
        $status = $dbc->getValue($status, array($this->id));
        if ($status != 'INACT2') {
            echo 'Can only terminate TERMPENDING owners';
            return false;
        }

        $equity = $dbc->prepare('SELECT balance FROM ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'equity_live_balance WHERE memnum=?');
        $equity = $dbc->getValue($equity, array($this->id));

        $classA = $equity < 20 ? $equity : 20;
        $classB = $equity < 20 ? 0 : $equity - 20;

        /*****************
         * Terminate account related tables
         ****************/
        $susp = new SuspensionsModel($dbc);
        $susp->cardno($this->id);
        $susp->type('T');
        $susp->reasoncode(64);
        $susp->save();

        $history = new SuspensionHistoryModel($dbc);
        $history->uesrname($this->current_user);
        $history->postdate(date('Y-m-d H:i:s'));
        $history->cardno($this->id);
        $history->reasoncode(64);
        $history->save();

        $custP = $dbc->prepare("UPDATE custdata SET type='TERM', memType=0, Discount=0, ChargeLimit=0, MemDiscountLimit=0 WHERE CardNo=?");
        $dbc->execute($custP, array($this->id));

        $note = new MemberNotesModel($dbc);
        $note->cardno($this->id);
        $note->note('Equity termination');
        $note->stamp(date('Y-m-d H:i:s'));
        $note->username($this->current_user);
        $note->save();

        /*****************
         * Write a transaction to remote equity
         ****************/
        $trans = DTrans::getTransNo($dbc, 1001, 30);
        $dtrans_table = $this->config->get('TRANS_DB') . $dbc->sep() . 'dtransactions';
        $record = DTrans::defaults();
        $record['register_no'] = 30;
        $record['emp_no'] = 1001;
        $record['trans_no'] = $trans;
        $record['upc'] = $classA.'DP992';
        $record['description'] = 'Class A Equity';
        $record['trans_type'] = 'D';
        $record['department'] = 992;
        $record['unitPrice'] = -1*$classA;
        $record['total'] = -1*$classA;
        $record['regPrice'] = -1*$classA;
        $record['card_no'] = $this->id;
        $record['trans_id'] = 1;
        $info = DTrans::parameterize($record, 'datetime', $dbc->now());
        $prep = $dbc->prepare("INSERT INTO $dtrans_table ({$info['columnString']}) VALUES ({$info['valueString']})");
        $dbc->execute($prep, $info['arguments']);

        $record = DTrans::defaults();
        $record['register_no'] = 30;
        $record['emp_no'] = 1001;
        $record['trans_no'] = $trans;
        $record['upc'] = $classB.'DP991';
        $record['description'] = 'Class B Equity';
        $record['trans_type'] = 'D';
        $record['department'] = 991;
        $record['unitPrice'] = -1*$classB;
        $record['total'] = -1*$classB;
        $record['regPrice'] = -1*$classB;
        $record['card_no'] = $this->id;
        $record['trans_id'] = 2;
        $info = DTrans::parameterize($record, 'datetime', $dbc->now());
        $prep = $dbc->prepare("INSERT INTO $dtrans_table ({$info['columnString']}) VALUES ({$info['valueString']})");
        $dbc->execute($prep, $info['arguments']);

        $record = DTrans::defaults();
        $record['register_no'] = 30;
        $record['emp_no'] = 1001;
        $record['trans_no'] = $trans;
        $record['upc'] = $equity.'DP7030';
        $record['description'] = 'Abandon Equity';
        $record['trans_type'] = 'D';
        $record['department'] = 703;
        $record['unitPrice'] = $equity;
        $record['total'] = $equity;
        $record['regPrice'] = $equity;
        $record['card_no'] = $this->id;
        $record['trans_id'] = 3;
        $info = DTrans::parameterize($record, 'datetime', $dbc->now());
        $prep = $dbc->prepare("INSERT INTO $dtrans_table ({$info['columnString']}) VALUES ({$info['valueString']})");
        $dbc->execute($prep, $info['arguments']);

        $record = DTrans::defaults();
        $record['register_no'] = 30;
        $record['emp_no'] = 1001;
        $record['trans_no'] = $trans;
        $record['upc'] = '0';
        $record['description'] = '63350';
        $record['trans_type'] = 'C';
        $record['trans_subtype'] = 'CM';
        $record['card_no'] = $this->idk;
        $record['trans_id'] = 4;
        $info = DTrans::parameterize($record, 'datetime', $dbc->now());
        $prep = $dbc->prepare("INSERT INTO $dtrans_table ({$info['columnString']}) VALUES ({$info['valueString']})");
        $dbc->execute($prep, $info['arguments']);

        /******************
         * Generate a check
         *****************/
        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->SetMargins(6.35, 6.35, 6.35); // quarter-inch margins
        $pdf->SetAutoPageBreak(false);

        $plugin_settings = $this->config->get('PLUGIN_SETTINGS');
        $checkDB = $plugin_settings['GiveUsMoneyDB'];
        $numberP = $dbc->prepare("
            SELECT checkNumber
            FROM " . $checkDB . $dbc->sep() . "GumPayoffs
            WHERE alternateKey=?");

        $custdata = new CustdataModel($dbc);
        $custdata->CardNo($this->id);
        $custdata->personNum(1);
        $custdata->load();
        $meminfo = new MeminfoModel($dbc);
        $meminfo->card_no($this->id);
        $meminfo->load();

        $payoffKey = 'eqr' . $card_no;
        $number = $dbc->getValue($numberP, array($payoffKey));
        if ($number === false) {
            $number = GumLib::allocateCheck($custdata, false, 'EQ REFUND', 'eqr' . $card_no);
        }

        $pdf->AddPage();
        $check = new GumCheckTemplate($custdata, $meminfo, $equity, 'Equity Refund', $number);
        $check->renderAsPDF($pdf);
        $pdf->Output('Equity Refund ' . $this->id . '.pdf', 'I');
    }
}

