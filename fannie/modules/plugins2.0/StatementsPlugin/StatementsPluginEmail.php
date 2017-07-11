<?php
include_once(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('FPDF')) {
    include(dirname(__FILE__) . '/../../../src/fpdf/fpdf.php');
}

class StatementsPluginEmail extends FannieRESTfulPage
{
    public $page_set = 'Plugin :: StatementsPlugin';
    public $description = '[Business Statement Email] generates business invoices as emails';
    public $discoverable = false;
    protected $header = 'Statements Plugin: Email';
    protected $title = 'Statements Plugin: Email';

    private $sent = array();

    private function b2bHandler($dbc, $ids)
    {
        $ids = array_map(function($i) { return str_replace('b2b', '', $i); }, $ids);
        $invP = $dbc->prepare('SELECT * FROM ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'B2BInvoices WHERE b2bInvoiceID=?');
        foreach ($ids as $id) {
            $mail = new PHPMailer();
            $mail->isSMTP();
            $mail->Host = '127.0.0.1';
            $mail->Port = 25;
            $mail->SMTPAuth = false;
            $mail->From = 'finance@wholefoods.coop';
            $mail->FromName = 'Whole Foods Co-op';

            $invoice = $dbc->getRow($invP, array($id));
            $account = \COREPOS\Fannie\API\member\MemberREST::get($invoice['cardNo']);
            $primary = array();
            foreach ($account['customers'] as $c) {
                if ($c['accountHolder']) {
                    $primary = $c;
                    break;
                }
            }
            if (!filter_var($primary['email'], FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $name = $primary['lastName'];
            if (!empty($primary['firstName'])) {
                $name = $primary['firstName'].' '.$name;
            }
            $mail->Subject = 'Invoice ' . $invoice['b2bInvoiceID'];
            $body = "Invoice #: {$invoice['b2bInvoiceID']}\n";
            $html = "<p>Invoice #: {$invoice['b2bInvoiceID']}<br>";
            $stateDate = date('Y-m-d', strtotime($invoice['createdDate']));
            $body .= $stateDate . "\n\n";
            $html .= $stateDate . "</p>";
            $html .= '<p>' . trim($invoice['cardNo']) . ' ' . trim($name) . "<br>";
            $body .= $account['addressFirstLine'] . "\n";
            $html .= $account['addressFirstLine'] . "<br>";
            if ($account['addressSecondLine']) {
                $body .= $account['addressSecondLine'] . "\n";
                $html .= $account['addressSecondLine'] . "<br>";
            }
            $body .= $account['city'] . ', ' . $account['state'] . '   ' . $account['zip'] . "\n\n";
            $html .= $account['city'] . ', ' . $account['state'] . '   ' . $account['zip'] . "</p>";

            $body .= "If payment has been made or sent, please ignore this invoice. If you have any questions about this invoice or would like to make arrangements to pay your balance, please write or call the Finance Department at the above address or (218) 728-0884.\n\n";
            $html .= "<p>If payment has been made or sent, please ignore this invoice. If you have any questions about this invoice or would like to make arrangements to pay your balance, please write or call the Finance Department at the above address or (218) 728-0884.</p>";

            $html .= '<table border="1" cellspacing="0" cellpadding="4">';
            $body .= str_pad($invoice['description'], 100);
            $html .= '<tr><td>' . $invoice['description'] . '</td>';
            $body .= sprintf('$%.2f', $invoice['amount']) . "\n";
            $html .= sprintf('<td>$%.2f</td></tr>', $invoice['amount']);
            if ($invoice['customerNotes']) {
                $body .= 'Notes: ' . $invoice['customerNotes'] . "\n";
                $html .= '<tr><td colspan=2>Notes: ' . $invoice['customerNotes'] . '</td></tr>';
            }
            $body .= "\n";
            $html .= '</table>';

            $body .= 'Amount Due: ';
            $html .= '<p>Amount Due: ';
            $body .= '$ ' . sprintf("%.2f",$invoice['amount']) . "\n";
            $html .= '$ ' . sprintf("%.2f",$invoice['amount']) . "</p>";

            $mail->isHTML(true);
            $mail->Body = $html;
            $mail->AltBody = $body;
            //$mail->addAddress($primary['email']);
            //$mail->addBCC('bcarlson@wholefoods.coop');
            //$mail->addBCC('andy@wholefoods.coop');
            $mail->addAddress('andy@wholefoods.coop');
            $mail->send();
            $this->sent[$name] = $primary['email'];
        }

        return true;
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

        //Meat of the statement
        $balP = $dbc->prepare('
            SELECT balance
            FROM ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'ar_live_balance
            WHERE card_no=?');
        $rowNum=0;
        $dlogMin = date('Y-m-d', mktime(0,0,0, date('n'), date('j')-90, date('Y')));
        foreach ($this->id as $card_no) {
            $mail = new PHPMailer();
            $mail->isSMTP();
            $mail->Host = '127.0.0.1';
            $mail->Port = 25;
            $mail->SMTPAuth = false;
            $mail->From = 'finance@wholefoods.coop';
            $mail->FromName = 'Whole Foods Co-op';

            $account = \COREPOS\Fannie\API\member\MemberREST::get($card_no);
            $primary = array();
            foreach ($account['customers'] as $c) {
                if ($c['accountHolder']) {
                    $primary = $c;
                    break;
                }
            }
            if (!filter_var($primary['email'], FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $name = $primary['lastName'];
            if (!empty($primary['firstName'])) {
                $name = $primary['firstName'].' '.$name;
            }
            $balance = $dbc->getValue($balP, array($card_no));

            $invoice = sprintf('%s-%s', $card_no, date('ymd'));
            $mail->Subject = 'Invoice ' . $invoice;
            $body = "Invoice #: {$invoice}\n";
            $html = "<p>Invoice #: {$invoice}<br>";
            $body .= $stateDate . "\n\n";
            $html .= $stateDate . "</p>";
            $body .= trim($card_no) . ' ' . trim($name) . "\n";
            $html .= '<p>' . trim($card_no) . ' ' . trim($name) . "<br>";
            $body .= $account['addressFirstLine'] . "\n";
            $html .= $account['addressFirstLine'] . "<br>";
            if ($account['addressSecondLine']) {
                $body .= $account['addressSecondLine'] . "\n";
                $html .= $account['addressSecondLine'] . "<br>";
            }
            $body .= $account['city'] . ', ' . $account['state'] . '   ' . $account['zip'] . "\n\n";
            $html .= $account['city'] . ', ' . $account['state'] . '   ' . $account['zip'] . "</p>";
 
            $body .= "If payment has been made or sent, please ignore this invoice. If you have any questions about this invoice or would like to make arrangements to pay your balance, please write or call the Finance Department at the above address or (218) 728-0884.\n\n";
            $html .= "<p>If payment has been made or sent, please ignore this invoice. If you have any questions about this invoice or would like to make arrangements to pay your balance, please write or call the Finance Department at the above address or (218) 728-0884.</p>";

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
            if ($priorBalance != 0) {
                $body .= sprintf('Balance Forward: $%.2f', $priorBalance) . "\n\n";
                $html .= sprintf('<p>Balance Forward: $%.2f</p>', $priorBalance);
            }
 
            $body .= "Billing History\n";
            $html .= "<p>Billing History</p>";
            $html .= '<table border="1" cellspacing="0" cellpadding="4">
                <tr><td>Date</td><td>Receipt#</td><td>Amount</td></tr>';
 
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

                $body .= str_pad($date, 20) . str_pad($trans, 15);
                $html .= "<tr><td>{$date}</td><td>{$trans}</td>";
                if ($payment > $charges) {
                    $body .= sprintf('$%.2f',$payment-$charges);
                    $html .= sprintf('<td>$%.2f</td></tr>',$payment-$charges);
                } else {
                    $body .= sprintf('$(%.2f)',abs($payment-$charges));
                    $html .= sprintf('<td>$(%.2f)</td></tr>',abs($payment-$charges));
                }
                $body .= "\n";
                if (!empty($lineitem)){
                    $body .= "    {$lineitem}\n";
                    $html .= '<tr><td>&nbsp;</td><td colspan="2">' . $lineitem . '</td></tr>';
                }
            }

            $body .= "\n";
            $html .= "</table>";
            if ($balance >= 0) {
                $body .= 'Amount Due: ';
                $html .= '<p>Amount Due: ';
            } else {
                $body .= 'Credit Balance: ';
                $html .= '<p>Credit Balance: ';
            }
            $body .= '$ ' . sprintf("%.2f",$balance) . "\n";
            $html .= '$ ' . sprintf("%.2f",$balance) . "</p>";

            $mail->isHTML(true);
            $mail->Body = $html;
            $mail->AltBody = $body;
            $mail->addAddress($primary['email']);
            $mail->addBCC('bcarlson@wholefoods.coop');
            $mail->addBCC('andy@wholefoods.coop');
            $mail->send();
            $this->sent[$name] = $primary['email'];

            $docfile = "/var/www/cgi-bin/docfile/docfile/" . $card_no;
            if (!file_exists($docfile)) {
                mkdir($docfile);
            }
            $docfile .= '/' . $invoice . '.html';
            file_put_contents($docfile, $html);
        }

        return true;
    }

    function post_id_view()
    {
        $ret = '<h3>Messages sent</h3><ul>';
        foreach ($this->sent as $name => $email) {
            $ret .= "<li>{$name} ({$email})</li>";
        }
        $ret .= '</ul>
            <p>
                <a href="StatementsPluginIndex.php" class="btn btn-default">More Statements</a>
            </p>';

        return $ret;
    } 
}

FannieDispatch::conditionalExec();

