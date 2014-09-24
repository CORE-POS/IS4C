<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class GumEmailPage extends FannieRESTfulPage 
{
    // disabled so that other pages can call the email-sending request handlers
    protected $must_authenticate = false;
    //protected $auth_classes = array('GiveUsMoney');

    public $page_set = 'Plugin :: Give Us Money';
    public $description = '[Emails] can send different notifications to account holders.';

    public function preprocess()
    {
        $acct = FormLib::get('id');
        $this->header = 'Email Communications' . ' : ' . $acct;
        $this->title = 'Email Communications' . ' : ' . $acct;
        $this->__routes[] = 'get<id><welcome>';
        $this->__routes[] = 'get<id><creceipt><cid>';
        $this->__routes[] = 'get<id><loanstatement>';

        return parent::preprocess();
    }

    public function get_id_welcome_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;

        $bridge = GumLib::getSetting('posLayer');
        $this->custdata = $bridge::getCustdata($this->id);
        $this->meminfo = $bridge::getMeminfo($this->id);

        // bridge may change selected database
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        $msg = 'Dear ' . $this->custdata->FirstName() . ' ' . $this->custdata->LastName() . ',' . "\n";
        $msg .= "\n";

        $msg .= wordwrap('Your investment in Whole Foods Co-op is deeply appreciated. Your participation will make WFC-Denfeld develop and thrive') . "\n";
        $msg .= "\n";

        $msg .= wordwrap('Please take a moment to review your contact information for accuracy:') . "\n";
        $msg .= "\n";

        $spacer = str_repeat(' ', 6);
        $msg .= $spacer . $this->custdata->FirstName() . ' ' . $this->custdata->LastName() . "\n";
        $msg .= $spacer . $this->meminfo->street() . "\n";
        $msg .= $spacer . $this->meminfo->city() . ', ' . $this->meminfo->state() . ' ' . $this->meminfo->zip() . "\n";
        $msg .= $spacer . $this->meminfo->phone() . "\n";
        if ($this->meminfo->email_2()) {
            $msg .= $spacer . $this->meminfo->email_2() . "\n";
        }
        $msg .= $spacer . $this->meminfo->email_1() . "\n";
        $msg .= $spacer . 'Owner #' . $this->id . "\n";
        $msg .= "\n";

        $msg .= wordwrap('As more Owners invest, we want your experiences and involvement to go as smoothly as possible. If any of this information is incorrect or any issues arise, please reply to this email or to finance@wholefoods.coop. Or you may call 218-728-0884, ask for Finance, and we will gladly assist you. We ask that you contact us in the future with any changes in the above information.') . "\n";
        $msg .= "\n";

        $msg .= wordwrap('Whole Foods Co-op thanks you once again for your commitment to our stores and our community') . "\n";
        $msg .= "\n";

        $msg .= 'Dale Maiers' . "\n";
        $msg .= 'Finance Manager' . "\n";

        $subject = 'WFC Owner Financing: Welcome';
        $to = $this->meminfo->email_1();
        $headers = 'From: Whole Foods Co-op <finance@wholefoods.coop>' . "\r\n"
            . 'Reply-To: finance@wholefoods.coop' . "\r\n";

        $uid = FannieAuth::getUID($this->current_user);
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        $log = new GumEmailLogModel($dbc);
        $log->card_no($this->id);
        $log->tdate(date('Y-m-d H:i:s'));
        $log->uid($uid);
        $log->messageType('Welcome');

        if (FormLib::get('sendAs') == 'print') {
            echo '<pre>' . $msg . '</pre>';

            return false;
        } else if (mail($to, $subject, $msg, $headers)) {
            $log->save();
            header('Location: GumEmailPage.php?id=' . $this->id);
        } else {
            echo 'Error: unable to send email. Notify IT';
        }
        return false;
    }

    public function get_id_loanstatement_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB, $FANNIE_ROOT;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        $loan = new GumLoanAccountsModel($dbc);
        $loan->accountNumber($this->id);
        $loan->load();

        $bridge = GumLib::getSetting('posLayer');
        $this->custdata = $bridge::getCustdata($loan->card_no());
        $this->meminfo = $bridge::getMeminfo($loan->card_no());

        // bridge may change selected database
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        $preamble = 'Hello, Owner ' . "\n";
        $preamble .= 'Here is a statement on your owner loan to WFC as of '
            . date('m/d/Y', mktime(0, 0, 0, GumLib::getSetting('FYendMonth'), GumLib::getSetting('FYendDay'), date('Y')))
            . ', the end of the co-op\'s fiscal year. This is just for your information -'
            . ' no action is required and you do not have to report interest income until your'
            . ' loan is reppaid. Thank you for your support';

        $info_section = 'First Name: ' . $this->custdata->FirstName() . "\n"
            . 'Last Name: ' . $this->custdata->LastName() . "\n"
            . 'Address: ' . $this->meminfo->street() . "\n"
            . 'City: ' . $this->meminfo->city() . "\n"
            . 'State: ' . $this->meminfo->state() . "\n"
            . 'Zip Code: ' . $this->meminfo->zip() . "\n"
            . 'Loan Amount: ' . number_format($loan->principal(), 2) . "\n"
            . 'Loan Date: ' . date('m/d/Y', strtotime($loan->loanDate())) . "\n"
            . 'Loan Term: ' . ($loan->termInMonths() / 12) . ' years' . "\n"
            . 'Interest Rate: ' . number_format($loan->interestRate()*100, 2) . "%\n";
        $ld = strtotime($loan->loanDate());
        $ed = mktime(0, 0, 0, date('n', $ld)+$loan->termInMonths(), date('j', $ld), date('Y', $ld));
        $info_section .= 'Maturity Date: ' . date('m/d/Y', $ed) . "\n";

        $schedule = GumLib::loanSchedule($loan);
        $interest = 0.0;
        $balance = 0.0;
        $html = '<table style="border-spacing:1em;">
                <tr><th colspan="4" style="text-align:center;">Schedule</th></tr>
                <tr><th>Year Ending</th><th>Days</th><th>Interest</th><th>Balance</th></tr>';
        $text = 'Annual Schedule:' . "\n";
        foreach ($schedule['schedule'] as $year) {
            if (strtotime($year['end_date']) > time()) {
                break;
            }
            $html .= '<tr> <td>' . $year['end_date'] . '</td> <td>'
                . $year['days'] . '</td> <td>'
                . number_format($year['interest'], 2) . '</td> <td>'
                . number_format($year['balance'], 2) . '</td> </tr>';
            $text .= 'Year Ending: ' . $year['end_date'] . "\n"
                . 'Days: ' . $year['days'] . "\n"
                . 'Interest: ' . number_format($year['interest'], 2) . "\n"
                . 'Balance: ' . number_format($year['balance'], 2) . "\n\n";
            $interest += $year['interest'];
            $balance = $year['balance'];
        }
        $html .= '<tr><th>Balance</th><th>'
                . number_format($loan->principal(), 2) . '</th><th>'
                . number_format($interest, 2) . '</th><th>'
                . number_format($balance, 2) . '</th></tr>';
        $html .= '</table>';

        $text = wordwrap($preamble) . "\n" . $info_section . $text;
        $html = '<p>' . wordwrap(nl2br($preamble)) . '</p>'
                . '<p>' . nl2br($info_section) . '</p>'
                . wordwrap($html);
        $html = '<html><body>' . $html . '</body></html>';

        $uid = FannieAuth::getUID($this->current_user);
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        $log = new GumEmailLogModel($dbc);
        $log->card_no($loan->card_no());
        $log->tdate(date('Y-m-d H:i:s'));
        $log->uid($uid);
        $log->messageType('Statement (' . $this->id . ')');

        if (!class_exists('SMTP')) {
            include($FANNIE_ROOT . 'src/PHPMailer/class.smtp.php');
        }
        if (!class_exists('PHPMailer')) {
            include($FANNIE_ROOT . 'src/PHPMailer/class.phpmailer.php');
        }
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = '127.0.0.1';
        $mail->Port = 25;
        $mail->SMTPAuth = false;
        $mail->From = 'finance@wholefoods.coop';
        $mail->FromName = 'Whole Foods Co-op';
        $mail->addReplyTo('finance@wholefoods.coop');
        $mail->addAddress($this->meminfo->email_1());
        $mail->isHTML(true);
        $mail->Subject = 'Owner Loan Statement';
        $mail->Body = $html;
        $mail->AltBody = $text;

        if (FormLib::get('sendAs') == 'print') {
            echo $html;

            return false;
        } else if ($mail->send()) {
            $log->save();
            header('Location: GumEmailPage.php?id=' . $loan->card_no());
        } else {
            echo 'Error: unable to send email. Notify IT';
        }

        return false;
    }

    public function get_id_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;

        $bridge = GumLib::getSetting('posLayer');
        $this->custdata = $bridge::getCustdata($this->id);
        $this->meminfo = $bridge::getMeminfo($this->id);

        // bridge may change selected database
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        $this->maillog = new GumEmailLogModel($dbc);
        $this->maillog->card_no($this->id);

        $this->loans = array();
        $model = new GumLoanAccountsModel($dbc);
        $model->card_no($this->id);
        foreach($model->find('loanDate') as $obj) {
            $this->loans[] = $obj;
        }

        $this->equity = array();
        $model = new GumEquitySharesModel($dbc);
        $model->card_no($this->id);
        foreach($model->find('tdate') as $obj) {
            $this->equity[] = $obj;
        }

        $this->settings = new GumSettingsModel($dbc);

        return true;
    }

    public function get_id_creceipt_cid_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;

        $bridge = GumLib::getSetting('posLayer');
        $this->custdata = $bridge::getCustdata($this->id);
        $this->meminfo = $bridge::getMeminfo($this->id);
        $uid = FannieAuth::getUID($this->current_user);

        // bridge may change selected database
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
    
        $msg = 'Dear ' . $this->custdata->FirstName() . ' ' . $this->custdata->LastName() . ',' . "\n";
        $msg .= "\n";

        $msg .= 'Class C Stock Purchase Receipt:' . "\n";
        $msg .= "\n";

        $model = new GumEquitySharesModel($dbc);
        $model->gumEquityShareID($this->cid);
        $model->load();

        $spacer = str_repeat(' ', 6);
        $msg .= $spacer . 'Owner Number: ' . $this->id . "\n";
        $msg .= $spacer . 'Date/Time: ' . $model->tdate() . "\n";
        $msg .= $spacer . 'No. of Shares: ' . $model->shares() . "\n";
        $msg .= $spacer . 'Purchase Amount: $' . number_format($model->value(), 2) . "\n";
        $msg .= "\n";

        $model->reset();
        $model->card_no($this->id);
        $shares = 0;
        $value = 0;
        $purchases = 0;
        foreach($model->find() as $obj) {
            $shares += $obj->shares();
            $value += $obj->value();
            if ($shares > 0) {
                $purchases++;
            }
        }
        if ($purchases > 1) {
            $msg .= 'Total Class C Stock Owned:' . "\n";
            $msg .= $spacer . 'No. of Shares: ' . $shares . "\n";
            $msg .= $spacer . 'Value of Shares: $' . number_format($value, 2) . "\n";
            $msg .= "\n";
        }

        $msg .= wordwrap('Whole Foods Co-op recognizes and thanks you for your support and purchase of Class C Stock. It is important that we maintain your current contact information so that we can deliver any dividends you may earn. Please reply to this email or to finance@wholefoods.coop with any questions or concerns. Or you may also call 218-728-0884, ask for Finance, and we will gladly assist you.') . "\n";
        $msg .= "\n";

        $msg .= 'Dale Maiers' . "\n";
        $msg .= 'Finance Manager' . "\n";

        $subject = 'WFC Owner Financing: Class C Stock Receipt';
        $to = $this->meminfo->email_1();
        $headers = 'From: Whole Foods Co-op <finance@wholefoods.coop>' . "\r\n"
            . 'Reply-To: finance@wholefoods.coop' . "\r\n";

        $log = new GumEmailLogModel($dbc);
        $log->card_no($this->id);
        $log->tdate(date('Y-m-d H:i:s'));
        $log->uid($uid);
        $log->messageType('Equity Receipt (' . $this->cid . ')');

        if (FormLib::get('sendAs') == 'print') {
            echo '<pre>' . $msg . '</pre>';

            return false;
        } else if (mail($to, $subject, $msg, $headers)) {
            $log->save();
            header('Location: GumEmailPage.php?id=' . $this->id);
        } else {
            echo 'Error: unable to send email. Notify IT';
        }

        return false;
    }

    public function css_content()
    {
        return '
        ';
    }

    public function get_id_view()
    {
        global $FANNIE_URL;
        $ret = '';

        $ret .= _('Owner') . ': ' . $this->id . ' ' . $this->custdata->FirstName() . ' ' . $this->custdata->LastName();
        $ret .= '<br />';
        $ret .= 'Email: ' . $this->meminfo->email_1();
        $ret .= '<br />';
        $ret .= 'Action: <select id="sendType">';
        $ret .= '<option value="email">E-Mail</option>';
        $ret .= sprintf('<option value="print" %s>Print</option>',
                    ($this->meminfo->email_1() == '' ? 'selected' : ''));
        $ret .= '</select>';
        $ret .= '<br />';
        $ret .= '<br />';

        $ret .= '<fieldset><legend>Message History</legend>';
        $ret .= '<table cellpadding="4" cellspacing="0" border="1">';
        foreach($this->maillog->find('tdate', true) as $obj) {
            $ret .= sprintf('<tr>
                            <td>%s</td>
                            <td>%s</td>
                            </tr>',
                            $obj->tdate(),
                            $obj->messageType()
            );
        }
        $ret .= '</table>';
        $ret .= '</fieldset>';

        $ret .= '<fieldset><legend>Send Messages</legend>';
        $ret .= '<table cellpadding="4" cellspacing="0" border="1">';
        $ret .= sprintf('<tr><td colspan="2">Welcome Message</td>
                <td><input type="button" value="Send Welcome" 
                    onclick="location=\'GumEmailPage.php?id=%d&welcome=1&sendAs=\'+$(\'#sendType\').val();" />
                    </td></tr>', $this->id);
        foreach($this->loans as $obj) {
            $ret .= sprintf('<tr>
                            <td>Loan Account %s (%.2f)</td>
                            <td>%s</td>
                            <td><input type="button" value="Send Statement"
                                onclick="location=\'GumEmailPage.php?id=%s&loanstatement=1&sendAs=\'+$(\'#sendType\').val();" />
                            </tr>',
                            $obj->accountNumber(),
                            $obj->principal(),
                            $obj->loanDate(),
                            $obj->accountNumber()
            );
        }
        foreach($this->equity as $obj) {
            $ret .= sprintf('<tr>
                            <td>Equity %s %.2f</td>
                            <td>%s</td>',
                            ($obj->value() < 0 ? 'Payoff' : 'Purchase'),
                            $obj->value(),
                            $obj->tdate()
            );
            if ($obj->value() < 0) {
                $ret .= '<td>(No messages available)</td>';
            } else {
                $ret .= sprintf('<td><input type="button" value="Send Receipt"
                                    onclick="location=\'GumEmailPage.php?id=%d&creceipt=1&cid=%d&sendAs=\'+$(\'#sendType\').val();" />
                                    </td>',
                                    $this->id,
                                    $obj->gumEquityShareID()
                );
            }
        }
        $ret .= '</table>';
        $ret .= '</fieldset>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

