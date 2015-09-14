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

class PatronageCheckTask extends FannieTask
{

    public $name = 'Patronage Checks';

    public $description = 'Review recent transactions to locate rebate
    checks used as tender in-store. Mark those checks as "cashed" in
    patronage information.';

    public $default_schedule = array(
        'min' => 30,
        'hour' => 1,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        if (!$dbc->isConnected()) {
            $this->cronMsg('No database connection', FannieLogger::ALERT);
            return false;
        }

        $query = "
            SELECT MAX(tdate) AS tdate,
                card_no
            FROM " . $FANNIE_TRANS_DB . $dbc->sep() . "dlog_15
            WHERE trans_type='T'
                AND description='REBATE CHECK'
            GROUP BY card_no
            HAVING SUM(total) <> 0";
        $result = $dbc->query($query);

        $findP = $dbc->prepare('
            SELECT FY 
            FROM patronage
            WHERE cardno=?
                AND check_number IS NOT NULL
                AND check_number <> 0
            ORDER BY FY DESC'); 
        $markP = $dbc->prepare('
            UPDATE patronage
            SET cashed_date = ?,
                cashed_here = 1
            WHERE cardno = ?
                AND FY = ?');
        while ($row = $dbc->fetch_row($result)) {
            $findR = $dbc->execute($findP, array($row['card_no']));
            while ($findW = $dbc->fetch_row($findR)) {
                $args = array($row['tdate'], $row['card_no'], $findW['FY']);
                $markR = $dbc->execute($markP, $args);
                break; // only mark one check as cashed
            }
        }
    }
}

