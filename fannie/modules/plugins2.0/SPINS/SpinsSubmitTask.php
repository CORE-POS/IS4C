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
class SpinsSubmitTask extends FannieTask 
{
    public $name = 'Submit SPINS data';

    public $description = 'Submits weekly sales data to SPINS. SPINS plugin must be configured
    with proper FTP credentials';

    public $default_schedule = array(
        'min' => 0,
        'hour' => 4,
        'day' => '*',
        'month' => '*',
        'weekday' => '2',
    );

    public function run()
    {
        global $argv, $FANNIE_OP_DB, $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $iso_week = date('W');
        $iso_week--;
        $year = date('Y');
        if ($iso_week <= 0) {
            $iso_week = 52;
            $year--;
        }
        $upload = true;

        /**
          Handle additional args
        */
        if (isset($argv) && is_array($argv)) {
            foreach($argv as $arg) {
                if (is_numeric($arg)) {
                    $iso_week = $arg;
                } else if ($arg == '--file') {
                    $upload = false;
                }
            }
        }

        /**
          Keep SPINS week number separate for logging purposes
        */
        $spins_week = $iso_week;
        if (isset($FANNIE_PLUGIN_SETTINGS['SpinsOffset'])) {
            $iso_week += $FANNIE_PLUGIN_SETTINGS['SpinsOffset'];
        }

        // First day of ISO week is a Monday
        $start = strtotime($year . 'W' . str_pad($iso_week, 2, '0', STR_PAD_LEFT));
        // if the SpinsOffset results in non-existant week 0, 
        // use ISO week 1 and go back seven days
        if ($iso_week == 0) {
            $start = strtotime($year . 'W01');
            $start = mktime(0, 0, 0, date('n', $start), date('j',$start)-7, date('Y', $start));
        }
        // walk forward to Sunday
        $end = $start;
        while (date('w', $end) != 0) {
            $end = mktime(0,0,0,date('n',$end),date('j',$end)+1,date('Y',$end));
        }

        $dlog = DTransactionsModel::selectDlog(date('Y-m-d', $start), date('Y-m-d',$end));

        $lastDay = date("M d, Y", $end) . ' 11:59PM'; 

        $this->cronMsg('SPINS data for week #' . $spins_week . '(' . date('Y-m-d', $start) . ' to ' . date('Y-m-d', $end) . ')', FannieLogger::INFO);

        // Odd "CASE" statement is to deal with special order
        // line items the have case size & number of cases
        $dataQ = "SELECT d.upc, p.description,
                    SUM(CASE WHEN d.quantity <> d.ItemQtty AND d.ItemQtty <> 0 THEN d.quantity*d.ItemQtty ELSE d.quantity END) as quantity,
                    SUM(d.total) AS dollars,
                    '$lastDay' AS lastDay
                  FROM $dlog AS d
                    INNER JOIN products AS p ON d.upc=p.upc
                  WHERE p.Scale = 0
                    AND d.upc > '0000000999999' 
                    AND tdate BETWEEN ? AND ?
                  GROUP BY d.upc, p.description";

        $filename = date('mdY', $end) . '.csv';
        $outfile = sys_get_temp_dir()."/".$filename;
        $fp = fopen($outfile,"w");

        $dataP = $dbc->prepare($dataQ);
        $args = array(date('Y-m-d 00:00:00', $start), date('Y-m-d 23:59:59', $end));
        $dataR = $dbc->execute($dataP, $args);
        while($row = $dbc->fetch_row($dataR)){
            for($i=0;$i<4; $i++){
                if ($i==2 || $i==3) {
                    $row[$i] = sprintf('%.2f', $row[$i]);
                }
                fwrite($fp,"\"".$row[$i]."\",");
            }
            fwrite($fp,"\"".$row[4]."\"\n");
        }
        fclose($fp);

        if ($upload) {
            $conn_id = ftp_connect('ftp.spins.com');
            $login_id = ftp_login($conn_id, $FANNIE_PLUGIN_SETTINGS['SpinsFtpUser'], $FANNIE_PLUGIN_SETTINGS['SpinsFtpPw']);
            if (!$conn_id || !$login_id) {
                $this->cronMsg('FTP Connection failed', FannieLogger::ERROR);
            } else {
                ftp_chdir($conn_id, "data");
                ftp_pasv($conn_id, true);
                $uploaded = ftp_put($conn_id, $filename, $outfile, FTP_ASCII);
                if (!$uploaded) {
                    $this->cronMsg('FTP upload failed', FannieLogger::ERROR);
                } else {
                    $this->cronMsg('FTP upload successful', FannieLogger::INFO);
                }
                ftp_close($conn_id);
            }
            unlink($outfile);
        } else {
            rename($outfile, './' . $filename);    
            $this->cronMsg('Generated file: ' . $filename, FannieLogger::INFO);
        }
    }
}

