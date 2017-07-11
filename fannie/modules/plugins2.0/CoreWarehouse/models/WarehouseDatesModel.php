<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

    This file is part of CORE-POS.

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

/**
  @class WarehouseDatesModel
*/
class WarehouseDatesModel extends CoreWarehouseModel
{
    protected $name = "WarehouseDates";
    protected $preferred_db = 'plugin:WarehouseDatabase';

    protected $columns = array(
    'warehouseDateID' => array('type'=>'INT', 'primary_key'=>true),
    'year' => array('type'=>'SMALLINT'),
    'month' => array('type'=>'TINYINT'),
    'day' => array('type'=>'TINYINT'),
    'fiscalYear' => array('type'=>'SMALLINT'),
    'isoWeekNumber' => array('type'=>'TINYINT'),
    'calendarQuarter' => array('type'=>'TINYINT'),
    'fiscalQuarter' => array('type'=>'TINYINT'),
    'dayOfWeek' => array('type'=>'TINYINT'),
    'holiday' => array('type'=>'TINYINT', 'default'=>0),
    'limitedHours' => array('type'=>'TINYINT', 'default'=>0),
    'expandedHours' => array('type'=>'TINYINT', 'default'=>0),
    'weather' => array('type'=>'VARCHAR(255)'),
    );

    public function reload($trans_db,$start_month,$start_year,$end_month=False,$end_year=False)
    {
        if (!$end_month) {
            $end_month = $start_month;
        }
        if (!$end_year) {
            $end_year = $start_year;
        }
        $startTS = mktime(0, 0, 0, $start_month, 1, $start_year);
        $endTS = mktime(0, 0, 0, $end_month, 1, $end_year);
        while ($startTS <= $endTS) {
            $this->refresh_data($trans_db, date('n', $startTS), date('Y', $startTS));
            $startTS = mktime(0, 0, 0, date('n', $startTS)+1, 1, date('Y', $startTS));
        }
    }

    public function refresh_data($trans_db, $month, $year, $day=False)
    {
        if ($day) {
            $str = date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
            $this->initDay($str);
        } else {
            $ts = mktime(0, 0, 0, $month, 1, $year);
            while (date('n', $ts) == $month && date('Y', $ts) == $year) {
                $str = date('Y-m-d', $ts);
                $this->initDay($str);
                $ts = mktime(0, 0, 0, date('n',$ts), date('j', $ts)+1, date('Y', $ts));
            }
        }
    }

    private function initDay($datestr)
    {
        echo "Reloading $datestr\n";
        if (!strtotime($datestr)) {
            return false;
        }
        $ts = strtotime($datestr);
    
        $obj = new WarehouseDatesModel($this->connection);
        $obj->warehouseDateID(date('Ymd', $ts));
        $obj->year(date('Y', $ts));
        $obj->month(date('n', $ts));
        $obj->day(date('j', $ts));
        $obj->dayOfWeek(date('N', $ts));
        $obj->isoWeekNumber(date('W', $ts));

        switch (date('n', $ts)) {
            case 1:
            case 2:
            case 3:
                $obj->calendarQuarter(1);
                $obj->fiscalYear($obj->year());
                $obj->fiscalQuarter(3);
                break;
            case 4:
            case 5:
            case 6:
                $obj->calendarQuarter(2);
                $obj->fiscalYear($obj->year());
                $obj->fiscalQuarter(4);
                break;
            case 7:
            case 8:
            case 9:
                $obj->calendarQuarter(3);
                $obj->fiscalYear($obj->year()+1);
                $obj->fiscalQuarter(1);
                break;
            case 10:
            case 11:
            case 12:
                $obj->calendarQuarter(4);
                $obj->fiscalYear($obj->year()+1);
                $obj->fiscalQuarter(2);
                break;
        }

        if ($obj->save()) {
            return true;
        } else {
            return false;
        }
    }
}

