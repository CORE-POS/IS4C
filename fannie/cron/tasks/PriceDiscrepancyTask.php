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

class PriceDiscrepancyTask extends FannieTask
{
    public $name = 'Product Price-discrepancy Check';

    public $description = 'Compares product prices 
    between two stores to locate discrepancies';

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
        $item = array();
        $prep = $dbc->prepare("SELECT 
                upc, 
                p.normal_price - (SELECT normal_price FROM products WHERE store_id=2 AND upc=p.upc) AS discrepancy,
                description,
                normal_price,
                size,
                brand
            FROM products AS p 
            WHERE store_id=1 
                AND inUse=1 
                AND (p.normal_price - (SELECT normal_price FROM products WHERE store_id=2 AND upc=p.upc) ) != 0
                AND department NOT BETWEEN 508 AND 998
                AND department NOT BETWEEN 250 AND 259
                AND department NOT BETWEEN 225 AND 234
                AND department NOT BETWEEN 1 AND 25
                AND department NOT BETWEEN 61 AND 78
                AND department != 46
                AND department != 150
                AND department != 208
                AND department != 235
                AND department != 240
                AND department != 500
                AND (p.inUse - (SELECT inUse FROM products WHERE store_id=2 AND upc=p.upc) ) = 0
        ;");

        $result = $dbc->execute($prep);
        while ($row = $dbc->fetch_row($result)) {
            $item[$row['upc']]['isdiscrep'] = 1;
            $item[$row['upc']]['desc'] = $row['description'];
            $item[$row['upc']]['hprice'] = $row['normal_price'];
            $item[$row['upc']]['dprice'] = $row['normal_price'] - $row['discrepancy'];
            $item[$row['upc']]['size'] = $row['size'];
            $item[$row['upc']]['brand'] = $row['brand'];
        }
        
        $ret = "";
        foreach ($item as $key => $row) {
            $ret .= $key . " :: " . $row['desc'] . "\n";
        }

        $ret .= "\n";
        //  Replace this link with the link to the Fannie Page once it exists.
        $ret .= 'To make Corrections, visit ';
        $ret .= 'http://key/git/fannie/item/PriceDiscrepancyScanner/PriceDiscrepancyPage.php';
        $ret = wordwrap($ret, 10, '\n');
        $ret = str_replace('\n', '', $ret);
        mail('it@wholefoods.coop', count($item) . ' Price Discrepancies found in POS', $ret, 'From: automail@wholefoods.coop');
        
    }
}

