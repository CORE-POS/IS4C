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
        $url = $this->config->get('URL');
        $host = $this->config->get('HTTP_HOST');
        
        $msg = "";
        $msg .= self::priceTask($dbc);
        $msg .= self::deptTask($dbc);
        
        if ($msg != "") {
            $to = $this->config->get('ADMIN_EMAIL');
            $from = $to;
            $subject = 'Product Discrepancies Discovered Between Stores';
            $msg .= "\n";
            mail($to, $subject, $msg, 'From: ' . $from);
        }
    }
    
    private function deptTask($dbc)
    {
        $itemA = array();
        $itemB = array();

        $queryA = $dbc->prepare('
            SELECT upc, department 
            FROM products 
                WHERE store_id=1
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
        ');
        $resultA = $dbc->execute($queryA);
        while ($row = $dbc->fetch_row($resultA))  {
            $itemA[$row['upc']] = $row['department'];
        }
        
        $queryB = $dbc->prepare('
            SELECT upc, department 
            FROM products 
            WHERE store_id=2
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
        ');
        $resultb = $dbc->execute($queryB);
        while ($row = $dbc->fetch_row($resultB))  {
            $itemB[$row['upc']] = $row['department'];
        }
        
        $count = 0;
        foreach ($itemA as $upc => $department)  {
            if (isset($itemB[$upc])) {
                if ($department != $itemB[$upc]) $count++;
            }
        }

        $msg = "";
        if ($count > 0 ) {
            $msg = $count . " department discrepancies were discovered\n";
            foreach ($itemA as $upc => $department)  {
                $link = "http://192.168.1.2/git/fannie/item/ItemEditorPage.php?searchupc=" . $upc . "\t";
                if ($department != $itemB[$upc]) {
                    $msg .=  $link . $department . "\t" . $itemB[$upc] . "\n";
                }
            }
            
        }

        return $msg;
    }
    
    private function priceTask($dbc)
    {
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
            $item[$row['upc']]['priceA'] = $row['normal_price'];
            $item[$row['upc']]['priceB'] = $row['normal_price'] - $row['discrepancy'];
            $item[$row['upc']]['size'] = $row['size'];
            $item[$row['upc']]['brand'] = $row['brand'];
        }
        
        $msg = "";
        foreach ($item as $key => $row) {
            $link = "http://192.168.1.2/git/fannie/item/ItemEditorPage.php?searchupc=" . $key . "\t";
            $msg .= $link . $row['desc'] . "\t" . $row['priceA'] . "\t" . $row['priceB'] . "\n";
        }

        
        if($item) {
            $msg .= "\n";
            $msg .= 'To make Corrections, visit ';
            $msg .= "http://" . $this->config->get("HTTP_HOST") . $this->config->get("URL")
                . "fannie/item/PriceDiscrepancyScanner/PriceDiscrepancyPage.php";
        }
        
        return $msg;
    }
}

