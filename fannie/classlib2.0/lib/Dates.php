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

namespace COREPOS\Fannie\API\lib;
use \FannieConfig;
use \DateTime;

class Dates
{
    public static function lastWeek()
    {
        $week_start = (!FannieConfig::config('FANNIE_WEEK_START')) ? 1 :  FannieConfig::config('FANNIE_WEEK_START');
        $week_end = $week_start == 1 ? 7 : $week_start-1;
        $day_name = strtolower(self::dayName($week_end));
        $sunday = strtotime('last ' . $day_name);
        $monday = mktime(0, 0, 0, date('n', $sunday), date('j',$sunday)-6, date('Y', $sunday));

        return array(date('Y-m-d', $monday), date('Y-m-d', $sunday));
    }

    public static function dayName($num)
    {
        $date = new DateTime();
        for ($i=0; $i<7; $i++) {
            $date->modify('+1 day');
            if ($date->format('N') == $num) {
                return $date->format('l');
            }
        }

        return 'octoday';
    }
}

