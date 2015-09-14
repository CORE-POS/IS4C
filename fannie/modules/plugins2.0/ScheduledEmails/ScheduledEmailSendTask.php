<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

class ScheduledEmailSendTask extends FannieTask
{
    public $name = 'Scheduled Emails Task';

    public $description = 'Sends any pending, queued emails to members';    

    public $default_schedule = array(
        'min' => 10,
        'hour' => 4,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['ScheduledEmailDB']);

        $sentP = $dbc->prepare('
            UPDATE ScheduledEmailQueue
            SET sentDate=' . $dbc->now() . ',
                sent=1,
                sentToEmail=?
            WHERE scheduledEmailQueueID=?');

        $failP = $dbc->prepare('
            UPDATE ScheduledEmailQueue
            SET sentDate=' . $dbc->now() . ',
                sent=2,
                sentToEmail=?
            WHERE scheduledEmailQueueID=?');

        // find messages due to be sent
        $query = '
            SELECT scheduledEmailQueueID,
                scheduledEmailTemplateID,
                cardNo,
                templateData
            FROM ScheduledEmailQueue
            WHERE sent=0
                AND sendDate <= ' . $dbc->now() . '
            ORDER BY scheduledEmailTemplateID';
        $result = $dbc->query($query);
        $template = new ScheduledEmailTemplatesModel($dbc);
        while ($row = $dbc->fetchRow($result)) {
            $template->scheduledEmailTemplateID($row['scheduledEmailTemplateID']);
            if (!$template->load()) {
                $this->cronMsg('Template does not exist: ' . $row['scheduledEmailTemplateID']);
                continue;
            }
            $member = \COREPOS\Fannie\API\member\MemberREST::get($row['cardNo']);
            $dbc->selectDB($settings['ScheduledEmailDB']); // reset current DB
            if ($member === false) {
                $this->cronMsg('Member does not exist: ' . $row['cardNo']);
                continue;
            }
            $email = false;
            foreach ($member['customers'] as $customer) {
                if ($customer['accountHolder']) {
                    $email = $customer['email'];
                    break;
                }
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->cronMsg('Member does not have valid email address: ' . $row['cardNo']);
                $dbc->execute($failP, array('no email address', $row['scheduledEmailQueueID']));
                continue;
            }
            $data = json_decode($row['templateData'], true);
            if ($data === null && $row['templateData'] !== null) {
                $this->cronMsg('Invalid template data: ' . $row['data']);
                continue;
            } elseif (!is_array($data)) {
                $data = array();
            }

            if (self::sendEmail($template, $email, $data)) {
                $dbc->execute($sentP, array($email, $row['scheduledEmailQueueID']));
            } else {
                $dbc->execute($failP, array('error sending', $row['scheduledEmailQueueID']));
            }
        }
    }

    /**
      Helper function to send messages
      @param $templateID [ScheduledEmailTemplatesModel] template 
      @param $address [string] recipient email address
      @param $data [keyed array] of placeholder values
      @return [boolean] success
    */
    static public function sendEmail($template, $address, $data=array())
    {
        $config = FannieConfig::factory();
        $settings = $config->get('PLUGIN_SETTINGS');

        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = '127.0.0.1';
        $mail->Port = 25;
        $mail->SMTPAuth = false;
        $mail->From = $settings['ScheduledEmailFrom'];
        $mail->FromName = $settings['ScheduledEmailFromName'];
        $mail->addReplyTo($settings['ScheduledEmailReplyTo']);
        $mail->addAddress($address);
        $mail->Subject = $template->subject();
        if ($template->hasHTML()) {
            $mail->isHTML(true);
            $mail->Body = self::substitutePlaceholders($template->htmlCopy(), $data);
            if ($template->hasText()) {
                $mail->AltBody = self::substitutePlaceholders($template->textCopy(), $data);
            }
            return $mail->send();
        } elseif ($template->hasText()) {
            $mail->isHTML(false);
            $mail->Body = self::substitutePlaceholders($template->textCopy(), $data);
            return $mail->send();
        } else {
            return false;
        }
    }

    static private function substitutePlaceholders($text, $placeholders)
    {
        foreach ($placeholders as $name => $value) {
            $text = str_replace('{{' . $name . '}}', $value, $text);
        }

        return $text;
    }
}

