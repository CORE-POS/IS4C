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
        // url for updating lane status in fannie
        $url = "http://{$this->config->get('HTTP_HOST')}{$this->config->get('URL')}admin/LaneStatus.php";

        // loop thru all defined lanes
        $number = 1;
        foreach ($this->config->get('LANES') as $lane) {
            $this->cronMsg("testing lane $number ({$lane['host']}) ...");

            // report whether fannie "thought" lane was online
            $supposedStatus = $lane['offline'] ? "offline" : "online";
            $this->cronMsg("according to Fannie, lane $number is currently $supposedStatus");

            // assume lane is offline unless proven otherwise
            $online = false;
            $dbc = new SQLManager($lane['host'],
                                  $lane['type'],
                                  $lane['op'],
                                  $lane['user'],
                                  $lane['pw']);
            if ($dbc->isConnected()) {
                $online = true;
            }

            // report actual status
            $actualStatus = $online ? "online" : "offline";
            $this->cronMsg("in reality, lane $number is currently $actualStatus");

            if ($supposedStatus == $actualStatus) {
                $this->cronMsg("Fannie was right about lane $number, so nothing to do");

            } else {
                // must update fannie to reflect reality
                $data = array('id' => $number, 'up' => $online);

                // but..this will only work if Fannie Authentication is *disabled*
                if ($this->config->get('AUTH_ENABLED')) {
                    // TODO: probably need to figure out how to do this someday
                    $this->cronMsg("Fannie authentication is enabled, which means we CANNOT update its lane status",
                                   FannieLogger::WARNING);

                } else {
                    $this->cronMsg("will POST to $url: " . print_r($data, true),
                                   FannieLogger::DEBUG);

                    // TODO: some error handling might be nice here...
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // suppress output
                    curl_exec($ch);
                    curl_close($ch);

                    $this->cronMsg("Fannie status for lane $number should now be correct");
                }
            }

            $number++;
        }
    }
}
