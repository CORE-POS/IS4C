<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
class ReportMetricsTask extends FannieTask 
{
    public $name = 'E-mail system status';

    public $description = 'Reports overall health via email';

    public $default_schedule = array(
        'min' => 45,
        'hour' => 23,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $msg = '';
        if (!isset($settings['ReportMetricsEmail'])) {
            return false;
        }

        $free = disk_free_space(dirname(__FILE__));
        $total = disk_total_space(dirname(__FILE__));
        $msg .= sprintf('Disk space available: %d/%d (%.2f)%%', $free, $total, ($free/$total)*100) 
            . "\n\n";

        $msg .= 'Lane status' . "\n";
        foreach ($this->config->get('LANES', array()) as $lane) {
            $sql = new SQLManager($lane['host'],$lane['type'],$lane['op'],
                        $lane['user'],$lane['pw']);    
            if (!$sql->isConnected($lane['op'])) {
                $msg .= 'OFFLINE ' . $lane['host'] . "\n";
            } else {
                $msg .= 'ONLINE ' . $lane['host'] . "\n";
            }
        }
        $msg .= "\n" . 'Lane activity' . "\n";

        $dbc = FannieDB::get($this->config->get('TRANS_DB'));
        $res = $dbc->query('
            SELECT register_no,
                COUNT(*) as activity
            FROM dlog
            GROUP BY register_no
            ORDER by register_no');
        while ($w = $dbc->fetchRow($res)){
            $msg .= 'Lane #' . $w['register_no'] . ', ' . $w['activity'] . " records\n";
        }
        $msg .= "\n";

        $dbc->selectDB($this->config->get('OP_DB'));
        $res = $dbc->query('
            SELECT COUNT(*) AS total,
                COUNT(DISTINCT userHash) AS users,
                COUNT(DISTINCT ipHash) AS hosts
            FROM usageStats
            WHERE tdate >= \'' . date('Y-m-d') . '\'');
        $row = $dbc->fetchRow($res);
        $msg .= 'Pages served: ' . $row['total'] . "\n";
        $msg .= 'Unique users: ' . $row['users'] . "\n";
        $msg .= 'Unique IPs: ' . $row['hosts'] . "\n";
        $res = $dbc->query('
            SELECT COUNT(*) AS total,
                pageName
            FROM usageStats
            WHERE tdate >= \'' . date('Y-m-d') . '\'
            GROUP BY pageName
            ORDER BY COUNT(*) DESC');
        $msg .= 'Most popular pages: ' . "\n";
        $page_list = 0;
        while ($w = $dbc->fetchRow($res)) {
            $msg .= $w['total'] . ' ' . $w['pageName'] . "\n";
            $page_list++;
            if ($page_list > 9) {
                break;
            }
        }
        $msg .= "\n";

        $LOG_MAX = 100;
        $syslog_date = date('M j ');
        $msg .= "\nLog Entries:\n";
        $logs = $this->tail(dirname(__FILE__) . '/../../../logs/fannie.log', $LOG_MAX);
        foreach ($logs as $l) {
            if (substr($l, 0, strlen($syslog_date)) != $syslog_date) {
                continue;
            }
            $msg .= $l . "\n";
        }

        $msg .= "\nError Entries:\n";
        $logs = array();
        $logs = $this->tail(dirname(__FILE__) . '/../../../logs/debug_fannie.log', $LOG_MAX);
        foreach ($logs as $l) {
            if (substr($l, 0, strlen($syslog_date)) != $syslog_date) {
                continue;
            }
            $msg .= $l . "\n";
        }

        if (class_exists('PHPMailer')) {
            $mail = new PHPMailer();
            $mail->isSMTP();
            $mail->Host = '127.0.0.1';
            $mail->Port = 25;
            if ($settings['ReportMetricsSmtpUser'] && $settings['ReportMetricsSmtpPass']) {
                $mail->SMTPAuth = true;
                $mail->Username = $settings['ReportMetricsSmtpUser'];
                $mail->Password = $settings['ReportMetricsSmtpPass'];
            } else {
                $mail->SMTPAuth = false;
            }
            if ($settings['ReportMetricsSmtpEnc']) {
                $mail->SMTPSecure = strtolower($settings['ReportMetricsSmtpEnc']);
            }
            $mail->From = 'report-metrics-task@wholefoods.coop';
            $mail->FromName = 'CORE Metrics Report';
            $mail->addReplyTo('no-reply@wholefoods.coop');
            $mail->addAddress($settings['ReportMetricsEmail']);
            $mail->isHTML(false);
            $mail->Subject = 'CORE Metrics';
            $mail->Body = $msg;
            $mail->send();
        } else {
            mail($settings['ReportMetricsEmail'], 'CORE Metrics', $msg);
        }
    }

    private function tail($filename, $num=500)
    {
        $lines = 0;
        $data = "";
        $fp = fopen($filename, 'r');
        if (!$fp) {
            return array('Could not find ' . $filename);
        }
        fseek($fp, 0, SEEK_END);
        $pos = -1;
        while ($lines < $num && ftell($fp)) {
            $char = fgetc($fp);
            if ($char == "\r") {
                $char = '';
            }
            if ($char == "\n") {
                $lines++;
            }
            $data = $char . $data;
            fseek($fp, $pos, SEEK_END);
            $pos--;
        }

        return explode("\n", $data);
    }
}


