<?php

use COREPOS\Fannie\API\data\pipes\OutgoingEmail;

class StaffArAutoTask extends FannieTask
{
    public $name = 'Staff AR Setup';

    public $description = 'Sets planned AR adjustments and emails datafile to payroll';

    public $default_schedule = array(
        'min' => 5,
        'hour' => 3,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['StaffArPayrollDB']);
        $today = date('Y-m-d');

        $prep = $dbc->prepare("SELECT tdate FROM StaffArDates WHERE tdate > ? ORDER BY tdate");
        $next = $dbc->getValue($prep, array($today));

        $nowDT = new DateTime($today);
        $thenDT = new DateTime($next);
        $diff = $thenDT->diff($nowDT);
        if ($diff->days == 4) {
            $res = $dbc->query("SELECT card_no, nextPayment AS adjust FROM StaffArAccounts");
            $balP = $dbc->prepare("SELECT balance FROM " . FannieDB::fqn('ar_live_balance', 'trans') . " WHERE card_no=?");
            $setP = $dbc->prepare("UPDATE StaffArAccounts SET nextPayment=? WHERE card_no=?");
            while ($row = $dbc->fetchRow($res)) {
                $balance = $dbc->getValue($balP, array($row['card_no']));
                $dbc->execute($setP, array($balance, $row['card_no']));
            }


            $res = $dbc->query("
                SELECT s.payrollIdentifier AS adpID,
                    c.lastName,
                    c.firstName,
                    s.nextPayment AS adjust
                FROM StaffArAccounts AS s
                    LEFT JOIN " . FannieDB::fqn('custdata', 'op') . " AS c ON s.card_no=c.CardNo AND c.personNum=1
                ORDER BY c.lastName");
            $csv = "Employee ID (Clock Sequence),\"1 = Earning, 3 = Deduction\",Paycom Deduction Code,adjust ded amount\r\n";
            while ($row = $dbc->fetchRow($res)) {
                $csv .= sprintf('"%s",3,IOU,%.2f' . "\r\n",
                    $row['adpID'],
                    $row['adjust']
                );
            }

            $mail = OutgoingEmail::get();
            $mail->isSMTP();
            $mail->Host = '127.0.0.1';
            $mail->Port = 25;
            $mail->SMTPAuth = false;
            $mail->SMTPAutoTLS = false;
            $mail->From = $this->config->get('ADMIN_EMAIL');
            $mail->FromName = 'WFC Staff AR';
            $mail->isHTML = false;
            $mail->addAddress('tracyjohnson@wholefoods.coop');
            $mail->addAddress('jlepak@wholefoods.coop');
            $mail->addAddress('andy@wholefoods.coop');
            $mail->Subject = 'Payroll Deductions for ' . $next;
            $mail->Body = 'Data file attached';
            $mail->addStringAttachment(
                $csv,
                'epiU8U16.csv',
                'base64',
                'text/csv'
            );
            $sent = $mail->send();

        }
    }
}

