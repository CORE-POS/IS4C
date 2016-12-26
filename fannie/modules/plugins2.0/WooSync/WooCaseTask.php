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

use COREPOS\Fannie\API\item\ItemText;

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class WooCaseTask extends FannieTask 
{
    public $name = 'Sync Cases to WooCommerce';

    public $description = 'Re-submits all cases';

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

        // hardcoded for now
        $settings['WooUrl'] = 'http://order.wholefoods.coop';
        $settings['WooKey'] = 'ck_92e50dfa535d08b383a7282279ac0e649236df10';
        $settings['WooSecret'] = 'cs_4a23e11bffbdd1a7de6c35b5326c2690510f7591';

        $woo = new Automattic\WooCommerce\Client(
            $settings['WooUrl'],
            $settings['WooKey'],
            $settings['WooSecret'],
            array('wp_api'=>true, 'version'=>'wc/v1', 'timeout'=>300)
        );

        /**
          Map existing item's SKU (UPC) to woo's IDs
        */
        $exists = array();
        $page = 1;
        do {
            $json = $woo->get('products', array('per_page' => 20, 'page'=>$page));
            $headers = $woo->headers();
            foreach ($json as $p) {
                if (substr($p['sku'], -3) == '-CS') {
                    $exists[$p['sku']] = $p['id'];
                }
            }
            $page++;
        } while (isset($headers['Link']) && strstr($headers['Link'], 'rel="next"'));

        /**
          Get CORE items and batch submit them to woo
        */
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $infoP = $dbc->prepare('
            SELECT p.upc,
                p.normal_price,
                p.special_price,
                ' . ItemText::longBrandSQL() . ',
                ' . ItemText::longDescriptionSQL() . ',
                ' . ItemText::signSizeSQL() . ',
                v.sku,
                v.units,
                p.discounttype,
                p.discount,
                u.long_text,
                p.start_date,
                p.end_date,
                p.tax,
                u.photo
            FROM products AS p
                LEFT JOIN productUser AS u ON p.upc=u.upc
                LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
            WHERE store_id=1
                AND p.upc=?');
        $query = "
            SELECT upc
            FROM " . $this->config->get('TRANS_DB') . $dbc->sep() . "CompleteSpecialOrder
            WHERE upc NOT LIKE '00000000%'
                AND mixMatch='UNFI'
                AND datetime >= '" . date('Y-m-d', strtotime('1 year ago')) . "'
            GROUP BY upc
            ORDER BY COUNT(*) DESC LIMIT 25";
        $res = $dbc->query($query);
        $create = $update = array(); 
        while ($soW = $dbc->fetchRow($res)) {
            $row = $dbc->getRow($infoP, array($soW['upc']));
            if ($row === false || !$row['units']) {
                continue;
            }
            if (empty($row['long_text'])) {
                $row['long_text'] = 'Unit size: ' . $row['size'] . '<br />'
                        . 'Case of ' . $row['units'];
            }
            if ($row['photo']) {
                echo "\t Has image {$row['photo']}!\n";
            }
            $row['upc'] .= '-CS';
            $item = array(
                'name' => $row['brand'] . ' ' . $row['description'],
                'sku' => $row['upc'],
                'description' => $row['long_text'],
                'short_description' => $row['brand'] . ' ' . $row['description'],
                'regular_price' => $row['units'] * $row['normal_price'],
                'sale_price' => $row['units'] * $row['special_price'],
                'tax_status' => $row['tax'] ? 'taxable' : 'none',
                'attributes' => array(
                    array(
                        'id' => 1,
                        'options' => array(
                            ($row['discount'] && !$row['discounttype']) ? '1' : '0',
                        ),
                    ),
                ),
            );
            if ($row['special_price'] == 0) {
                $item['date_on_sale_from'] = date('Y-m-d', strtotime('1 month ago'));
                $item['date_on_sale_to'] = date('Y-m-d', strtotime('1 month ago'));
            } else {
                $item['date_on_sale_from'] = $row['start_date'];
                $item['date_on_sale_to'] = $row['end_date'];
            }

            if (isset($exists[$row['upc']])) {
                $item['id'] = $exists[$row['upc']];
                $update[] = $item;
            } else {
                $create[] = $item;
            }
            if (count($update) + count($create) >= 5) {
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

