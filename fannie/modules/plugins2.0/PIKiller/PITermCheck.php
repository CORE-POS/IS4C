<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
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
        if ($status != 'INACT2' && $status != 'TERM') {
            echo 'Can only terminate TERMPENDING owners';
            return false;
        }

        $equity = $dbc->prepare('SELECT payments FROM ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'equity_live_balance WHERE memnum=?');
        $equity = $dbc->getValue($equity, array($this->id));

        $classA = $equity < 20 ? $equity : 20;
        $classB = $equity < 20 ? 0 : $equity - 20;

        $amount = FormLib::get('amount', false);
        if ($amount === false) {
            // Terminate account related tables
            $susp = new SuspensionsModel($dbc);
            $susp->cardno($this->id);
            $susp->type('T');
            $susp->reasoncode(64);
            $susp->save();

            $history = new SuspensionHistoryModel($dbc);
            $history->username($this->current_user);
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

            //
            // Write a transaction to remote equity
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
            $record['description'] = '21230-00-00';
            $record['trans_type'] = 'C';
            $record['trans_subtype'] = 'CM';
            $record['card_no'] = $this->id;
            $record['trans_id'] = 4;
            $info = DTrans::parameterize($record, 'datetime', $dbc->now());
            $prep = $dbc->prepare("INSERT INTO $dtrans_table ({$info['columnString']}) VALUES ({$info['valueString']})");
            $dbc->execute($prep, $info['arguments']);
        } else {
            $equity = $amount;
            $classA = $equity < 20 ? $equity : 20;
            $classB = $equity < 20 ? 0 : $equity - 20;
        }

        /******************
         * Generate a check
         *****************/
        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->SetMargins($left, $left, $left); // quarter-inch margins
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $plugin_settings = $this->config->get('PLUGIN_SETTINGS');
        $checkDB = $plugin_settings['GiveUsMoneyDB'];
        $numberP = $dbc->prepare("
            SELECT checkNumber
            FROM " . $checkDB . $dbc->sep() . "GumPayoffs
            WHERE alternateKey=?");

        $checkDateP = $dbc->prepare("
            UPDATE " . $checkDB . $dbc->sep() . "GumPayoffs 
            SET checkIssued=NOW()
            WHERE checkNumber=?");

        $custdata = new CustdataModel($dbc);
        $custdata->CardNo($this->id);
        $custdata->personNum(1);
        $custdata->load();
        $meminfo = new MeminfoModel($dbc);
        $meminfo->card_no($this->id);
        $meminfo->load();

        $payoffKey = 'eqr' . $this->id;
        $number = $dbc->getValue($numberP, array($payoffKey));
        if ($number === false) {
            $number = GumLib::allocateCheck($custdata, false, 'EQ REFUND', 'eqr' . $this->id);
        }
        $dbc->execute($checkDateP, array($number));

        $pdf->SetXY(0, 0);
        $pdf->Image('../GiveUsMoneyPlugin/img/new_letterhead.png', 10, 10, 35);
        $pdf->SetFont('Arial', '', 12);
        $line_height = 5;
        $left = 55;
        $width = 125;
        $pdf->SetXY($left, 10);
        $pdf->Cell($width, $line_height, date('F d, Y'));
        $pdf->Ln(3*$line_height);
        $pdf->SetX($left);
        $pdf->Cell($width, $line_height, $this->id, 0, 1);
        $pdf->SetX($left);
        $pdf->Cell($width, $line_height, $custdata->FirstName() . ' ' . $custdata->LastName(), 0, 1);
        foreach (explode("\n", $meminfo->street()) as $addr) {
            $pdf->SetX($left);
            $pdf->Cell($width, $line_height, $addr, 0, 1);
        }
        $pdf->SetX($left);
        $pdf->Cell($width, $line_height, $meminfo->city() . ', ' . $meminfo->state() . ' ' . $meminfo->zip(), 0, 1);
        $pdf->Ln($line_height);
        $pdf->SetX($left);
        $pdf->Cell($width, $line_height, 'To Whom It May Concern:', 0, 1);
        $pdf->Ln($line_height);
        $pdf->SetX($left);
        $pdf->MultiCell($width, $line_height, 'The WFC Board of Directors reviewed and approved your application for termination');
        $pdf->Ln($line_height);
        $invest = sprintf('$%.2f Class A Voting Stock', $classA);
        if ($classB > 0) {
            $invest .= sprintf(' and $%.2f Class B Equity Stock', $classB);
        }
        $pdf->SetX($left);
        $pdf->MultiCell($width, $line_height, "We are returning your investment of {$invest}. As per our policy your equity was redeemed at face value. Please see the attached check");
        $pdf->Ln($line_height);
        $pdf->SetX($left);
        $pdf->MultiCell($width, $line_height, 'Thank you for your investment and support of the Whole Foods Co-op.  If you have questions or concerns regarding the return of investment, please contact the Finance Department.');
        $pdf->Ln($line_height);
        $pdf->SetX($left);
        $pdf->Cell($width, $line_height, 'Thank you,', 0, 1);
        $pdf->Ln(2*$line_height);
        $pdf->SetX($left);
        $pdf->Cell($width, $line_height, 'Amanda Borgren', 0, 1);
        $pdf->SetX($left);
        $pdf->Cell($width, $line_height, 'Finance Coordinator', 0, 1);
        $pdf->SetX($left);
        $pdf->Cell($width, $line_height, 'Finance Department', 0, 1);
        $pdf->Ln(2*$line_height);
        $pdf->SetX($left);
        $pdf->Cell($width, $line_height, '218.728.0884 | ext. 453', 0, 1);
        $pdf->SetX($left);
        $pdf->Cell($width, $line_height, 'os@wholefoods.coop', 0, 1);

        $check = new GumCheckTemplate($custdata, $meminfo, $equity, 'Equity Refund', $number);
        $check->shiftMICR(true);
        $check->renderAsPDF($pdf);

        $pdf->Output('Equity Refund ' . $this->id . '.pdf', 'I');
    }
}

FannieDispatch::conditionalExec();

