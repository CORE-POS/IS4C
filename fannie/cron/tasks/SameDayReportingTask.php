<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

class SameDayReportingTask extends FannieTask
{
    public $name = 'Real-time transaction sync';

    public $description = 'Transfer the current
day\'s transactions into dlog_15. Can be run
repeatedly throughout the day.';

    public $log_start_stop = false;

    public $default_schedule = array(
        'min' => '*/5',
        'hour' => '7-22',
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('TRANS_DB'));
        $lookup = $dbc->prepare('
            SELECT MAX(store_row_id), store_id FROM dlog_15 GROUP BY store_id
        ');
        $res = $dbc->execute($lookup);
        while ($row = $dbc->fetchRow($res)) {
            $rotate = $dbc->prepare('
                INSERT INTO dlog_15
                SELECT * FROM dlog
                WHERE store_row_id > ?
                    AND store_id=?
            ');
            $dbc->execute($rotate, array($row[0], $row[1]));
        }
    }
}

