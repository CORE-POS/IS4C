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

class InitLastSold extends FannieTask
{
    public $name = 'Initialize Product Last-Sold';

    public $description = 'Scans entire sales history
    for the most recent time an item was sold.';

    public $schedulable = false;

    public $default_schedule = array(
        'min' => 45,
        'hour' => 3,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $p_def = $dbc->tableDefinition('products');
        if (!isset($p_def['last_sold'])) {
            $this->logger->warning('products table does not have a last_sold column');
            return;
        }

        $update = $dbc->prepare('
            UPDATE products
            SET last_sold=?
            WHERE upc=?');

        // really old date to ensure we get the whole history
        $dlog = DTransactionsModel::selectDlog('1950-01-01', date('Y-m-d'));

        $missingR = $dbc->query('
            SELECT upc
            FROM products
            WHERE last_sold IS NULL');
        /**
          Lookup each transaction containing the UPC instead of
          just MAX(tdate) over the time period. This is to adjust
          for voids. We want the lastest transaction where the item
          had a non-zero total.
        */
        $lastSoldP = $dbc->prepare('
            SELECT upc,
                YEAR(tdate),
                MONTH(tdate),
                DAY(tdate),
                trans_num,
                MAX(tdate) AS last_sold
            FROM ' . $dlog . '
            WHERE trans_type=\'I\'
                AND upc=?
            GROUP BY YEAR(tdate),
                MONTH(tdate),
                DAY(tdate),
                trans_num,
                upc
            HAVING SUM(total) <> 0
            ORDER BY tdate
            ');
        while ($missingW = $dbc->fetchRow($missingR)) {
            echo "Scanning sales for {$missingW['upc']}\n";
            $res = $dbc->execute($lastSoldP, $missingW['upc']);
            while ($w = $dbc->fetchRow($res)) {
                $dbc->execute($update, array($w['last_sold'], $w['upc']));
            }
        }
    }
}

