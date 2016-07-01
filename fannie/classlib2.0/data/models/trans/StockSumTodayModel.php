<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
  @class StockSumTodayModel
*/
class StockSumTodayModel extends ViewModel
{

    protected $name = "stockSumToday";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'card_no' => array('type'=>'INT'),
    'totPayments' => array('type'=>'MONEY'),
    'startdate' => array('type'=>'DATETIME'),
    );

    public function definition()
    {
        $eq_depts = FannieConfig::config('EQUITY_DEPARTMENTS', '');
        $ret = preg_match_all('/[0-9]+/', $eq_depts, $depts);
        if ($ret == 0) {
            $depts = array(-999);
        } else {
            $depts = array_pop($depts);
        }

        $inStr = '';
        foreach ($depts as $d) {
            $inStr .= sprintf('%d,', $d);
        }
        $inStr = substr($inStr, 0, strlen($inStr)-1);

        return '
            SELECT card_no,
                SUM(CASE WHEN department IN (' . $inStr . ') THEN total ELSE 0 END) AS totPayments,
                MIN(tdate) AS startdate
            FROM dlog
            WHERE department IN (' . $inStr . ')
                AND ' . $this->connection->datediff('tdate', $this->connection->now()) . ' = 0
            GROUP BY card_no';
    }

    public function doc()
    {
        return '
Depends on:
* dlog (view)

Use:
This view lists equity activity
for the current day. It exists to 
calculate balances in real time.

The view\'s construction depends on Fannie\'s
Equity Department configuration
        ';
    }
}

