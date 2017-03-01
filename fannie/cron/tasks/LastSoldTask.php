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

class LastSoldTask extends FannieTask
{
    public $name = 'Product Last-Sold Maintenance';

    public $description = 'Scans recent transactions for UPC
    sales and updates the last-sold date on items.';

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
            WHERE upc=?
                AND store_id=?');

        $dlog = $this->config->get('TRANS_DB') . $dbc->sep() . 'dlog_15';
        /**
          Lookup each transaction containing the UPC instead of
          just MAX(tdate) over the time period. This is to adjust
          for voids. We want the lastest transaction where the item
          had a non-zero total.
        */
        $query = '
            SELECT upc,
                YEAR(tdate),
                MONTH(tdate),
                DAY(tdate),
                trans_num,
                store_id,
                MAX(tdate) AS last_sold
            FROM ' . $dlog . '
            WHERE trans_type=\'I\'
                AND tdate > \'' . date('Y-m-d', strtotime('1 week ago')) . '\'
                AND charflag <> \'SO\'
            GROUP BY YEAR(tdate),
                MONTH(tdate),
                DAY(tdate),
                trans_num,
                store_id,
                upc
            HAVING SUM(total) <> 0
            ORDER BY tdate
            ';
        $res = $dbc->query($query);
        while ($w = $dbc->fetchRow($res)) {
            $dbc->execute($update, array($w['last_sold'], $w['upc'], $w['store_id']));
        }
    }
}

