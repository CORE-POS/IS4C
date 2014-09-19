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
            'GumEmailLog',
            'GumDividends',
            'GumDividendPayoffMap',
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

        /**
          Employee # used for writing any POS transactions
        */
        $settings->key('emp_no');
        if (!$settings->load()) {
            $settings->value(1001);
            $settings->save();
        }

        /**
          Register # used for writing any POS transactions
        */
        $settings->reset();
        $settings->key('register_no');
        if (!$settings->load()) {
            $settings->value(99);
            $settings->save();
        }

        /**
          $ value of one equity share
        */
        $settings->reset();
        $settings->key('equityShareSize');
        if (!$settings->load()) {
            $settings->value(500.00);
            $settings->save();
        }

        /**
          Department # for equity-related POS transactions
        */
        $settings->reset();
        $settings->key('equityPosDept');
        if (!$settings->load()) {
            $settings->value(993);
            $settings->save();
        }

        /**
          Department description for equity-related POS transactions
          (could probably be pulled from department table instead;
           guaranteeing a description even if the department doesn't
           exist may make debugging easier if the department # is
           misconfigured).
        */
        $settings->reset();
        $settings->key('equityDescription');
        if (!$settings->load()) {
            $settings->value('Class C Stock');
            $settings->save();
        }

        /**
          Department # for equity-related POS transactions
        */
        $settings->reset();
        $settings->key('loanPosDept');
        if (!$settings->load()) {
            $settings->value(998);
            $settings->save();
        }

        /**
          Department description for loan-related POS transactions
          See equityDescription for justification.
        */
        $settings->reset();
        $settings->key('loanDescription');
        if (!$settings->load()) {
            $settings->value('Member Loan');
            $settings->save();
        }

        /**
          Department # for balancing transactions
          Transactions are written as:
             $X.XX to loan/equity department
            -$X.XX to offset department
          This ensures transactions net to zero. It also
          allows tender to occur at a different time/place
          without leaving any transaction half-complete.
          The tender transaction would (probably) be:
             $X.XX to offset department
            -$X.XX tender type
        */
        $settings->reset();
        $settings->key('offsetPosDept');
        if (!$settings->load()) {
            $settings->value(800);
            $settings->save();
        }

        /**
          Month the fiscal year ends (typically 6 or 12)
        */
        $settings->reset();
        $settings->key('FYendMonth');
        if (!$settings->load()) {
            $settings->value(6);
            $settings->save();
        }

        /**
          Day the fiscal year ends (typically 30 or 31)
        */
        $settings->reset();
        $settings->key('FYendDay');
        if (!$settings->load()) {
            $settings->value(30);
            $settings->save();
        }

        /**
          The store's federal tax identification number.
          Only needed for tax forms.
        */
        $settings->reset();
        $settings->key('storeFederalID');
        if (!$settings->load()) {
            $settings->value('12-1234567');
            $settings->save();
        }

        /**
          The store's state tax identification number.
          Only needed for tax forms.
        */
        $settings->reset();
        $settings->key('storeStateID');
        if (!$settings->load()) {
            $settings->value('1234567');
            $settings->save();
        }

        /**
          The store's name (or DBA name)
          Used for address blocks
        */
        $settings->reset();
        $settings->key('storeName');
        if (!$settings->load()) {
            $settings->value('Name of Co-op / DBA');
            $settings->save();
        }

        /**
          The store's street address
          Multi-line street address is not permitted
          because vertical spacing is often tight. Some
          tax forms also *require* a single line street
          address. Use comma separation as needed.
          Used for address blocks
        */
        $settings->reset();
        $settings->key('storeAddress');
        if (!$settings->load()) {
            $settings->value('Street Address');
            $settings->save();
        }

        /**
          The store's city
          Used for address blocks
        */
        $settings->reset();
        $settings->key('storeCity');
        if (!$settings->load()) {
            $settings->value('Anytown');
            $settings->save();
        }

        /**
          The store's state (or equivalent)
          Used for address blocks
        */
        $settings->reset();
        $settings->key('storeState');
        if (!$settings->load()) {
            $settings->value('XX');
            $settings->save();
        }

        /**
          The store's zip code (or equivalent)
          Used for address blocks
        */
        $settings->reset();
        $settings->key('storeZip');
        if (!$settings->load()) {
            $settings->value('12345');
            $settings->save();
        }

        /**
          The store's phone number
          Used for [some] address blocks
        */
        $settings->reset();
        $settings->key('storePhone');
        if (!$settings->load()) {
            $settings->value('555-867-5309');
            $settings->save();
        }

        /**
          The store's checking account routing #
          Used for creating checks
        */
        $settings->reset();
        $settings->key('routingNo');
        if (!$settings->load()) {
            $settings->value('123456789');
            $settings->save();
        }

        /**
          The store's checking account #
          Used for creating checks
        */
        $settings->reset();
        $settings->key('checkingNo');
        if (!$settings->load()) {
            $settings->value('987654321987');
            $settings->save();
        }

        /**
          The first check number to use when creating checks.
          The plugin will use check numbers sequentially starting
          from this value
        */
        $settings->reset();
        $settings->key('firstCheckNumber');
        if (!$settings->load()) {
            $settings->value('1');
            $settings->save();
        }

        /**
          Zero-pad check numbers to this many digits.
          Six is the default.
        */
        $settings->reset();
        $settings->key('checkNumberPadding');
        if (!$settings->load()) {
            $settings->value('6');
            $settings->save();
        }

        /**
          POS interaction interface
        */
        $settings->reset();
        $settings->key('posLayer');
        if (!$settings->load()) {
            $settings->value('GumCoreLayer');
            $settings->save();
        }
    }

    public function plugin_enable()
    {
        FannieAuth::createClass('GiveUsMoney', 'Grants permission to use the GiveUsMoney plugin');
    }
}

