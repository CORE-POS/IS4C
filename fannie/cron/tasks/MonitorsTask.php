<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op

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

class MonitorsTask extends FannieTask
{
    public $name = 'Monitoring Task';

    public $description = 'Run periodic system monitoring tasks
to assess conditions, generate reports, and populate the dashboard.';

    public $log_start_stop = false;

    public $default_schedule = array(
        'min' => 22,
        'hour' => 3,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $cache = array();
        $objs = FannieAPI::listModules('\COREPOS\Fannie\API\monitor\Monitor');
        $escalate = false;
        foreach ($objs as $class) {
            $mon = new $class($this->config);
            $cache[$class] = $mon->check();
            $escalate |= $mon->escalate($cache[$class]);
        }

        COREPOS\Fannie\API\data\DataCache::putFile('forever', serialize($cache), 'monitoring');
        if ($escalate) {
            echo "OH NO!!!!!\n";
            // send emails
        }
    }
}

