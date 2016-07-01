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
  @class ArHistoryTodayModel
*/
class ArHistoryTodayModel extends ViewModel
{

    protected $name = "ar_history_today";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'card_no' => array('type'=>'INT'),
    'charges' => array('type'=>'MONEY'),
    'payments' => array('type'=>'MONEY'),
    'tdate' => array('type'=>'DATETIME'),
    'trans_num' => array('type'=>'VARCHAR(50)'),
    );

    public function definition()
    {
        $ar_depts = FannieConfig::config('AR_DEPARTMENTS', '');
        $ret = preg_match_all('/[0-9]+/', $ar_depts, $depts);
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
                SUM(CASE WHEN trans_subtype=\'MI\' THEN -total ELSE 0 END) AS charges,
                SUM(CASE WHEN department IN (' . $inStr . ') THEN total ELSE 0 END) AS payments,
                MAX(tdate) AS tdate,
                trans_num
            FROM dlog
            WHERE (trans_subtype=\'MI\' OR department IN (' . $inStr . '))
                AND ' . $this->connection->datediff('tdate', $this->connection->now()) . ' = 0
            GROUP BY card_no,
                trans_num';
    }

    public function doc()
    {
        return '
Depends on:
* dlog (view)
* AR departments in Fannie config.

Use:
  In-store charge account activity summary for
   the current day.
  Combine with ar_history
   for a "live" view of account status
        ';
    }
}

