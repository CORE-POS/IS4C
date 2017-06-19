<?php
include_once(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('FPDF')) {
    include(dirname(__FILE__) . '/../../../src/fpdf/fpdf.php');
}

class StatementsPluginBusiness extends FannieRESTfulPage
{
    public $page_set = 'Plugin :: StatementsPlugin';
    public $description = '[Business Statement PDF] generates business invoices';

    private function b2bHandler($dbc, $ids)
    {
        $ids = array_map(function($i) { return str_replace('b2b', '', $i); }, $ids);
        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
        $pdf->SetAutoPageBreak(false);
        $invP = $dbc->prepare('SELECT * FROM ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'B2BInvoices WHERE b2bInvoiceID=?');
        foreach ($ids as $id) {
            $invoice = $dbc->getRow($invP, array($id));
            $account = \COREPOS\Fannie\API\member\MemberREST::get($invoice['cardNo']);
            $primary = array();
            foreach ($account['customers'] as $c) {
                if ($c['accountHolder']) {
                    $primary = $c;
                    break;
                }
            }

            $pdf->AddPage();
            $pdf->Image('new_letterhead_horizontal.png',5,10, 200);
            $pdf->SetFont('Gill','','12');
            $pdf->Ln(45);
            $pdf->Cell(10,5,"Invoice #: " . $invoice['b2bInvoiceID'],0,1,'L');
            $pdf->Cell(10,5,date('Y-m-d', strtotime($invoice['createdDate'])),0);
            $pdf->Ln(8);

            $name = $primary['lastName'];
            if (!empty($primary['firstName'])) {
                $name = $primary['firstName'].' '.$name;
            }
            $pdf->Cell(50,10,trim($card_no).' '.trim($name),0);
            $pdf->Ln(5);
            $pdf->Cell(80, 10, $account['addressFirstLine'], 0);
            $pdf->Ln(5);
            if ($account['addressSecondLine']) {
                $pdf->Cell(80, 10, $account['addressSecondLine'], 0);
                $pdf->Ln(5);
            }
            $pdf->Cell(90,10,$account['city'] . ', ' . $account['state'] . '   ' . $account['zip'],0);
            $pdf->Ln(25);

            $txt = "If payment has been made or sent, please ignore this invoice. If you have any questions about this invoice or would like to make arrangements to pay your balance, please write or call the Finance Department at the above address or (218) 728-0884.";
            $pdf->MultiCell(0,5,$txt);
            $pdf->Ln(10);

            $indent = 10;
            $columns = array(140, 30);
            $pdf->Cell($indent,8,'',0,0,'L');
            $pdf->Cell($columns[0],8,$invoice['description'],0,0,'L');
            $pdf->Cell($columns[1],8,'$ ' . sprintf('%.2f',$invoice['amount']),0,0,'L');
            $pdf->Ln(5);

            if ($invoice['customerNotes']) {
                $pdf->Ln(5);
                $pdf->MultiCell(0, 5, 'Notes: ' . $invoice['customerNotes']);
                $pdf->Ln(5);
            }

            $pdf->Ln(15);
            $pdf->Cell($indent,8,'');
            $pdf->SetFillColor(200);
            $pdf->Cell(35,8,'Amount Due',0,0,'L',1);
            $pdf->Cell(25,8,'$ ' . sprintf("%.2f",$invoice['amount']),0,0,'L');
        }
        $pdf->Output('makeStatement.pdf','D');

        return false;
    }

    public function post_id_handler()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB, $FANNIE_ROOT, $FANNIE_ARCHIVE_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $cards = "(";
        $args = array();
        if (!is_array($this->id)) {
            $this->id = array($this->id);
        }
        if (count($this->id) > 0 && substr($this->id[0], 0, 3) == 'b2b') {
            return $this->b2bHandler($dbc, $this->id);
        }
        foreach($this->id as $c) {
            $cards .= "?,";
            $args[] = $c;
        }
        $cards = rtrim($cards,",");
        $cards .= ")";

        $cardsClause = " AND m.card_no IN $cards ";
        if ($cards == "(") $cardsClause = "";

        /**
          Look up transactions involving AR over last 90 days
        */
        $transP = $dbc->prepare("
            SELECT card_no, 
                CASE WHEN trans_subtype='MI' THEN -total ELSE 0 END AS charges,
                CASE WHEN department=990 then total ELSE 0 END as payments, 
                tdate, 
                trans_num
            FROM " . $FANNIE_TRANS_DB . $dbc->sep() . "dlog_90_view AS m 
            WHERE m.card_no IN " . $cards . "
                AND (department=990 OR trans_subtype='MI')
            ORDER BY card_no, 
                tdate, 
                trans_num");
        $transP = $dbc->prepare("
            SELECT card_no,
                charges,
                payments,
                tdate,
                trans_num,
                'OLD' as timespan
            FROM " . $FANNIE_TRANS_DB . $dbc->sep() . "ar_history 
            WHERE card_no IN " . $cards . "
                AND tdate >= ?
            UNION ALL
            SELECT card_no,
                charges,
                payments,
                tdate,
                trans_num,
                'TODAY' as timespan
            FROM " . $FANNIE_TRANS_DB . $dbc->sep() . "ar_history_today
            WHERE card_no IN " . $cards . "
            ORDER BY tdate");
        $date = date('Y-m-d', mktime(0, 0, 0, date('n'), date('j')-90, date('Y')));
        $trans_args = $args;
        $trans_args[] = $date;
        foreach ($args as $a) { // need cards twice for the union
            $trans_args[] = $a;
        }
        $transR = $dbc->execute($transP, $trans_args);

        $arRows = array();
        while ($row = $dbc->fetch_row($transR)) {
            if (!isset($arRows[$row['card_no']])) {
                $arRows[$row['card_no']] = array();
            }
            $arRows[$row['card_no']][] = $row;
            $date = explode(' ',$row['tdate']);
            $date_id = date('Ymd', strtotime($date[0]));
        }

        /**
          Lookup details of AR related transactions
          Stucture is:
          * card_no
            => trans_num
               => line item description(s)
        */
        $detailsQ = '
            SELECT card_no,
                description,
                department,
                trans_num
            FROM ' . $FANNIE_ARCHIVE_DB . $dbc->sep() . 'dlogBig
            WHERE tdate BETWEEN ? AND ?
                AND trans_num=?
                AND card_no=?
                AND trans_type IN (\'I\', \'D\')
        ';         
        $todayQ = str_replace($FANNIE_ARCHIVE_DB . $dbc->sep() . 'dlogBig', $FANNIE_TRANS_DB . $dbc->sep() . 'dlog', $detailsQ);
        $detailsP = $dbc->prepare($detailsQ);
        $todayP = $dbc->prepare($todayQ);
        $details = array();
        $minDate = array();
        foreach ($arRows as $card_no => $trans) {
            $found_charge = false;
            foreach ($trans as $info) {
                if ($info['charges'] != 0) {
                    $found_charge = true;
                }
                $tstamp = strtotime($info['tdate']);
                $args = array(
                    date('Y-m-d 00:00:00', $tstamp),
                    date('Y-m-d 23:59:59', $tstamp),
                    $info['trans_num'],
                    $info['card_no'],
                );
                if ($info['timespan'] == 'TODAY') {
                    $res = $dbc->execute($todayP, $args);
                } else {
                    $res = $dbc->execute($detailsP, $args);
                }
                while ($row = $dbc->fetch_row($res)) {
                    $trans_num = $row['trans_num'];
                    if (!isset($details[$row['card_no']])) {
                        $details[$row['card_no']] = array();
                    }
                    if (!isset($details[$row['card_no']][$trans_num])) {
                        $details[$row['card_no']][$trans_num] = array();
                    }
                    $details[$row['card_no']][$trans_num][] = $row['description'];
                }
            }
            if ($found_charge) {
                $actual = array();
                $num=0;
                while ($arRows[$card_no][$num]['charges'] == 0) {
                    $num++;
                }
                for ($i=$num; $i<count($arRows[$card_no]); $i++) {
                    $actual[] = $arRows[$card_no][$i];
                }
                $arRows[$card_no] = $actual;
                $minDate[$card_no] = $arRows[$card_no][0]['tdate'];
            }
        }

        $today= date("d-F-Y");
        $month = date("n");
        $year = date("Y");

        $stateDate = date("d F, Y",mktime(0,0,0,date('n'),0,date('Y')));

        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
        $pdf->SetAutoPageBreak(false);

        //Meat of the statement
        $balP = $dbc->prepare('
            SELECT balance
            FROM ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'ar_live_balance
            WHERE card_no=?');
        $rowNum=0;
        $dlogMin = date('Y-m-d', mktime(0, 0, 0, date('n'), date('j')-90, date('Y')));
        foreach ($this->id as $card_no) {
            $account = \COREPOS\Fannie\API\member\MemberREST::get($card_no);
            $primary = array();
            foreach ($account['customers'] as $c) {
                if ($c['accountHolder']) {
                    $primary = $c;
                    break;
                }
            }
            $balance = $dbc->getValue($balP, array($card_no));

            $pdf->AddPage();
            $copy = new FPDF('P', 'mm', 'Letter');
            $copy->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
            $copy->SetAutoPageBreak(false);
            $copy->AddPage();

            $pdf->Image('new_letterhead_horizontal.png',5,10, 200);
            $copy->Image('new_letterhead_horizontal.png',5,10, 200);
            $pdf->SetFont('Gill','','12');
            $copy->SetFont('Gill','','12');
            $pdf->Ln(45);
            $copy->Ln(45);

            $invoice = sprintf("%s-%s", $card_no, date('ymd'));
            $pdf->Cell(10,5,"Invoice #: " . $invoice,0,1,'L');
            $copy->Cell(10,5,"Invoice #: " . $invoice,0,1,'L');
            $pdf->Cell(10,5,$stateDate,0);
            $copy->Cell(10,5,$stateDate,0);
            $pdf->Ln(8);
            $copy->Ln(8);

            //Member address
            $name = $primary['lastName'];
            if (!empty($primary['firstName'])) {
                $name = $primary['firstName'].' '.$name;
            }
            $pdf->Cell(50,10,trim($card_no).' '.trim($name),0);
            $copy->Cell(50,10,trim($card_no).' '.trim($name),0);
            $pdf->Ln(5);
            $copy->Ln(5);

            $pdf->Cell(80, 10, $account['addressFirstLine'], 0);
            $copy->Cell(80, 10, $account['addressFirstLine'], 0);
            $pdf->Ln(5);
            $copy->Ln(5);
            if ($account['addressSecondLine']) {
                $pdf->Cell(80, 10, $account['addressSecondLine'], 0);
                $copy->Cell(80, 10, $account['addressSecondLine'], 0);
                $pdf->Ln(5);
                $copy->Ln(5);
            }
            $pdf->Cell(90,10,$account['city'] . ', ' . $account['state'] . '   ' . $account['zip'],0);
            $copy->Cell(90,10,$account['city'] . ', ' . $account['state'] . '   ' . $account['zip'],0);
            $pdf->Ln(25);
            $copy->Ln(25);
 
            $txt = "If payment has been made or sent, please ignore this invoice. If you have any questions about this invoice or would like to make arrangements to pay your balance, please write or call the Finance Department at the above address or (218) 728-0884.";
            $pdf->MultiCell(0,5,$txt);
            $copy->MultiCell(0,5,$txt);
            $pdf->Ln(10);
            $copy->Ln(10);

            $priorQ = $dbc->prepare("
                SELECT SUM(charges) - SUM(payments) AS priorBalance
                FROM " . $FANNIE_TRANS_DB . $dbc->sep() . "ar_history
                WHERE tdate < ?
                    AND card_no = ?");
            $cutoff = isset($minDate[$card_no]) ? $minDate[$card_no] : $dlogMin;
            $priorR = $dbc->execute($priorQ, array($cutoff, $card_no));
            $priorW = $dbc->fetch_row($priorR);
            $priorBalance = is_array($priorW) ? $priorW['priorBalance'] : 0;

            $indent = 10;
            $columns = array(75, 35, 30, 30);
            $pdf->Cell($indent,8,'');
            $copy->Cell($indent,8,'');
            $pdf->SetFillColor(200);
            $copy->SetFillColor(200);
            $pdf->Cell(40,8,'Balance Forward',0,0,'L',1);
            $copy->Cell(40,8,'Balance Forward',0,0,'L',1);
            $pdf->Cell(25,8,'$ ' . sprintf("%.2f",$priorBalance),0,0,'L');
            $copy->Cell(25,8,'$ ' . sprintf("%.2f",$priorBalance),0,0,'L');
            $pdf->Ln(8);
            $copy->Ln(8);
 
            $pdf->Cell(0,8,"Billing History",0,1,'C');
            $copy->Cell(0,8,"Billing History",0,1,'C');
            $pdf->SetFillColor(200);
            $copy->SetFillColor(200);
            $pdf->Cell($indent,8,'',0,0,'L');
            $copy->Cell($indent,8,'',0,0,'L');
            $pdf->Cell($columns[0],8,'Date',0,0,'L',1);
            $copy->Cell($columns[0],8,'Date',0,0,'L',1);
            $pdf->Cell($columns[1],8,'Receipt',0,0,'L',1);
            $copy->Cell($columns[1],8,'Receipt',0,0,'L',1);
            $pdf->Cell($columns[2],8,'',0,0,'L',1);
            $copy->Cell($columns[2],8,'',0,0,'L',1);
            $pdf->Cell($columns[3],8,'Amount',0,1,'L',1);
            $copy->Cell($columns[3],8,'Amount',0,1,'L',1);
 
            if (!isset($arRows[$card_no])) {
                $arRows[$card_no] = array();
            }
            foreach ($arRows[$card_no] as $arRow) {

                $date = $arRow['tdate'];
                $trans = $arRow['trans_num'];
                $charges = $arRow['charges'];
                $payment =  $arRow['payments'];

                $detail = $details[$card_no][$trans];

                $lineitem = (count($detail)==1) ? $detail[0] : '(multiple items)';
                foreach ($detail as $line) {
                    if ($line == 'ARPAYMEN') {
                        $lineitem = 'Payment Received - Thank You';
                    }
                }

                $pdf->Cell($indent,8,'',0,0,'L');
                $copy->Cell($indent,8,'',0,0,'L');
                $pdf->Cell($columns[0],8,$date,0,0,'L');
                $copy->Cell($columns[0],8,$date,0,0,'L');
                $pdf->Cell($columns[1],8,$trans,0,0,'L');
                $copy->Cell($columns[1],8,$trans,0,0,'L');
                $pdf->Cell($columns[2],8,'',0,0,'L');
                $copy->Cell($columns[2],8,'',0,0,'L');
                if ($payment > $charges) {
                    $pdf->Cell($columns[3],8,'$ ' . sprintf('%.2f',$payment-$charges),0,0,'L');
                    $copy->Cell($columns[3],8,'$ ' . sprintf('%.2f',$payment-$charges),0,0,'L');
                } else {
                    $pdf->Cell($columns[3],8,'$ ' . sprintf('(%.2f)',abs($payment-$charges)),0,0,'L');
                    $copy->Cell($columns[3],8,'$ ' . sprintf('(%.2f)',abs($payment-$charges)),0,0,'L');
                }
                if ($pdf->GetY() > 245){
                    $pdf->AddPage();
                    $copy->AddPage();
                } else {
                    $pdf->Ln(5);
                    $copy->Ln(5);
                }
                if (!empty($lineitem)){
                    $pdf->SetFontSize(10);
                    $copy->SetFontSize(10);
                    $pdf->Cell($indent+10,8,'',0,0,'L');
                    $copy->Cell($indent+10,8,'',0,0,'L');
                    $pdf->Cell(60,8,$lineitem,0,0,'L');
                    $copy->Cell(60,8,$lineitem,0,0,'L');
                    if ($pdf->GetY() > 245) {
                        $pdf->AddPage();
                        $copy->AddPage();
                    } else {
                        $pdf->Ln(5);
                        $copy->Ln(5);
                    }
                    $pdf->SetFontSize(12);
                    $copy->SetFontSize(12);
                }
            }

            $pdf->Ln(15);
            $copy->Ln(15);
            $pdf->Cell($indent,8,'');
            $copy->Cell($indent,8,'');
            $pdf->SetFillColor(200);
            $copy->SetFillColor(200);
            if ($balance >= 0) {
                $pdf->Cell(35,8,'Amount Due',0,0,'L',1);
                $copy->Cell(35,8,'Amount Due',0,0,'L',1);
            } else {
                $pdf->Cell(35,8,'Credit Balance',0,0,'L',1);
                $copy->Cell(35,8,'Credit Balance',0,0,'L',1);
            }
            $pdf->Cell(25,8,'$ ' . sprintf("%.2f",$balance),0,0,'L');
            $copy->Cell(25,8,'$ ' . sprintf("%.2f",$balance),0,0,'L');

            $docfile = "/var/www/cgi-bin/docfile/docfile/" . $card_no;
            if (!file_exists($docfile)) {
                mkdir($docfile);
            }
            $docfile .= '/' . $invoice . '.pdf';
            $copy->Output($docfile, 'F');
        }

        $pdf->Output('makeStatement.pdf','D');

        return false;
    }
}

FannieDispatch::conditionalExec();

