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

use \COREPOS\Fannie\API\data\SyncLanes as SyncLanes;

class LaneSyncTask extends FannieTask
{
    public $name = 'Lane Data Sync';

    public $description = 'Copy operational data tables to the lanes.

Replaces nightly.lanesync.php and/or lanesync.api.php';

    public $default_schedule = array(
        'min' => 0,
        'hour' => 4,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    private $regularPushTables = array(
        'products',
        'custdata',
        'memberCards',
        'custReceiptMessage',
        'CustomerNotifications',
        'employees',
        'departments',
        'houseCoupons',
        'houseCouponItems',
        'houseVirtualCoupons',
        'custPreferences',
        'taxrates',
    );

    public function run()
    {
        set_time_limit(0);

        if ($this->config->get('COMPOSE_LONG_PRODUCT_DESCRIPTION') == true) {
            $this->regularPushTables[] = 'productUser';
        }
        if ($this->config->get('COOP_ID') == 'WEFC_Toronto') {
            $this->regularPushTables[] = 'tenders';
        }
        if ($this->test_mode) {
            $this->regularPushTables = array('houseCoupons');
        }
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        if ($dbc->tableExists('EWicItems')) {
            $this->regularPushTables[] = 'EWicItems';
        }
        foreach ($this->regularPushTables as $table) {
            $result = SyncLanes::pushTable("$table", 'op', SyncLanes::TRUNCATE_DESTINATION);
            /**
            @severity: error message may indicate lane down or connectivity problem
            */
            $severity = strstr($result['messages'], 'Error:') ? FannieLogger::CRITICAL : FannieLogger::INFO;
            $this->cronMsg($result['messages'], $severity);
        }
    }
}

