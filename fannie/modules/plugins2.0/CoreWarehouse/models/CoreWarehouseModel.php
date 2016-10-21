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

if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) .'/../../../../classlib2.0/FannieAPI.php');
}

class CoreWarehouseModel extends BasicModel {

    /**
      Reload transaction data for the table
      @param $trans_db Name of Fannie trans_archive database
      @param $start_month start reloading at this month
      @param $start_year see $start_month
      @param $end_month (optional) end reloading at this month
        defaults to current month
      @param $end_year (optional) see $end_month
    */
    public function reload($trans_db,$start_month,$start_year,$end_month=False,$end_year=False){
        if ($end_month === False) $end_month = date('n');
        if ($end_year === False) $end_year = date('Y');

        while($start_year <= $end_year){
            if ($start_year == $end_year && $start_month > $end_month)
                break; // done processing
            if (php_sapi_name() == 'cli'){
                echo 'Processing '.date('F',mktime(0,0,0,$start_month)).', '.$start_year."\n";
            }
            $this->refresh_data($trans_db, $start_month, $start_year);
            $start_month += 1;
            if ($start_month > 12){
                $start_year += 1;
                $start_month = 1;
            }
        }
    }

    /**
      Reload data for a specific month or day
      Subclasses should override
      @param $trans_db Name of Fannie trans_archive database
      @param $month the month
      @param $year the year
      @param $day (optional) if omitted reload entire month 
    */
    public function refresh_data($trans_db, $month, $year, $day=False){

    }

    protected function dates($month, $year, $day=false)
    {
        $start_id = date('Ymd',mktime(0,0,0,$month,1,$year));
        $start_date = date('Y-m-d',mktime(0,0,0,$month,1,$year));
        $end_id = date('Ymt',mktime(0,0,0,$month,1,$year));
        $end_date = date('Y-m-t',mktime(0,0,0,$month,1,$year));
        if ($day !== false){
            $start_id = date('Ymd',mktime(0,0,0,$month,$day,$year));
            $start_date = date('Y-m-d',mktime(0,0,0,$month,$day,$year));
            $end_id = $start_id;
            $end_date = $start_date;
        }

        return array($start_id, $start_date, $end_id, $end_date);
    }

    protected function clearDates($sql, $start_id, $end_id)
    {
        /* clear old entries */
        $sql = 'DELETE FROM '.$this->name.' WHERE date_id BETWEEN ? AND ?';
        $prep = $this->connection->prepare($sql);
        return $this->connection->execute($prep, array($start_id, $end_id));
    }

}

