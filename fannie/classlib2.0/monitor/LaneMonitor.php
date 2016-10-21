<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op, Duluth, MN

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

namespace COREPOS\Fannie\API\monitor;
use COREPOS\Fannie\API\data\Util;
use COREPOS\Fannie\API\lib\FannieUI;

class LaneMonitor extends Monitor
{
    private $check_tables = array(
        'products',
        'custdata',
        'departments',
        'employees',
        'tenders',
    );

    /**
      Returns {
        lane_host => {
            online => boolean,
            connected => boolean,
            tables => {
                table_name => number_of_records,
                ...
            }
        },
        ...
      }
    */
    public function check()
    {
        $ret = array();
        foreach ($this->config->get('LANES') as $lane) {
            // verify lane is online
            $iter = array(
                'online' => Util::checkHost($lane['host'], $lane['type']), 
                'tables' => array(),
            ); 
            // verify lane database is accessible
            $dbc = new \SQLManager($lane['host'], $lane['type'], $lane['op'], $lane['user'], $lane['pw']);
            $iter['connected'] = $dbc->isConnected($lane['op']);
            if ($iter['connected']) {
                // get the number of rows in each table. empty
                // would indicate problems.
                foreach ($this->check_tables as $table) {
                    $iter = $this->checkTable($dbc, $table, $iter);
                }
            }
            $ret[$lane['host']] = $iter;
        }

        return json_encode($ret);
    }

    /**
      Escalate if any lane is offline,
      any lane database is unreachable, or
      any measured lane table is empty
    */
    public function escalate($json)
    {
        $json = json_decode($json, true);

        $offline = array_filter($json, function($item) { return !$item['online']; });
        $no_db = array_filter($json, function($item) { return !$item['connected']; });

        $no_data = array();
        foreach ($json as $lane) {
            $no_data = array_merge($no_data, array_filter($lane, function($item) { return $item == 0; }));
        }

        return (count($offline) !== 0 || count($no_db) !== 0 || count($no_data) !== 0);
    }

    /** get number of rows in table **/
    private function checkTable($dbc, $table, $lane)
    {
        $prep = $dbc->prepare('SELECT COUNT(*) FROM ' . $table);
        $rows = $dbc->getValue($prep, array());
        $lane['tables'][$table] = $rows;

        return $lane;
    }

    /**
      Proof of concept: just dumping out JSON is not ideal
    */
    public function display($json)
    {
        return '<pre>' . FannieUI::prettyJSON($json) . '</pre>';
    }
}

