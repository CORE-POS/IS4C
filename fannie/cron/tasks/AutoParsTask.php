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

class AutoParsTask extends FannieTask
{

    public $name = 'Auto Pars Task';

    public $description = '';

    public $default_schedule = array(
        'min' => 15,
        'hour' => 3,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        // look up item daily movement for last quarter 
        $salesQ = 'SELECT ' 
                . DTrans::sumQuantity('d') . ' AS qty, '
                . $dbc->datediff($dbc->now(), 'MIN(tdate)') . ' AS diff,
                  MAX(discounttype) AS onSale
                FROM ' . $FANNIE_TRANS_DB . $dbc->sep() . 'dlog_90_view AS d
                WHERE d.upc=?
                    AND charflag <> \'SO\'
                    AND trans_status <> \'R\'
                GROUP BY year(tdate), month(tdate), day(tdate)
                ORDER BY year(tdate), month(tdate), day(tdate) DESC';
        $salesP = $dbc->prepare($salesQ);
        $prodP = $dbc->prepare('UPDATE products SET auto_par=? WHERE upc=?');

        $product = new ProductsModel($dbc);
        $product->inUse(1);
        $prodR = $dbc->query('
            SELECT upc
            FROM products
            WHERE inUse=1
        ');
        $lambda = 0.25;
        // average daily sales for items at retail price
        // sale days are discarded from both quantity sold
        // and number of days
        while ($prodW = $dbc->fetchRow($prodR)) {
            $upc = $prodW['upc'];
            $salesR = $dbc->execute($salesP, array($upc));
            if ($dbc->numRows($salesR) == 0) {
                $dbc->execute($prodP, array(0, $upc));
                continue;
            }
            $max = 0;
            $days = array();
            $last_nonsale_qty = 0.1;
            $nonsale_qty = 0.1;
            $nonsale_count = 0;
            while ($w = $dbc->fetchRow($salesR)) {
                $index = $w['diff'];
                if ($index > $max) {
                    $max = $index;
                }
                $days[$index] = $w['qty'];
                if ($w['onSale']) {
                    $days[$index] = ($nonsale_count == 0 ? $nonsale_qty : $nonsale_qty/$nonsale_count);
                } else {
                    $nonsale_qty += $w['qty'];
                    $nonsale_count++;
                }
            }
            $sum = 0;
            $count = 0;
            for ($i=1; $i<=$max; $i++) {
                if (isset($days[$i]) && $days[$i] == 'skip') {
                    continue;
                }
                $sum += (isset($days[$i]) ? $days[$i] : 0);
                $count++;
            }
            $avg = ($count == 0) ? 0 : $sum/$count;
            $dbc->execute($prodP, array($avg, $upc));
        }
    }

    // Box-Cox
    private function transform($val, $lambda)
    {
        if ($lambda == 0) {
            return log($val);
        } else {
            return (pow($val, $lambda) - 1) / $lambda;
        }
    }

    // Box-Cox
    private function backTransform($val, $lambda)
    {
        if ($lambda == 0) {
            return exp($val);
        } else {
            return pow(($val*$lambda)+1, (1/$lambda));
        }
    }
}

