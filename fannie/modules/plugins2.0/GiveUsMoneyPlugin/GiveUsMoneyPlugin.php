<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
class GiveUsMoneyPlugin extends FanniePlugin 
{
    public $plugin_settings = array(
    'GiveUsMoneyDB' => array('default'=>'GiveUsMoneyDB','label'=>'Database',
            'description'=>'Database for related information.'),
    );

    public $plugin_description = 'Plugin for mananging member loan/bond accounts
                                and share-based, dividend earning equity.';

    public function setting_change() {
        global $FANNIE_ROOT, $FANNIE_PLUGIN_SETTINGS;

        $db_name = $FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB'];
        if (empty($db_name)) return;

        $dbc = FannieDB::get($db_name);
        
        $tables = array(
            'GumEquityShares',
            'GumLoanAccounts',
            'GumLoanDefaultInterestRates',
            'GumLoanLedger',
            'GumLoanValidTerms',
            'GumSettings',
            'GumTaxIdentifiers',
            'GumPayoffs',
            'GumEquityPayoffMap',
            'GumLoanPayoffMap',
        );

        foreach($tables as $t) {
            $model_class = $t.'Model';
            if (!class_exists($model_class)) {
                include_once(dirname(__FILE__).'/models/'.$model_class.'.php');
            }
            $instance = new $model_class($dbc);
            $instance->create();        
        }

        $settings = new GumSettingsModel($dbc);

        $settings->key('emp_no');
        if (!$settings->load()) {
            $settings->value(1001);
            $settings->save();
        }

        $settings->reset();
        $settings->key('register_no');
        if (!$settings->load()) {
            $settings->value(30);
            $settings->save();
        }

        $settings->reset();
        $settings->key('equityShareSize');
        if (!$settings->load()) {
            $settings->value(500.00);
            $settings->save();
        }

        $settings->reset();
        $settings->key('equityPosDept');
        if (!$settings->load()) {
            $settings->value(993);
            $settings->save();
        }

        $settings->reset();
        $settings->key('equityDescription');
        if (!$settings->load()) {
            $settings->value('Class C Stock');
            $settings->save();
        }

        $settings->reset();
        $settings->key('FYendMonth');
        if (!$settings->load()) {
            $settings->value(6);
            $settings->save();
        }

        $settings->reset();
        $settings->key('FYendDay');
        if (!$settings->load()) {
            $settings->value(30);
            $settings->save();
        }

        $settings->reset();
        $settings->key('storeFederalID');
        if (!$settings->load()) {
            $settings->value('12-1234567');
            $settings->save();
        }

        $settings->reset();
        $settings->key('storeStateID');
        if (!$settings->load()) {
            $settings->value('1234567');
            $settings->save();
        }

        $settings->reset();
        $settings->key('storeState');
        if (!$settings->load()) {
            $settings->value('XX');
            $settings->save();
        }

        $settings->reset();
        $settings->key('routingNo');
        if (!$settings->load()) {
            $settings->value('123456789');
            $settings->save();
        }

        $settings->reset();
        $settings->key('checkingNo');
        if (!$settings->load()) {
            $settings->value('987654321987');
            $settings->save();
        }
    }
}

