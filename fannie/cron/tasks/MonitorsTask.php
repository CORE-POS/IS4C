<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

class MonitorsTask extends FannieTask
{
    public $name = 'Monitoring Task';

    public $description = 'Run periodic system monitoring tasks
to assess conditions, generate reports, and populate the dashboard.';

    public $log_start_stop = false;

    public $default_schedule = array(
        'min' => '*/15',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $cache = array();
        $objs = FannieAPI::listModules('\COREPOS\Fannie\API\monitor\Monitor');
        $escalate = false;
        $enabled = $this->config->get('MON_ENABLED');
        foreach ($objs as $class) {
            if (is_array($enabled) && !in_array($class, $enabled)) {
                continue;
            }
            $mon = new $class($this->config);
            $cache[$class] = $mon->check();
            $escalate |= $mon->escalate($cache[$class]);
        }

        COREPOS\Fannie\API\data\DataCache::putFile('forever', serialize($cache), 'monitoring');
        if ($escalate) {
            $this->sendEmail($this->emailBody($cache));
        }
    }

    private function sendEmail($msg)
    {
        if (!class_exists('PHPMailer')) {
            $this->logger->error('Cannot send notifications without PHPMailer');
        } else {
            $mail = new PHPMailer();
            $mail->isSMTP();
            $mail->Host = $this->config->get('MON_SMTP_HOST');
            $mail->Port = $this->config->get('MON_SMTP_PORT');
            if ($this->config->get('MON_SMTP_AUTH') === 'Yes') {
                $mail->SMTPAuth = true;
                $mail->Username = $this->config->get('MON_SMTP_USER');
                $mail->Password = $this->config->get('MON_SMTP_PW');
            }
            if ($this->config->get('MON_SMTP_ENC') !== 'None') {
                $mail->SMTPSecure = $this->config->get('MON_SMTP_ENC');
            }
            $mail->From = 'corepos@localhost';
            $mail->FromName = 'CORE POS Monitoring';
            $mail->addAddress($this->config->get('MON_SMTP_ADDR'));
            $mail->Subject = 'CORE POS Alert';
            $mail->Body = $msg;
            if (!$mail->send()) {
                $this->logger->error('Error emailing monitoring notification');
            }
        }
    }

    private function emailBody($cache)
    {
        $ret = '';
        foreach ($cache as $class => $json) {
            $class = new $obj($this->config);
            $ret .= $obj->display($json);
        }

        return $ret;
    }
}

