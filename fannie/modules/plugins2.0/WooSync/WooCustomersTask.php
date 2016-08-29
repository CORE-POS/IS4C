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
class WooCustomersTask extends FannieTask 
{
    public $name = 'Sync Customers to WooCommerce';

    public $description = 'Re-submits all customers';

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
        $json = $woo->get('customers');
        $customers = json_decode($json, true);
        $exists = array();
        foreach ($customers as $c) {
            $exists[$c['email']] = $c['id'];
        }

        /**
          Get CORE items and batch submit them to woo
        */
        $ours = COREPOS\Fannie\API\member\MemberREST::search(array('memberStatus'=>'PC'));
        $create = $update = array(); 
        foreach ($ours as $o) {
            /**
              Find the primary account holder
            */
            $c = array('email'=>'');
            foreach ($o['customers'] as $name) {
                if ($name['acountHolder']) {
                    $c = $name;
                    break;
                }
            }
            /**
              Can't sync w/o an email address
            */
            if (empty($c['email'])) {
                continue;
            }
            $item = array(
                'email' => $c['email'],
                'first_name' => $c['firstName'],
                'last_name' => $c['lastName'],
                'billing' => array(
                    'email' => $c['email'],
                    'first_name' => $c['firstName'],
                    'last_name' => $c['lastName'],
                    'address_1' => $o['addressFirstLine'],
                    'address_2' => $o['addressSecondLine'],
                    'city' => $o['city'],
                    'state' => $o['state'],
                    'postcode' => $o['zip'],
                    'phone' => $c['phone'],
                ),
            );
            if (isset($exists[$c['email']])) {
                $item['id'] = $exists[$c['email']];
                $update[] = $item;
            } else {
                $item['shipping'] = $item['billing'];
                $create[] = $item;
            }
            if (count($update) > 100 || count($create) > 100) {
                $woo->post('customers/batch', array(
                    'create' => $create,
                    'update' => $update,
                ));
                $create = $update = array(); 
            }
        }

        $woo->post('customers/batch', array(
            'create' => $create,
            'update' => $update,
        ));
    }
}

