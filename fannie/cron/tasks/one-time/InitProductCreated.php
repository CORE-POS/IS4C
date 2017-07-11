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

class InitProductCreated extends FannieTask
{
    public $name = 'Initialize Product Created';

    public $description = 'Scans product update logs
    to locate the oldest known change to an item. If no
    update history is found created will still be initialized
    to the current date.';

    public $schedulable = false;

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $p_def = $dbc->tableDefinition('products');
        if (!isset($p_def['created'])) {
            echo 'products table does not have a created column' . PHP_EOL;
            return;
        }

        $update = $dbc->prepare('
            UPDATE products
            SET created=?
            WHERE upc=?
                AND store_id=?');

        $log1P = $dbc->prepare('
            SELECT MIN(modified) AS created
            FROM prodUpdate
            WHERE modified IS NOT NULL
                AND upc=?
                AND storeID=?');

        $log2P = $dbc->prepare('
            SELECT MIN(modified) AS created
            FROM prodUpdate
            WHERE modified IS NOT NULL
                AND upc=?');

        $missingR = $dbc->query('
            SELECT upc, store_id
            FROM products
            WHERE created IS NULL');
        $items = $dbc->numRows($missingR);
        $dbc->startTransaction();
        /**
         * Set created to (in order of preference)
         * 1. Oldest update record matching UPC and storeID
         * 2. Oldest update record matching just UPC
         * 3. Current date & time
         */
        $count = 1;
        while ($missingW = $dbc->fetchRow($missingR)) {
            $upc = $missingW['upc'];
            $store = $missingW['store_id'];
            $created = $dbc->getValue($log1P, array($upc, $store));
            if ($created === false || $created === null) {
                $created = $dbc->getValue($log1P, array($upc));
                if ($created === false || $created === null) {
                    $created = date('Y-m-d H:i:s');
                }
            }
            echo $upc . '-' . $created . PHP_EOL;
            $dbc->execute($update, array($created, $upc, $store));
            $count++;
        }
        $dbc->commitTransaction();
    }
}

