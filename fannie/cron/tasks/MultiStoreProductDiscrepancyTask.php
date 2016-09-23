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

class MultiStoreProductDiscrepancyTask extends FannieTask
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
        $fields = array(
            'normal_price',
            'cost',
            'tax',
            'foodstamp',
            'wicable',
            'discount',
            'scale',
            'department', 
            'description',
            'brand',
            'local',
            'price_rule_id',
        );
        
        foreach ($fields as $field) $msg .= self::getDiscrepancies($dbc,$field);
        
        if ($msg != "") {
            $to = $this->config->get('ADMIN_EMAIL');
            $from = $to;
            $subject = 'Product Discrepancies Discovered Between Stores';
            $msg .= "\n";
            mail($to, $subject, $msg, 'From: ' . $from);
        }
    }
    
    private function getDiscrepancies($dbc,$field)
    {
        $diffR = $dbc->query("
            SELECT upc
            FROM products
            GROUP BY upc
            HAVING MIN({$field}) <> MAX({$field})
            ORDER BY department
        ");
        $count = $dbc->numRows($diffR);
        $msg = "";
        if ($count > 0 ) {
            $msg = "\n" . $count . " " . $field . " discrepancies were discovered\n";
            $host = $this->config->get('HTTP_HOST');
            $baseURL = $this->config->get('URL');
            while ($row = $dbc->fetchRow($diffR)) {
                $msg .= "http://{$host}{$baseURL}item/ItemEditorPage.php?searchupc=" . $row['upc'] . "\n";
            }
        }

        return $msg;
    }
    
}

