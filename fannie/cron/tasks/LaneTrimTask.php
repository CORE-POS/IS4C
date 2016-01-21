<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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

class LaneTrimTask extends FannieTask
{
    public $name = 'Trim Lane History';

    public $description = 'Removes old data from lane-side
transaction tables. Anything more than 30 days old gets
deleted. This is probably no longer strictly necessary.
There might be a performance impact if lane-side transaction
data builds up continually for years.
Deprecates lanes.clean.php.';

    public $default_schedule = array(
        'min' => 30,
        'hour' => 3,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        set_time_limit(0);

        foreach ($this->config->get('LANES') as $ln) {

            $sql = new SQLManager($ln['host'],$ln['type'],$ln['trans'],$ln['user'],$ln['pw']);
            if ($sql === false || !$sql->isConnected()) {
                $this->cronMsg("Could not connect to lane: ".$ln['host']);
                continue;
            }

            $table = 'localtrans_today';
            $cleanQ = "DELETE FROM $table WHERE ".$sql->datediff($sql->now(),'datetime')." <> 0";
            $cleanR = $sql->query($cleanQ,$ln['trans']);
            if ($cleanR === false) {
                $this->cronMsg("Could not clean $table on lane: ".$ln['host']);
            }

            $table = 'localtranstoday';
            $cleanQ = "DELETE FROM $table WHERE ".$sql->datediff($sql->now(),'datetime')." <> 0";
            $cleanR = $sql->query($cleanQ,$ln['trans']);
            if ($cleanR === false) {
                $this->cronMsg("Could not clean $table on lane: ".$ln['host']);
            }

            $table = 'localtrans';
            $cleanQ = "DELETE FROM $table WHERE ".$sql->datediff($sql->now(),'datetime')." > 30";
            $cleanR = $sql->query($cleanQ,$ln['trans']);
            if ($cleanR === false) {
                $this->cronMsg("Could not clean $table on lane: ".$ln['host']);
            }
        }
    }
}

