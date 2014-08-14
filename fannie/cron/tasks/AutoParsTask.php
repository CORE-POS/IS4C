<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
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
        $salesQ = 'SELECT ' . DTrans::sumQuantity('d') . ' AS qty, '
                . $dbc->datediff('MIN(d.tdate)', '?') . ' AS weight, '
                . 'MAX(discounttype) AS discounttype, '
                . $dbc->dayofweek('d.tdate') . ' AS dayNumber
                FROM ' . $FANNIE_TRANS_DB . $dbc->sep() . 'dlog_90_view AS d
                WHERE d.upc=?
                    AND charflag <> \'SO\'
                GROUP BY YEAR(d.tdate), MONTH(d.tdate), DAY(d.tdate)';
        $salesP = $dbc->prepare($salesQ);

        // look up vendor items that are in use
        $itemQ = 'SELECT v.sku, v.upc
                  FROM vendorItems AS v
                    INNER JOIN products AS p ON v.upc=p.upc AND v.vendorID=p.default_vendor_id
                  WHERE v.vendorID=?';
        $itemP = $dbc->prepare($itemQ);

        $prodP = $dbc->prepare('SELECT discounttype FROM products WHERE upc=?');
        $saveP = $dbc->prepare('UPDATE products SET auto_par=? WHERE upc=?');

        // look up number of days in actual data set
        $daysQ = 'SELECT ' . $dbc->datediff('MIN(tdate)', 'MAX(tdate)') . ' AS days,
                    MIN(tdate) AS minDay
                  FROM ' . $FANNIE_TRANS_DB . $dbc->sep() . 'dlog_90_view';
        $daysR = $dbc->query($daysQ);
        $daysW = $dbc->fetch_row($daysR);
        $num_days = (float)(abs($daysW['days']) + 1);
        $minDay = $daysW['minDay'];
        
        $model = new VendorDeliveriesModel($dbc);
        $vendors = new VendorsModel($dbc);
        // Examine all stocked items from all vendors
        foreach ($vendors->find() as $vendor) {
            $model->reset();
            $model->vendorID($vendor->vendorID());
            echo $this->cronMsg('Processing ' . $vendor->vendorName());

            $days = array(date('w'));
            // if vendor has a regular delivery schedule,
            // calculate next deliveries and track 
            // which days fall between them
            if ($model->load() && $model->regular() == 1) {
                $model->autoNext();
                $model->save();

                $ts1 = strtotime($model->nextDelivery());
                $ts2 = strtotime($model->nextNextDelivery());

                $days = array();
                while ($ts1 < $ts2) {
                    $days[] = date('w', $ts1);
                    $ts1 = mktime(0, 0, 0, date('n',$ts1), date('j',$ts1)+1, date('Y',$ts1));
                }
            }

            $itemR = $dbc->execute($itemP, array($vendor->vendorID()));
            while ($itemW = $dbc->fetch_row($itemR)) {
                $total_sales = 0.0;
                $sameday_sales = array();
                $special_sales = array();
                $salesR = $dbc->execute($salesP, array($minDay, $itemW['upc']));
                while ($salesW = $dbc->fetch_row($salesR)) {
                    if ($salesW['discounttype'] == 0) {
                        $total_sales += $salesW['qty'];
                    } else {
                        $special_sales[] = $salesW['qty'];
                    }
                    if (in_array($salesW['dayNumber'], $days)) {
                        $sameday[] = $salesW['qty'];
                    }
                }

                $daily_avg = $total_sales / ($num_days - count($special_sales));
                $special_avg = 0.0;
                if (count($special_sales) > 0) {
                    $special_avg = array_sum($special_sales) / count($special_sales);
                }

                $period_avg = $daily_avg * count($days);
                $special_avg *= count($days);

                $discount = $dbc->execute($prodP, array($itemW['upc']));
                $discount = $dbc->fetch_row($discount);
                if ($discount['discounttype'] == 0) {
                    $dbc->execute($saveP, array($period_avg, $itemW['upc']));
                } else {
                    $dbc->execute($saveP, array($special_avg, $itemW['upc']));
                }
            }
        }
    }
}

