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
class WooPullTransTask extends FannieTask 
{
    public $name = 'Pull transactions from WooCommerce';

    public $description = 'Imports transaction data';

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
            array('wp_api'=>true, 'version'=>'wc/v1')
        );

        /**
          Map existing item's SKU (UPC) to woo's IDs
        */
        $json = $woo->get('orders', array('after' => date('Y-m-d 00:00:00', strtotime('yesterday'))));
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $itemP = $dbc->prepare('SELECT * FROM products WHERE upc=?');
        $dtrans = $this->config->get('TRANS_DB') . $dbc->sep() . 'dtransactions';
        foreach ($json as $order) {
            $trans_no = DTrans::getTransNo($dbc);
            $trans_id = 1;
            if ($order['status'] != 'completed') {
                continue;
            }
            foreach ($order['line_items'] as $line) {
                $row = DTrans::defaults();
                $row['store_id'] = 50;
                $row['emp_no'] = $this->config->get('EMP_NO');
                $row['register_no'] = $this->config->get('REGISTER_NO');
                $row['trans_no'] = $trans_no;
                $row['trans_id'] = $trans_id;
                $row['card_no'] = 11;
                $row['trans_type'] = 'I';
                $row['charflag'] = 'SO';
                $row['quantity'] = $line['quantity'];
                $row['ItemQtty'] = $line['quantity'];
                $row['upc'] = substr($line['sku'], 0, strlen($line['sku'])-3);
                $row['description'] = substr($line['name'], 0, 30);
                $row['unitPrice'] = $line['price'];
                $row['regPrice'] = $line['price'];
                $row['total'] = $line['total'];
                if ($line['total_tax'] > 0) {
                    $row['tax'] = 1;
                }
                $coreItem = $dbc->getRow($itemP, array($row['upc']));
                if ($coreItem) {
                    $row['department'] = $coreItem['department'];
                }
                $params = DTrans::parameterize($row);
                $params['columnString'] .= ',datetime';
                $params['valueString'] .= ',?';
                $params['arguments'][] = date('Y-m-d H:i:s', strtotime($order['date_modified']));
                $insP = $dbc->prepare("INSERT INTO {$dtrans} ({$params['columnString']}) VALUES ({$params['valuesString']})");
                $insR = $dbc->execute($insP, $params['arguments']);
                $trans_id++;
            }

            $row = DTrans::defaults();
            $row['store_id'] = 50;
            $row['emp_no'] = $this->config->get('EMP_NO');
            $row['register_no'] = $this->config->get('REGISTER_NO');
            $row['trans_no'] = $trans_no;
            $row['trans_id'] = $trans_id;
            $row['card_no'] = 11;
            $row['trans_type'] = 'T';
            $row['trans_subtype'] = 'PP';
            $row['description'] = 'PayPal';
            $row['total'] = -1 * $order['total'];
            $params = DTrans::parameterize($row);
            $params['columnString'] .= ',datetime';
            $params['valueString'] .= ',?';
            $params['arguments'][] = date('Y-m-d H:i:s', strtotime($order['date_modified']));
            $insP = $dbc->prepare("INSERT INTO {$dtrans} ({$params['columnString']}) VALUES ({$params['valuesString']})");
            $insR = $dbc->execute($insP, $params['arguments']);
            $trans_id++;

            $row = DTrans::defaults();
            $row['store_id'] = 50;
            $row['emp_no'] = $this->config->get('EMP_NO');
            $row['register_no'] = $this->config->get('REGISTER_NO');
            $row['trans_no'] = $trans_no;
            $row['trans_id'] = $trans_id;
            $row['card_no'] = 11;
            $row['trans_type'] = 'A';
            $row['description'] = 'TAX';
            $row['total'] = $order['tax_total'];
            $params = DTrans::parameterize($row);
            $params['columnString'] .= ',datetime';
            $params['valueString'] .= ',?';
            $params['arguments'][] = date('Y-m-d H:i:s', strtotime($order['date_modified']));
            $insP = $dbc->prepare("INSERT INTO {$dtrans} ({$params['columnString']}) VALUES ({$params['valuesString']})");
            $insR = $dbc->execute($insP, $params['arguments']);
            $trans_id++;
        }
    }
}

