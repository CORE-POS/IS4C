<?php
/*******************************************************************************

    Copyright 2021 Whole Foods Co-op

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

if (!function_exists('check_db_host')) {
    include(dirname(__FILE__).'/../../install/util.php');
}

class CheckLanesTask extends FannieTask
{
    public $name = "Check Lane Connections";

    public $description = "Checks actual connection status for each lane,\n"
                        . "and updates server config as needed.";

    public $default_schedule = array(
        'min' => '*/15',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        // we will only update config if any lanes change status
        $pendingUpdate = false;

        // loop thru all defined lanes
        $lanes = $this->config->get('LANES');
        $timeout = 2;
        foreach ($lanes as $i => $lane) {
            $number = $i + 1;
            $this->cronMsg("testing lane $number ({$lane['host']}) ...");

            // report whether fannie "thought" lane was online
            $supposedStatus = $lane['offline'] ? "offline" : "online";
            $this->cronMsg("according to Fannie, lane $number is currently $supposedStatus");

            // assume lane is offline unless proven otherwise
            $online = false;
            if (check_db_host($lane['host'], $lane['type'], $timeout)) {
                $online = true;
            }

            // report actual status
            $actualStatus = $online ? "online" : "offline";
            $this->cronMsg("in reality, lane $number is currently $actualStatus");

            if ($supposedStatus == $actualStatus) {
                $this->cronMsg("Fannie was right about lane $number, so nothing to do");

            } else {
                // flag lane for pending update
                $lanes[$i]['offline'] = $online ? 0 : 1;
                $pendingUpdate = true;
                $this->cronMsg("Fannie is wrong about lane $number, so will update config at the end");
            }
        }

        if ($pendingUpdate) {
            $this->cronMsg("will now attempt to update config", FannieLogger::DEBUG);
            update_lanes($lanes);
            $this->cronMsg("Fannie should now have correct status for all lanes");
        } else {
            $this->cronMsg("no lanes changed, so skipping config update", FannieLogger::DEBUG);
        }
    }
}
