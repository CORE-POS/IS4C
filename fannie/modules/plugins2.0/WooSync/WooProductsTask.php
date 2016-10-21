<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of IT CORE.

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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class WooProductsTask extends FannieTask 
{
    public $name = 'Sync Products to WooCommerce';

    public $description = 'Re-submits all products';

    public $default_schedule = array(
        'min' => 0,
        'hour' => 0,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        if (!class_exists('Automattic\WooCommerce\Client')) {
            $this->cronMsg('Library missing. Use composer to install automattic/woocommerce');
            return false;
        }
        $settings = $this->config->get('PLUGIN_SETTINGS');

        $woo = new Automattic\WooCommerce\Client(
            $settings['WooUrl'],
            $settings['WooKey'],
            $settings['WooSecret'],
            array('wp_api'=>true, 'version'=>'wc/v1')
        );

        /**
          Map existing item's SKU (UPC) to woo's IDs
        */
        $json = $woo->get('products');
        $products = json_decode($json, true);
        $exists = array();
        foreach ($products as $p) {
            $exists[$p['sku']] = $p['id'];
        }

        /**
          Get CORE items and batch submit them to woo
        */
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $query = "
            SELECT p.upc,
                (CASE WHEN u.brand IS NULL OR u.brand='' THEN p.brand ELSE u.brand END) AS brand,
                (CASE WHEN u.description IS NULL OR u.description='' THEN p.description ELSE u.description END) AS description,
                p.normal_price,
                p.special_price,
                p.start_date,
                p.end_date,
                u.long_text
            FROM products AS p
                LEFT JOIN productUser AS u ON p.upc=u.upc
            WHERE p.inUse=1
        ";
        $res = $dbc->query($query);
        $create = $update = array(); 
        while ($row = $dbc->fetchRow($res)) {
            $item = array(
                'name' => $row['description'],
                'sku' => $row['upc'],
                'description' => $row['long_text'],
                'short_description' => $row['brand'] . ' ' . $row['description'],
                'regular_price' => $row['normal_price'],
                'sale_price' => $row['special_price'],
                'date_on_sale_from' => $row['start_date'],
                'date_on_sale_to' => $row['end_date'],
            );
            if (isset($exists[$row['upc']])) {
                $item['id'] = $exists[$row['upc']];
                $update[] = $item;
            } else {
                $create[] = $item;
            }
            if (count($update) > 100 || count($create) > 100) {
                $woo->post('products/batch', array(
                    'create' => $create,
                    'update' => $update,
                ));
                $create = $update = array(); 
            }
        }

        $woo->post('products/batch', array(
            'create' => $create,
            'update' => $update,
        ));
    }
}

