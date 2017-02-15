<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

class ReportDataCacheTask extends FannieTask
{

    public $name = 'Cache Report Data';

    public $description = 'Certain queries are cached on a regular
basis to make reports perform faster.

Replaces nightly.tablecache.php';

    public $default_schedule = array(
        'min' => 0,
        'hour' => 5,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        global $FANNIE_OP_DB, $FANNIE_ARCHIVE_DB;
        $sql = FannieDB::get($FANNIE_OP_DB);

        $chk = $sql->query('DELETE FROM shelftags WHERE id < 0');

        $this->recacheCashierPerformance();

        $sql = FannieDB::get($FANNIE_ARCHIVE_DB);

        if ($sql->table_exists("reportDataCache")){
            $sql->query("DELETE FROM reportDataCache WHERE expires < ".$sql->now());
        }

        $daily = \COREPOS\Fannie\API\data\DataCache::fileCacheDir('daily');
        if ($daily) {
            $this->clearFileCache($daily);
        }

        $monthly = \COREPOS\Fannie\API\data\DataCache::fileCacheDir('monthly');
        if ($monthly && date('j') == 1) {
            $this->clearFileCache($monthly);
        }
    }

    private function recacheCashierPerformance()
    {
        $sql = FannieDB::get(FannieConfig::config('TRANS_DB'));

        $cashierPerformanceSQL = "
            SELECT
            min(tdate) as proc_date,
            max(emp_no) as emp_no,
            max(trans_num) as Trans_Num,
            min(tdate) as startTime,
            max(tdate) as endTime,
            CASE WHEN ".$sql->seconddiff('min(tdate)', 'max(tdate)')." =0 
                then 1 else 
                ".$sql->seconddiff('min(tdate)', 'max(tdate)') ."
            END as transInterval,
            sum(CASE WHEN abs(quantity) > 30 THEN 1 else abs(quantity) END) as items,
            Count(upc) as rings,
            SUM(case when trans_status = 'V' then 1 ELSE 0 END) AS Cancels,
            max(card_no) as card_no
            from dlog_90_view 
            where trans_type IN ('I','D','0','C')
            group by year(tdate),month(tdate),day(tdate),trans_num";

        if (!$sql->isView('CashPerformDay')) {
            $chk = $sql->query("TRUNCATE TABLE CashPerformDay");
            if ($chk === false) {
                $this->cronMsg("Could not truncate CashPerformDay", FannieLogger::WARNING);
            }
            $chk = $sql->query("INSERT INTO CashPerformDay " . $cashierPerformanceSQL);
            if ($chk === false) {
                $this->cronMsg("Could not load data for CashPerformDay", FannieLogger::WARNING);
            }
        }
        if ($sql->tableExists('CashPerformDay_cache')) {
            $chk = $sql->query("TRUNCATE TABLE CashPerformDay_cache");
            if ($chk === false) {
                $this->cronMsg("Could not truncate CashPerformDay_cache", FannieLogger::WARNING);
            }
            $chk = $sql->query("INSERT INTO CashPerformDay_cache " . $cashierPerformanceSQL);
            if ($chk === false) {
                $this->cronMsg("Could not load data for CashPerformDay_cache", FannieLogger::WARNING);
            }
        }
    }

    private function clearFileCache($path)
    {
        $dir = opendir($path);
        while ( ($file = readdir($dir)) !== false) {
            if (is_file($path . '/' . $file)) {
                unlink($path . '/' . $file);
            }
        }
        closedir($dir);
    }
}

