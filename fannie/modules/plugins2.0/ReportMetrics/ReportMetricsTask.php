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
        'min' => 50,
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

        foreach ($this->get('LANES', array()) as $lane) {
            $sql = new SQLManager($lane['host'],$lane['type'],$lane['op'],
                        $lane['user'],$lane['pw']);    
            if (!is_object($sql) || $sql->connections[$lane['op']] === false) {
                $msg .= 'OFFLINE ' . $lane['host'] . "\n";
            } else {
                $msg .= 'ONLINE ' . $lane['host'] . "\n";
            }
        }
        $msg .= "\n" . 'Lane activity' . "\n";

        $dbc = FannieDB::get($this->config->get('TRANS_DB)'));
        $res = $dbc->query('
            SELECT register_no,
                COUNT(*) as activity
            FROM dlog
            GROUP BY register_no
            ORDER by register_no');
        while ($w = $dbc->fetchRow($res)){
            $msg .= 'Lane #' . $w['register_no'] . ', ' . $w['activity'] . "\n";
        }

        $LOG_MAX = 100;
        $msg .= "\nLog Entries:\n";
        $logs = array();
        $fp = fopen(dirname(__FILE__) . '/../../log/fannie.log', 'r');
        while (($line=fgets($fp)) !== false) {
            if (count($logs) >= $LOG_MAX) {
                array_shift($logs);
            }
            $logs[] = $line;
        }
        fclose($fp);
        foreach ($logs as $l) {
            $msg .= $l . "\n";
        }

        $msg .= "\nError Entries:\n";
        $logs = array();
        $fp = fopen(dirname(__FILE__) . '/../../log/debug_fannie.log', 'r');
        while (($line=fgets($fp)) !== false) {
            if (count($logs) >= $LOG_MAX) {
                array_shift($logs);
            }
            $logs[] = $line;
        }
        fclose($fp);
        foreach ($logs as $l) {
            $msg .= $l . "\n";
        }

        mail($settings['ReportMetricsEmail'], 'CORE Metrics', $msg);
    }
}


