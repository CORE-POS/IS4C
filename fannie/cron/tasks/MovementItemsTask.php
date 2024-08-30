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
if (!class_exists('BasicModel')) {
    include(dirname(__FILE__).'/../../classlib2.0/data/models/BasicModel.php');
}
if (!class_exists('ProdUpdateModel')) {
    include(dirname(__FILE__).'/../../classlib2.0/data/models/op/ProdUpdateModel.php');
}

class MovementItemsTask extends FannieTask
{
    public $name = 'Movement Tag Initialization';

    public $description = 'Initialize movement control for applicable items.';

    public $default_schedule = array(
        'min' => 45,
        'hour' => 4,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function getMissing()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $storeIDs = $this->getStoreIDs();
        $upcs = array();

        foreach ($storeIDs as $storeID) {
            $args = array($storeID, $storeID);
            $prep = $dbc->prepare("
                SELECT upc
                FROM products AS p 
                    LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                WHERE m.superID IN (1, 4, 5, 6, 8, 9, 13, 17, 18) 
                    AND upc NOT IN (SELECT upc FROM MovementTags WHERE storeID = ?)
                    AND p.store_id = ? 
                GROUP by p.upc;
            ");
            $res = $dbc->execute($prep, $args);
            while ($row = $dbc->fetchRow($res)) {
                $upc = $row['upc'];
                $upcs[$upc] = $upc;
            }
        }

        return $upcs;

    }
    
    public function getStoreIDs()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $storeIDs = array();

        $storesP = $dbc->prepare("SELECT storeID FROM Stores WHERE hasOwnItems = 1;");
        $storesR = $dbc->execute($storesP);
        while ($row = $dbc->fetchRow($storesR)) {
            $storeIDs[] = $row['storeID'];
        }

        return $storeIDs;
    }

    public function run()
    {

        $start = time();
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $storeIDs = $this->getStoreIDs();

        $upcs = $this->getMissing();
        $dbc->startTransaction();
        foreach ($upcs as $upc) {
            foreach ($storeIDs as $storeID) {
                $args = array($upc, $storeID);
                $prep = $dbc->prepare("INSERT IGNORE INTO MovementTags (upc, storeID, lastPar, modified) VALUES (?, ?, 0.00, NOW())");
                $dbc->execute($prep, $args);
            }
        }
        $dbc->commitTransaction();

        return false;

    }


}
