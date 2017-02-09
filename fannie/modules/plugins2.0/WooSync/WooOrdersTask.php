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

if (!class_exists('OrderItemLib')) {
    include(__DIR__ . '/../../../ordering/OrderItemLib.php');
}
if (!class_exists('SpecialOrderLib')) {
    include(__DIR__ . '/../../../ordering/SpecialOrderLib.php');
}

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class WooOrdersTask extends FannieTask 
{
    public $name = 'Import Special Orders';

    public $description = 'Imports order data from WooCommerce';

    public $default_schedule = array(
        'min' => 0,
        'hour' => 1,
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
            array('wp_api'=>true, 'version'=>'wc/v1', 'timeoue'=>120)
        );

        /**
          Get a list of recent orders
        */
        $orders = array();
        $page = 1;
        do {
            $json = $woo->get('orders', array('after' => date('Y-m-d 00:00:00', strtotime('yesterday')), 'page'=>1));
            $headers = $woo->headers();
            foreach ($json as $o) {
                $orders[] = $o;
            }
            $page++;
        } while (isset($headers['Link']) && strstr($headers['Link'], 'rel="next"'));

        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $existsP = $dbc->prepare('
            SELECT orderID
            FROM ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'SpecialOrders
            WHERE onlineID=?');
        $contactP = $dbc->prepare('
            UPDATE ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'SpecialOrders
            SET firstName=?,
                lastName=?,
                street=?,
                city=?,
                state=?,
                zip=?,
                phone=?,
                email=?,
                sendEmails=1
            WHERE specialOrderID=?');
        $setID = $dbc->prepare('
            UPDATE ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'SpecialOrders
            SET onlineID=?
            WHERE specialOrderID=?');
        $transID = $dbc->prepare('
            SELECT MAX(trans_id)+1
            FROM ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'PendingSpecialOrder
            WHERE order_id=?');
        $spo = new SpecialOrderLib($dbc, $this->config);
        foreach ($orders as $order) {
            $exists = $dbc->getValue($existsP, array($order['id']));
            if ($exists) {
                continue;
            }
            $coreID = $spo->createEmptyOrder();
            $dbc->execute($setID, array($order['id'], $coreID));
            foreach ($order['line_items'] as $line) {
                $item = OrderItemLib::getItem(substr($line['sku'], 0, strlen($line['sku'])-3));
                $row = $spo->genericRow($coreID);
                $row['upc'] = $item['upc'];
                $row['description'] = $item['description'];
                $row['trans_type'] = 'I';
                $row['department'] = $item['department'];
                $row['quantity'] = $item['caseSize'];
                $row['unitPrice'] = $item['normal_price'];
                $row['total'] = $line['total'];
                $row['regPrice'] = $item['normal_price'];
                $row['tax'] = $item['tax'];
                $row['foodstamp'] = $item['foodstamp'];
                $row['discountable'] = $item['discount'];
                $row['ItemQtty'] = $line['quantity'];
                $row['mixMatch'] = $item['vendorName'];
                $row['trans_id'] = $dbc->getValue($transID, array($coreID));;
                $dbc->smartInsert($this->config->get('TRANS_DB') . $dbc->sep() . 'PendingSpecialOrder', $row);
            }
            $customer = $woo->get('customers/' . $order['customer_id']);
            $dbc->execute($contactP, array(
                $customer['first_name'],
                $customer['last_name'],
                $customer['billing']['address_1'] . (empty($customer['billing']['address_2']) ? '' : "\n" . $customer['billing']['address_2']),
                $customer['billing']['city'],
                $customer['billing']['state'],
                $customer['billing']['postcode'],
                $customer['billing']['phone'],
                $customer['email'],
                $coreID,
            ));
        }
    }
}

