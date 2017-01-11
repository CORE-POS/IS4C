<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

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

namespace COREPOS\Fannie\API\member;
use \FannieDB;
use \FannieConfig;
use \CustomerAccountsModel;
use \CustomersModel;
use \CustdataModel;
use \MemtypeModel;
use \MeminfoModel;
use \MemDatesModel;
use \MemberCardsModel;
use \MemContactModel;

class MemberREST
{
    private static $FIELD_INFO = array(
        'cardNo' => array('map'=>'CardNo', 'match'=>'strict'),
        'memberStatus' => array('map'=>'Type', 'match'=>'strict'),
        'customerTypeID' => array('map'=>'memType', 'match'=>'strict'),
        'chargeBalance' => array('map'=>'Balance', 'match'=>'strict'),
        'idCardUPC' => array('map'=>'upc', 'match'=>'strict'),
        'startDate' => array('map'=>'start_date', 'match'=>'date'),
        'endDate' => array('map'=>'end_date', 'match'=>'date'),
        'addressFirstLine' => array('map'=>'street', 'match'=>'fuzzy'),
        'addressSecondLine' => array('map'=>'street', 'match'=>'fuzzy'),
        'city' => array('map'=>'city', 'match'=>'fuzzy'),
        'state' => array('map'=>'state', 'match'=>'fuzzy'),
        'zip' => array('map'=>'zip', 'match'=>'fuzzy'),
        'contactAllowed' => array('map'=>'ads_OK', 'match'=>'strict'),
        'firstName' => array('map'=>'FirstName', 'match'=>'fuzzy'),
        'lastName' => array('map'=>'LastName', 'match'=>'fuzzy'),
        'chargeAllowed' => array('map'=>'ChargeOk', 'match'=>'strict'),
        'checksAllowed' => array('map'=>'writeChecks', 'match'=>'strict'),
        'discount' => array('map'=>'Discount', 'match'=>'strict'),
        'staff' => array('map'=>'staff', 'match'=>'strict'),
        'email' => array('map'=>'email_1', 'match'=>'fuzzy'),
        'lowIncomeBenefits' => array('map'=>'SSI', 'match'=>'strict'),
    );

    private static $hook_cache = array();

    private static $test_mode = false;
    public static function testMode($mode)
    {
        self::$test_mode = $mode;
    }

    /**
      A model class, on save, can trigger one or more hooks
      to perform additional operations. Discovering available
      hooks involves walking the file system. When creating 
      multiple objects of the same class this means walking the
      file system way more often than is really necessary. This
      wrapper caches per-class information about known hooks so
      subsequent instances don't have to re-walk the file system.
      This caching only persists through the current request so
      is only relevant to bulk edits.
    */
    private static function getModel($dbc, $class)
    {
        $model = new $class($dbc);
        if (isset(self::$hook_cache[$class])) {
            $model->setHooks(self::$hook_cache[$class]);
        }

        return $model;
    }

    /**
      Get an array representing a customer account
      @param $id [int] account ID (classically card_no / CardNo)
      @return [array] account info or [boolean] false

      Omitting the $id return an array of all accounts
      as is typical with REST endpoints
    */
    public static function get($id=0)
    {
        $config = FannieConfig::factory();
        $dbc = FannieDB::get($config->get('OP_DB'));

        if ($config->get('CUST_SCHEMA') == 1 && $dbc->tableExists('CustomerAccounts') && $dbc->tableExists('Customers')) {
            return self::getAccount($dbc, $id);
        } else {
            return self::getCustdata($dbc, $id);
        }
    }

    /**
      Get account using newer tables
    */
    private static function getAccount($dbc, $id)
    {
        if ($id == 0) {
            return self::getAllAccounts($dbc);
        }

        $account = new CustomerAccountsModel($dbc);
        $customers = new CustomersModel($dbc);
        $account->cardNo($id);
        if (!$account->load()) {
            // migrate already at this point?
            return false;
        }
        $customers->customerAccountID($account->customerAccountID());

        $ret = $account->toJSON();
        $ret['customers'] = array();
        foreach ($customers->find('accountHolder', true) as $c) {
            $ret['customers'][] = $c->toJSON();
        }

        $type = new MemtypeModel($dbc);
        $type->memtype($account->customerTypeID());
        $type->load();
        $ret['customerType'] = $type->memDesc();

        return $ret;
    }

    private static function getAllAccounts($dbc)
    {
        $account = new CustomerAccountsModel($dbc);
        $customers = new CustomersModel($dbc);
        $accounts = array();
        foreach ($account->find() as $obj) {
            $entry = $obj->toJSON();
            $entry['customers'] = array();
            $accounts[$obj->cardNo()] = $entry;
        }

        foreach ($customers->find('accountHolder', true) as $c) {
            if (!isset($accounts[$c->cardNo()])) {
                // parent account missing?
                continue;
            }
            $entry[$c->cardNo()]['customers'][] = $c->toJSON();
        }

        $ret = array();
        foreach ($entry as $id => $e) {
            $ret[] = $e;
        }

        return $ret;
    }

    /**
      Get account using older tables
    */
    private static function getCustdata($dbc, $id)
    {
        if ($id == 0) {
            return self::getAllCustdata($dbc);
        }

        $query = '
            SELECT c.CardNo,
                CASE WHEN s.memtype2 IS NOT NULL THEN s.memtype2 ELSE c.Type END AS memberStatus,
                CASE WHEN s.memtype2 IS NOT NULL THEN c.Type ELSE \'\' END AS activeStatus,
                c.memType AS customerTypeID,
                c.Balance,
                c.ChargeLimit,
                u.upc,
                d.start_date,
                d.end_date,
                m.street,
                m.city,
                m.state,
                m.zip,
                m.ads_OK,
                z.pref,
                t.memDesc,
                CASE WHEN c.LastChange > m.modified THEN c.LastChange ELSE m.modified END AS modified
            FROM custdata AS c
                LEFT JOIN meminfo AS m ON c.CardNo=m.card_no
                LEFT JOIN memContact AS z ON c.CardNo=z.card_no
                LEFT JOIN memDates AS d ON c.CardNo=d.card_no
                LEFT JOIN memberCards AS u ON c.CardNo=u.card_no
                LEFT JOIN suspensions AS s ON c.CardNo=s.cardno
                LEFT JOIN memtype AS t ON c.memType=t.memtype
            WHERE c.CardNo=?
                AND c.personNum=1';
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, array($id));
        if ($res === false || $dbc->numRows($res) == 0) {
            return false;
        }

        $row = $dbc->fetchRow($res);
        $ret = array(
            'cardNo' => $id,
            'memberStatus' => $row['memberStatus'],
            'activeStatus' => $row['activeStatus'],
            'customerTypeID' => $row['customerTypeID'],
            'customerType' => $row['memDesc'],
            'chargeLimit' => $row['ChargeLimit'],
            'chargeBalance' => $row['Balance'],
            'idCardUPC' => $row['upc'] === null ? '' : $row['upc'],
            'startDate' => $row['start_date'] === null ? '0000-00-00 00:00:00' : $row['start_date'],
            'endDate' => $row['end_date'] === null ? '0000-00-00 00:00:00' : $row['end_date'],
            'city' => $row['city'] === null ? '' : $row['city'],
            'state' => $row['state'] === null ? '' : $row['state'],
            'zip' => $row['zip'] === null ? '' : $row['zip'],
            'contactAllowed' => $row['ads_OK'] === null ? 1 : $row['ads_OK'],
            'contactMethod' => 'mail',
            'addressFirstLine' => $row['street'] === null ? '' : $row['street'],
            'addressSecondLine' => '',
            'customers' => array(),
            'modified' => $row['modified'],
        );
        if (strstr($row['street'], "\n")) {
            list($one, $two) = explode("\n", $row['street'], 2);
            $ret['addressFirstLine'] = $one;
            $ret['addressSecondLine'] = $two;
        }
        if ($row['pref'] == 2) {
            $ret['contactMethod'] == 'email';
        } elseif ($row['pref'] == 3) {
            $ret['contactMethod'] == 'both';
        }

        $ret['customers'] = self::getCustdataCustomers($dbc, $id);

        // if the new tables are present in classic mode,
        // migrate the account if needed and include the new style
        // record ID in the response
        if ($dbc->tableExists('CustomerAccounts') && $dbc->tableExists('Customers')) {
            $ret = self::addAccountIDs($dbc, $id, $ret);
        } else {
            // plug a value so the returned structure is complete
            $ret['customerAccountID'] = 0;
            $personNum = 2;
            for ($i=0; $i<count($ret['customers']); $i++) {
                if ($ret['customers'][$i]['accountHolder']) {
                    $ret['customers'][$i]['customerID'] = 1;
                } else {
                    $ret['customers'][$i]['customerID'] = $personNum;
                    $personNum++;
                }
            }
        }

        return $ret;
    }

    private static function addAccountIDs($dbc, $id, $ret)
    {
        $account = new CustomerAccountsModel($dbc);
        $account->cardNo($id);
        if ($account->load()) {
            $ret['customerAccountID'] = $account->customerAccountID();
        } else {
            $ret['customerAccountID'] = $account->migrateAccount($id);
        }
        // customers tables is more complicated
        // try to match IDs by names
        $customers = new CustomersModel($dbc);
        $customers->cardNo($id);
        $current = $customers->find();
        if (count($current) != count($ret['customers'])) {
            foreach ($customers as $c) {
                $c->delete();
            }
            $customers->migrateAccount($id);
            $customers->reset();
            $customers->cardNo($id);
        }
        foreach ($customers->find() as $c) {
            for ($i=0; $i<count($ret['customers']); $i++) {
                if ($ret['customers'][$i]['firstName'] == $c->firstName() && $ret['customers'][$i]['lastName'] == $c->lastName()) {
                    $ret['customers'][$i]['customerID'] = $c->customerID();
                    $ret['customers'][$i]['customerAccountID'] = $ret['customerAccountID'];
                    break;
                }
            }
        }

        return $ret;
    }

    private static function getCustdataCustomers($dbc, $id)
    {
        $query = '
            SELECT c.personNum,
                c.FirstName,
                c.LastName, 
                c.chargeOk,
                c.writeChecks,
                c.Discount,
                c.staff,
                m.phone,
                m.email_1,
                m.email_2,
                CASE WHEN s.memtype2 IS NOT NULL THEN s.memtype2 ELSE c.Type END AS memberStatus,
                c.SSI,
                CASE WHEN c.LastChange > m.modified THEN c.LastChange ELSE m.modified END AS modified
            FROM custdata AS c
                LEFT JOIN meminfo AS m ON c.CardNo=m.card_no
                LEFT JOIN suspensions AS s ON c.CardNo=s.cardno
            WHERE c.CardNo=?
            ORDER BY c.personNum';
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, array($id));
        $ret = array();
        while ($row = $dbc->fetchRow($res)) {
            $customer = array(
                'customerID' => 0, // placeholder for compatibility
                'firstName' => $row['FirstName'],
                'lastName' => $row['LastName'],
                'chargeAllowed' => $row['chargeOk'],
                'checksAllowed' => $row['writeChecks'],
                'discount' => $row['Discount'],
                'staff' => $row['staff'],
                'lowIncomeBenefits' => $row['SSI'],
                'modified' => $row['modified'],
            );
            if ($row['personNum'] == 1) {
                $customer['accountHolder'] = 1;
                $customer['phone'] = $row['phone'] === null ? '' : $row['phone'];
                $customer['email'] = $row['email_1'] === null ? '' : $row['email_1'];
                $customer['altPhone'] = $row['email_2'] === null ? '' : $row['email_2'];
            } else {
                $customer['accountHolder'] = 0;
                $customer['phone'] = '';
                $customer['email'] = '';
                $customer['altPhone'] = '';
            }
            if ($row['memberStatus'] == 'PC') {
                $customer['memberPricingAllowed'] = 1;
                $customer['memberCouponsAllowed'] = 1;
            }
            $ret[] = $customer;
        }

        return $ret;
    }

    private static function getAllCustdata($dbc)
    {
        // grab supplementary data from new tables if present
        $new_available = false;
        if ($dbc->tableExists('CustomerAccounts') && $dbc->tableExists('Customers')) {
            $new_available = true;
        }

        $query = '
            SELECT c.CardNo,
                CASE WHEN s.memtype2 IS NOT NULL THEN s.memtype2 ELSE c.Type END AS memberStatus,
                CASE WHEN s.memtype2 IS NOT NULL THEN c.Type ELSE \'\' END AS activeStatus,
                c.memType AS customerTypeID,
                c.Balance,
                c.ChargeLimit,
                u.upc,
                d.start_date,
                d.end_date,
                m.street,
                m.city,
                m.state,
                m.zip,
                m.ads_OK,
                z.pref,
                t.memDesc,
                c.FirstName,
                c.LastName, 
                c.chargeOk,
                c.writeChecks,
                c.Discount,
                c.staff,
                m.phone,
                m.email_1,
                m.email_2,
                c.SSI,
                c.personNum,
                CASE WHEN c.LastChange > m.modified THEN c.LastChange ELSE m.modified END AS modified
                ' . ($new_available ? ', a.customerAccountID ' : '') . '
            FROM custdata AS c
                LEFT JOIN meminfo AS m ON c.CardNo=m.card_no
                LEFT JOIN memContact AS z ON c.CardNo=z.card_no
                LEFT JOIN memDates AS d ON c.CardNo=d.card_no
                LEFT JOIN memberCards AS u ON c.CardNo=u.card_no
                LEFT JOIN suspensions AS s ON c.CardNo=s.cardno
                LEFT JOIN memtype AS t ON c.memType=t.memtype
                ' . ($new_available ? ' LEFT JOIN CustomerAccounts AS a ON c.CardNo=a.cardNo ' : '') . '
            ORDER BY c.personNum';
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep);

        $accounts = array();
        while ($row = $dbc->fetchRow($res)) {
            if (!isset($accounts[$row['CardNo']])) {
                $account = array(
                    'cardNo' => $row['CardNo'],
                    'memberStatus' => $row['memberStatus'],
                    'activeStatus' => $row['activeStatus'],
                    'customerTypeID' => $row['customerTypeID'],
                    'customerType' => $row['memDesc'],
                    'chargeLimit' => $row['ChargeLimit'],
                    'chargeBalance' => $row['Balance'],
                    'idCardUPC' => $row['upc'] === null ? '' : $row['upc'],
                    'startDate' => $row['start_date'] === null ? '0000-00-00 00:00:00' : $row['start_date'],
                    'endDate' => $row['end_date'] === null ? '0000-00-00 00:00:00' : $row['end_date'],
                    'city' => $row['city'] === null ? '' : $row['city'],
                    'state' => $row['state'] === null ? '' : $row['state'],
                    'zip' => $row['zip'] === null ? '' : $row['zip'],
                    'contactAllowed' => $row['ads_OK'] === null ? 1 : $row['ads_OK'],
                    'contactMethod' => 'mail',
                    'addressFirstLine' => $row['street'] === null ? '' : $row['street'],
                    'addressSecondLine' => '',
                    'customers' => array(),
                    'modified' => $row['modified'],
                );
                if (strstr($row['street'], "\n")) {
                    list($one, $two) = explode("\n", $row['street'], 2);
                    $account['addressFirstLine'] = $one;
                    $account['addressSecondLine'] = $two;
                }
                if ($row['pref'] == 2) {
                    $account['contactMethod'] == 'email';
                } elseif ($row['pref'] == 3) {
                    $account['contactMethod'] == 'both';
                }
                if (isset($row['customerAccountID'])) {
                    $account['customerAccountID'] = $row['customerAccountID'];
                } else {
                    $account['customerAccountID'] = 0;
                }

                $account['customers'] = array();
                $accounts[$row['CardNo']] = $account;
            }
            $customer = array(
                'customerID' => 0,
                'accountHolder' => $row['personNum'] == 1 ? 1 : 0,
                'firstName' => $row['FirstName'],
                'lastName' => $row['LastName'],
                'chargeAllowed' => $row['chargeOk'],
                'checksAllowed' => $row['writeChecks'],
                'discount' => $row['Discount'],
                'staff' => $row['staff'],
                'lowIncomeBenefits' => $row['SSI'],
                'modified' => $row['modified'],
                'phone' => $row['phone'] === null || $row['personNum'] != 1 ? '' : $row['phone'],
                'email' => $row['email_1'] === null || $row['personNum'] != 1 ? '' : $row['email_1'],
                'altPhone' => $row['email_2'] === null || $row['personNum'] != 1 ? '' : $row['email_2'],
                'memberPricingAllowed' => $account['memberStatus'] == 'PC' ? 1 : 0,
                'memberCouponsAllowed' => $account['memberStatus'] == 'PC' ? 1 : 0,
            );
            $accounts[$row['CardNo']]['customers'][] = $customer;
            if (self::$test_mode) {
                break;
            }
        }

        $ret = array();
        foreach ($accounts as $id => $e) {
            $ret[] = $e;
        }

        return $ret;
    }

    /**
      Update a customer account
      @param $id [int] account ID (classically card_no / CardNo)
      @param $json [array] settings in same format as returned by get()
      @return [array] error count
    */
    public static function post($id, $json)
    {
        $config = FannieConfig::factory();
        $dbc = FannieDB::get($config->get('OP_DB'));

        if ($id == 0) {
            $id = self::createAccount($dbc, $config);
        }

        if ($config->get('CUST_SCHEMA') == 1 && $dbc->tableExists('CustomerAccounts') && $dbc->tableExists('Customers')) {
            return self::postAccount($dbc, $id, $json);
        } else {
            return self::postCustdata($dbc, $id, $json);
        }
    }

    /**
      Update customer account using newer tables
    */
    private static function createAccount($dbc, $config)
    {
        $max = 1;
        if ($config->get('CUST_SCHEMA') == 1 && $dbc->tableExists('CustomerAccounts')) {
            $query = 'SELECT MAX(cardNo) FROM customerAccounts';
        } else {
            $query = 'SELECT MAX(CardNo) FROM custdata';
        }
        $result = $dbc->query($query);
        if ($result && $dbc->numRows($result)) {
            $row = $dbc->fetchRow($result);
            $max = $row[0] + 1;
        }

        // even if using the old schema it should still create
        // this record if the new table exists.
        if ($dbc->tableExists('CustomerAccounts')) {
            $account = self::getModel($dbc, 'CustomerAccountsModel');
            $account->cardNo($max);
            $account->save();
            self::$hook_cache['CustomerAccountsModel'] = $account->getHooks();
        }
        $custdata = self::getModel($dbc, 'CustdataModel');
        $custdata->CardNo($max);
        $custdata->personNum(1);
        $custdata->save();
        self::$hook_cache['CustdataModel'] = $custdata->getHooks();
        $meminfo = self::getModel($dbc, 'MeminfoModel');
        $meminfo->card_no($max);
        $meminfo->save();
        self::$hook_cache['MeminfoModel'] = $meminfo->getHooks();

        return $max;
    }

    /**
      Update newer tables then sync changes to
      older tables
    */
    private static function postAccount($dbc, $id, $json)
    {
        $ret = array('errors' => 0, 'error-msg' => '');
        $config = FannieConfig::factory();
        $account = self::getModel($dbc, 'CustomerAccountsModel');
        $customers = self::getModel($dbc, 'CustomersModel');

        $account->cardNo($id);
        foreach ($account->getColumns() as $col_name => $info) {
            if ($col_name == 'cardNo') continue;    
            if ($col_name == 'customerAccountID') continue;
            if ($col_name == 'modified') continue;
            
            if (isset($json[$col_name])) {
                $account->$col_name($json[$col_name]);
            }
        }
        if (!$account->save()) {
            $ret['errors']++;
        }
        self::$hook_cache['CustomerAccountsModel'] = $account->getHooks();

        if (isset($json['customers']) && is_array($json['customers'])) {
            $columns = $customers->getColumns();
            foreach ($json['customers'] as $c_json) {
                $customers->reset();
                $customers->cardNo($id); 
                $deletable = 0;
                foreach ($columns as $col_name => $info) {
                    if ($col_name == 'cardNo') continue;
                    if ($col_name == 'modified') continue;

                    $deletable += self::deletableCustomer($col_name, $c_json);

                    if (isset($c_json[$col_name])) {
                        $customers->$col_name($c_json[$col_name]);
                    }
                }
                if ($deletable == 3) {
                    // submitted an ID and blank name fields
                    $customers->delete();
                } elseif ($deletable == 2 && $customers->customerID() == 0) {
                    // skip creating member
                } elseif (!$customers->save()) {
                    $ret['errors']++;
                }
            }
        }
        self::$hook_cache['CustomersModel'] = $customers->getHooks();

        // mirror changes to older tables
        if ($config->get('CUST_SCHEMA') == 1) {
            $account->legacySync($id);
            $customers->legacySync($id);
        }

        $ret['account'] = self::get($id);

        return $ret;
    }

    private static function deletableCustomer($col_name, $c_json)
    {
        $deletable = 0;
        if ($col_name == 'customerID' && isset($c_json[$col_name]) && $c_json[$col_name] != 0) {
            $deletable++;
        } elseif ($col_name == 'firstName' && isset($c_json[$col_name]) && $c_json[$col_name] == '') {
            $deletable++;
        } elseif ($col_name == 'lastName' && isset($c_json[$col_name]) && $c_json[$col_name] == '') {
            $deletable++;
        }

        return $deletable;
    }

    /**
      Update older tables.
    */
    private static function postCustdata($dbc, $id, $json)
    {
        $config = FannieConfig::factory();
        $ret = array('errors' => 0, 'error-msg' => '');

        /** save dates if provided **/
        $ret = self::postMemDates($dbc, $id, $json, $ret);

        /** save UPC if provided **/
        $ret = self::postMemberCards($dbc, $id, $json, $ret);

        /** save contact method if provided **/
        $ret = self::postMemContact($dbc, $id, $json, $ret);

        /**
          Custdata and meminfo are messier. Start with account-level
          settings.
        */
        $custdata = self::getModel($dbc, 'CustdataModel');
        $custdata->CardNo($id);
        $custdata_changed = false;
        $meminfo = self::getModel($dbc, 'MeminfoModel');
        $meminfo->card_no($id);
        if (isset($json['addressFirstLine'])) {
            $street = $json['addressFirstLine'];
            if (isset($json['addressSecondLine'])) {
                $street .= "\n" . $json['addressSecondLine'];
            }
            $meminfo->street($street);
        }
        if (isset($json['city'])) {
            $meminfo->city($json['city']);
        }
        if (isset($json['state'])) {
            $meminfo->state($json['state']);
        }
        if (isset($json['zip'])) {
            $meminfo->zip($json['zip']);
        }
        if (isset($json['contactAllowed'])) {
            $meminfo->ads_OK($json['contactAllowed']);
        }
        if (isset($json['activeStatus']) && $json['activeStatus'] != '') {
            $custdata->Type($json['activeStatus']);
            $custdata_changed = true;
        } elseif (isset($json['memberStatus'])) {
            $custdata->Type($json['memberStatus']);
            $custdata_changed = true;
        }
        if (isset($json['customerTypeID'])) {
            $custdata->memType($json['customerTypeID']);
            $custdata_changed = true;
        }
        if (isset($json['chargeLimit'])) {
            $custdata->ChargeLimit($json['chargeLimit']);
            $custdata->MemDiscountLimit($json['chargeLimit']);
            $custdata_changed = true;
        }
        if (isset($json['chargeBalance'])) {
            $custdata->Balance($json['chargeBalance']);
            $custdata_changed = true;
        }

        /**
          Now loop through per-person settings. Assign the primary account holder's
          email address and phone number to the global meminfo, but save the other
          settings using a different per-person custdata instance
        */
        if (isset($json['customers']) && is_array($json['customers']) && count($json['customers']) > 0) {
            $personNum = 2;
            foreach ($json['customers'] as $c_json) {
                if (!isset($c_json['accountHolder'])) {
                    $ret['errors']++;
                    $ret['error-msg'] .= 'ErrAcctHolder ';
                    continue;
                }
                $loopCD = new CustdataModel($dbc);
                $loopCD->CardNo($id);
                $loopCD_changed = false;
                if ($c_json['accountHolder']) {
                    $loopCD->personNum(1);
                    if (isset($c_json['phone'])) {
                        $meminfo->phone($c_json['phone']);
                    }
                    if (isset($c_json['altPhone'])) {
                        $meminfo->email_2($c_json['altPhone']);
                    }
                    if (isset($c_json['email'])) {
                        $meminfo->email_1($c_json['email']);
                    }
                } elseif (isset($c_json['firstName']) && isset($c_json['lastName']) && trim($c_json['firstName']) == '' && trim($c_json['lastName']) == '') {
                    // blank name fields on non-account holder mean
                    // the customer was removed from the account
                    continue;
                } else {
                    $loopCD->personNum($personNum);
                    $personNum++;
                }
                if (isset($c_json['firstName'])) {
                    $loopCD->FirstName($c_json['firstName']);
                    $loopCD_changed = true;
                }
                if (isset($c_json['lastName'])) {
                    $loopCD->LastName($c_json['lastName']);
                    $loopCD_changed = true;
                }
                if (isset($c_json['chargeAllowed'])) {
                    $loopCD->ChargeOk($c_json['chargeAllowed']);
                    $loopCD_changed = true;
                }
                if (isset($c_json['checksAllowed'])) {
                    $loopCD->WriteChecks($c_json['checksAllowed']);
                    $loopCD_changed = true;
                }
                if (isset($c_json['staff'])) {
                    $loopCD->staff($c_json['staff']);
                    $loopCD_changed = true;
                }
                if (isset($c_json['discount'])) {
                    $loopCD->Discount($c_json['discount']);
                    $loopCD_changed = true;
                }
                if (isset($c_json['lowIncomeBenefits'])) {
                    $loopCD->SSI($c_json['lowIncomeBenefits']);
                    $loopCD_changed = true;
                }

                if ($loopCD_changed && !$loopCD->save()) {
                    $ret['errors']++;
                    $ret['error-msg'] .= 'ErrPerson ';
                }
            }
            $cleanP = $dbc->prepare('DELETE FROM custdata WHERE CardNo=? AND personNum>=?');
            $cleanR = $dbc->execute($cleanP, array($id, $personNum));
        }

        if (!$meminfo->save()) {
            $ret['errors']++;
            $ret['error-msg'] .= 'ErrMeminfo ';
        }
        self::$hook_cache['MeminfoModel'] = $meminfo->getHooks();

        /**
          Finally, apply account-level settings to
          all custdata records for the account.
        */
        if ($custdata_changed) {
            $allCD = new CustdataModel($dbc);
            $allCD->CardNo($id);
            foreach ($allCD->find() as $c) {
                $custdata->personNum($c->personNum());
                if (!$custdata->save()) {
                    $ret['errors']++;
                    $ret['error-msg'] .= 'ErrGlobal ';
                }
            }
            self::$hook_cache['CustdataModel'] = $custdata->getHooks();
        }
        self::setBlueLines($id);

        // in classic mode sync changes back to the new table if present
        if ($config->get('CUST_SCHEMA') != 1 && $dbc->tableExists('CustomerAccounts')) {
            self::postAccount($dbc, $id, $json);
        }

        $ret['account'] = self::get($id);

        return $ret;
    }

    private static function postMemDates($dbc, $id, $json, $ret)
    {
        if (isset($json['startDate']) || isset($json['endDate'])) {
            $dates = self::getModel($dbc, 'MemDatesModel');
            $dates->start_date($json['startDate']); 
            $dates->end_date($json['endDate']); 
            $dates->card_no($id);
            if (!$dates->save()) {
                $ret['errors']++;
                $ret['error-msg'] .= 'ErrDates ';
            }
            self::$hook_cache['MemDatesModel'] = $dates->getHooks();
        }

        return $ret;
    }

    private static function postMemberCards($dbc, $id, $json, $ret)
    {
        if (isset($json['idCardUPC'])) {
            $cards = self::getModel($dbc, 'MemberCardsModel');
            $cards->card_no($id);
            if ($json['idCardUPC'] != '') {
                $cards->upc(\BarcodeLib::padUPC($json['idCardUPC']));
            } else {
                $cards->upc('');
            }
            if (!$cards->save()) {
                $ret['errors']++;
            }
            self::$hook_cache['MemberCardsModel'] = $cards->getHooks();
        }

        return $ret;
    }

    private static function postMemContact($dbc, $id, $json, $ret)
    {
        if (isset($json['contactMethod'])) {
            $contact = new MemContactModel($dbc);
            $contact = self::getModel($dbc, 'MemContactModel');
            $contact->card_no($id);
            if (isset($json['contactAllowed']) && !$json['contactAllowed']) {
                $contact->pref(0);
            } elseif ($json['contactMethod'] == 'email') {
                $contact->pref(2);
            } elseif ($json['contactMethod'] == 'both') {
                $contact->pref(3);
            } else {
                $contact->pref(1);
            }
            if (!$contact->save()) {
                $ret['errors']++;
                $ret['error-msg'] .= 'ErrUPC ';
            }
            self::$hook_cache['MemContactModel'] = $contact->getHooks();
        }

        return $ret;
    }

    /**
      Assign blueLine values to account based on template configuration
      @param $id [int] account identifier
    */
    public static function setBlueLines($id)
    {
        $config = FannieConfig::factory();
        $template = $config->get('BLUELINE_TEMPLATE');
        if ($template == '') {
            $template = '{{ACCOUNTNO}} {{FIRSTINITIAL}}. {{LASTNAME}}';
        }
        $dbc = FannieDB::get($config->get('OP_DB'));
        $custdata = self::getModel($dbc, 'CustdataModel');
        $custdata->CardNo($id);
        $account = self::get($id); 
        $personNum = 2;
        foreach ($account['customers'] as $c) {
            if (!isset($c['accountHolder'])) {
                continue;
            }
            if ($c['accountHolder']) {
                $custdata->personNum(1);
            } else {
                $custdata->personNum($personNum);
                $personNum++;
            }
            $blueline = $template;
            $blueline = str_replace('{{ACCOUNTNO}}', $id, $blueline);
            $blueline = str_replace('{{ACCOUNTTYPE}}', $account['customerType'], $blueline);
            $blueline = str_replace('{{FIRSTNAME}}', $c['firstName'], $blueline);
            $blueline = str_replace('{{LASTNAME}}', $c['lastName'], $blueline);
            $blueline = str_replace('{{FIRSTINITIAL}}', substr($c['firstName'],0,1), $blueline);
            $blueline = str_replace('{{LASTINITIAL}}', substr($c['lastName'],0,1), $blueline);
            $custdata->blueLine($blueline);
            $custdata->save();
        }
        self::$hook_cache['CustdataModel'] = $custdata->getHooks();
    }

    /**
      Search for an account
      @param $json [array] account attributes. All fields are optional, but
        if no search fields are provided this returns zero results rather than
        every single account.
      @param $limit [int, default=0] optional result set size limit
      @return [array] of account structures
    */
    public static function search($json, $limit=0, $minimal=false)
    {
        $config = FannieConfig::factory();
        $dbc = FannieDB::getReadOnly($config->get('OP_DB'));

        if ($config->get('CUST_SCHEMA') == 1 && $dbc->tableExists('CustomerAccounts') && $dbc->tableExists('Customers')) {
            return self::searchAccount($dbc, $json, $limit, $minimal);
        } else {
            return self::searchCustdata($dbc, $json, $limit, $minimal);
        }
    }

    /**
      Replace non-digit characters with wildcards so that
      phone number format doesn't matter
    */
    private static function searchablePhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '%', $phone);
        $phone = '%' . $phone . '%';
        $phone = preg_replace('/%+/', '%', $phone);

        return $phone;
    }

    /**
      Search using newer tables
    */
    private static function searchAccount($dbc, $json, $limit=0, $minimal=false)
    {
        $query = '
            SELECT a.cardNo,
                c.firstName,
                c.lastName,
            FROM CustomerAccounts AS a
                LEFT JOIN Customers AS c ON a.customerAccountID=c.customerAccountID
            WHERE 1=1 ';
        $params = array();
        foreach (self::$FIELD_INFO as $field_name => $info) {
            list($query, $params) = self::buildSearchClause($query, $params, $json, $field_name, false);
            foreach ($json['customers'] as $j) {
                list($query, $params) = self::buildSearchClause($query, $params, $j, $field_name, false);
            }
        }
        if (isset($json['chargeLimit'])) {
            $query .= ' AND a.chargeLimit=? ';
            $params[] = $json['chargeBalance'];
        }
        foreach ($json['customers'] as $j) {
            if (isset($j['accountHolder'])) {
                $query .= ' AND c.accountHolder = ? ';
                $params[] = $j['accountHolder'];
            }
            if (isset($j['phone'])) {
                $j['phone'] = self::searchablePhone($j['phone']);
                $query .= ' AND (c.phone LIKE ? OR c.altPhone LIKE ?) ';
                $params[] = '%' . $j['phone'] . '%';
                $params[] = '%' . $j['phone'] . '%';
            }
        }

        if (!$minimal) {
            $query .= ' GROUP BY a.cardNo';
        }

        return self::getSearchResults($dbc, $query, $params, $minimal, $limit);
    }

    private static function buildSearchClause($query, $params, $json, $field_name, $use_map)
    {
        if (isset($json[$field_name]) && $json[$field_name] !== '' && isset(self::$FIELD_INFO[$field_name])) {
            $column = ($use_map) ? self::$FIELD_INFO[$field_name]['map'] : $field_name;
            $value = $json[$field_name];
            $info = array(
                'column' => $column,
                'value' => $value,
                'type' => self::$FIELD_INFO[$field_name]['match'],
            );
            list($query, $params) = self::genericSearchClause($query, $params, $info);
        }

        return array($query, $params);
    }

    private static function genericSearchClause($query, $params, $info)
    {
        switch ($info['type']) {
            case 'strict':
                $query .= ' AND ' . $info['column'] . '=? ';
                $params[] = $info['value'];
                break;
            case 'date':
                $query .= ' AND ' . $info['column'] . ' BETWEEN ? AND ? ';
                $tstamp = strtotime($info['value']);
                $params[] = date('Y-m-d 00:00:00', $tstamp);
                $params[] = date('Y-m-d 23:59:59', $tstamp);
                break;
            case 'fuzzy':
                $query .= ' AND ' . $info['column'] . ' LIKE ? ';
                $params[] = '%' . $info['value'] . '%';
                break;
        }

        return array($query, $params);
    }

    /**
      Search using older tables
    */
    private static function searchCustdata($dbc, $json, $limit=0, $minimal=false)
    {
        $query = '
            SELECT c.CardNo AS cardNo,
                c.FirstName,
                c.LastName
            FROM custdata AS c
                LEFT JOIN meminfo AS m ON c.CardNo=m.card_no
                LEFT JOIN memDates AS d ON c.CardNo=d.card_no
                LEFT JOIN memberCards AS u ON c.CardNo=u.card_no
                LEFT JOIN memContact AS t ON c.CardNo=t.card_no
            WHERE 1=1 ';
        $params = array();
        if (!isset($json['customers']) || !is_array($json['customers'])) {
            $json['customers'] = array();
        }
        foreach (self::$FIELD_INFO as $field_name => $info) {
            list($query, $params) = self::buildSearchClause($query, $params, $json, $field_name, true);
            foreach ($json['customers'] as $j) {
                list($query, $params) = self::buildSearchClause($query, $params, $j, $field_name, true);
            }
        }
        if (isset($json['chargeLimit'])) {
            $query .= ' AND (c.ChargeLimit=? OR c.MemDiscountLimit=?) ';
            $params[] = $json['chargeBalance'];
            $params[] = $json['chargeBalance'];
        }
        foreach ($json['customers'] as $j) {
            if (isset($j['accountHolder'])) {
                if ($j['accountHolder']) {
                    $query .= ' AND c.personNum = ? ';
                    $params[] = 1;
                } else {
                    $query .= ' AND c.personNum <> ? ';
                    $params[] = 1;
                }
            }
            if (isset($j['phone'])) {
                $j['phone'] = self::searchablePhone($j['phone']);
                $query .= ' AND (m.phone LIKE ? OR m.email_2 LIKE ?) ';
                $params[] = '%' . $j['phone'] . '%';
                $params[] = '%' . $j['phone'] . '%';
            }
        }

        if (!$minimal) {
            $query .= ' GROUP BY c.CardNo';
        }

        return self::getSearchResults($dbc, $query, $params, $minimal, $limit);
    }

    private static function getSearchResults($dbc, $query, $params, $minimal, $limit)
    {
        if (count($params) == 0) {
            return array();
        }

        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $params);
        $ret = array();
        $ids = array();
        while ($row = $dbc->fetchRow($res)) {
            // this is not efficient
            if ($minimal) {
                $ret[] = self::searchResult($row, $minimal);
            } else {
                $ids[] = $row['cardNo'];
            }

            if ($limit > 0 && count($ret) >= $limit) {
                break;
            }
        }
        if (!$minimal) {
            $ret = self::fastSearchResults($dbc, $ids);
        }

        return $ret;
    }

    private static function fastSearchResults($dbc, $ids)
    {
        list($inStr, $args) = $dbc->safeInClause($ids);
        $config = FannieConfig::factory();
        if ($config->get('CUST_SCHEMA') == 1 && $dbc->tableExists('CustomerAccounts') && $dbc->tableExists('Customers')) {
            $accountP = $dbc->prepare('SELECT * FROM CustomerAccounts WHERE cardNo IN (' . $inStr . ')');
            $nameP = $dbc->prepare('SELECT * FROM Customers WHERE cardNo IN ('. $inStr . ')');
        } else {
            $account = new CustomerAccountsModel($dbc);
            $accountP = $dbc->prepare(str_replace('?', $inStr, $account->migrateQuery()));
            $customer = new CustomersModel($dbc);
            $nameP = $dbc->prepare(str_replace('?', $inStr, $customer->migrateQuery()));
        }
        $ret = array();
        $res = $dbc->execute($accountP, $args);
        while ($row = $dbc->fetchRow($res)) {
            $ret[$row['cardNo']] = array(
                'cardNo' => $row['cardNo'],
                'memberStatus' => $row['memberStatus'],
                'activeStatus' => $row['activeStatus'],
                'customerTypeID' => $row['customerTypeID'],
                'chargeBalance' => $row['chargeBalance'],
                'idCardUPC' => $row['idCardUPC'],
                'startDate' => $row['startDate'],
                'endDate' => $row['endDate'],
                'addressFirstLine' => $row['addressFirstLine'],
                'city' => $row['city'],
                'state' => $row['state'],
                'zip' => $row['zip'],
                'contactAllowed' => $row['contactAllowed'],
                'customers' => array(),
            );
        }
        $res = $dbc->execute($nameP, $args);
        while ($row = $dbc->fetchRow($res)) {
            $ret[$row['cardNo']]['customers'][] = array(
                'accountHolder' => $row['accountHolder'],
                'firstName' => $row['firstName'],
                'lastName' => $row['lastName'],
                'chargeAllowed' => $row['chargeAllowed'],
                'checksAllowed' => $row['checksAllowed'],
                'discount' => $row['discount'],
                'staff' => $row['staff'],
                'phone' => $row['phone'],
                'altPhone' => $row['altPhone'],
                'email' => $row['email'],
                'lowIncomeBenefits' => $row['lowIncomeBenefits'],
            );
        }
        $dekey = array();
        foreach ($ret as $card_no => $json) {
            $dekey[] = $json;
        }

        return $dekey;
    }
    
    private static function searchResult($row, $minimal)
    {
        if ($minimal) {
            return array(
                'cardNo' => $row['cardNo'],
                'customers' => array(
                    array(
                        'cardNo' => $row['cardNo'],
                        'firstName' => $row['FirstName'],
                        'lastName' => $row['LastName'],
                    ),
                ),
            );
        } else {
            return self::get($row['CardNo']);
        }
    }


    /**
      Get the next account number sequentially
      @param $id [int] account identifier
      @return [int] next account identifier
    */
    public static function nextAccount($id)
    {
        return self::prevNext($id, 'MIN', '>');
    }

    /**
      Get the previous account number sequentially
      @param $id [int] account identifier
      @return [int] previous account identifier
    */
    public static function prevAccount($id)
    {
        return self::prevNext($id, 'MAX', '<');
    }

    private static function prevNext($id, $func, $op)
    {
        $config = FannieConfig::factory();
        $dbc = FannieDB::getReadOnly($config->get('OP_DB'));
        if ($config->get('CUST_SCHEMA') == 1 && $dbc->tableExists('CustomerAccounts') && $dbc->tableExists('Customers')) {
            $query = 'SELECT ' . $func . '(cardNo) FROM CustomerAccounts WHERE cardNo ' . $op . ' ?';
        } else {
            $query = 'SELECT ' . $func . '(CardNo) FROM custdata WHERE CardNo ' . $op . ' ?';
        }
        $prep = $dbc->prepare($query);
        return $dbc->getValue($prep, array($id));
    }

    /**
      Provide lookups for the autocomplete service
      @param $field [string] field name being autocompleted
      @param $val [string] partial field 
    */
    public static function autoComplete($field, $val)
    {
        $config = FannieConfig::factory();
        $dbc = FannieDB::getReadOnly($config->get('OP_DB'));
        if (strtolower($field) == 'mfirstname') {
            list($query, $args) = self::autoCompleteFirstName($val);
        } elseif (strtolower($field) == 'mlastname') {
            list($query, $args) = self::autoCompleteLastName($val);
        } elseif (strtolower($field) == 'maddress') {
            list($query, $args) = self::autoCompleteAddress($val);
        } elseif (strtolower($field) == 'mcity') {
            list($query, $args) = self::autoCompleteCity($val);
        } elseif (strtolower($field) == 'memail') {
            list($query, $args) = self::autoCompleteEmail($val);
        } else {
            $query = $field;
            $args = array();
        }

        $ret = array();
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);
        while ($row = $dbc->fetch_row($res)) {
            $ret[] = $row[0];
            if (count($ret) > 50) {
                break;
            }
        }

        return $ret;
    }

    private static function autoCompleteFirstName($val)
    {
        if (FannieConfig::config('CUST_SCHEMA') == 1) {
            $query = 'SELECT firstName
            FROM Customers
            WHERE firstName LIKE ?
            GROUP BY firstName
            ORDER BY firstName';
        } else {
            $query = 'SELECT FirstName
            FROM custdata
            WHERE FirstName LIKE ?
            GROUP BY FirstName
            ORDER BY FirstName';
        }

        return array($query, array('%' . $val . '%'));
    }

    private static function autoCompleteLastName($val)
    {
        if (FannieConfig::config('CUST_SCHEMA') == 1) {
            $query = 'SELECT lastName
            FROM Customers
            WHERE lastName LIKE ?
            GROUP BY lastName
            ORDER BY lastName';
        } else {
            $query = 'SELECT LastName
            FROM custdata
            WHERE LastName LIKE ?
            GROUP BY LastName
            ORDER BY LastName';
        }

        return array($query, array('%' . $val . '%'));
    }

    private static function autoCompleteAddress($val)
    {
        if (FannieConfig::config('CUST_SCHEMA') == 1) {
            $query = 'SELECT addressLineOne
                       FROM CustomerAccounts
                       WHERE addressLineOne LIKE ?
                       GROUP BY addressLineOne
                       ORDER BY addressLineOne';
        } else {
            $query = 'SELECT street
                       FROM meminfo
                       WHERE street LIKE ?
                       GROUP BY street
                       ORDER BY street';
        }

        return array($query, array('%' . $val . '%'));
    }

    private static function autoCompleteCity($val)
    {
        if (FannieConfig::config('CUST_SCHEMA') == 1) {
            $query = 'SELECT city
                       FROM CustomerAccounts
                       WHERE city LIKE ?
                       GROUP BY city
                       ORDER BY city';
        } else {
            $query = 'SELECT city
                       FROM meminfo
                       WHERE city LIKE ?
                       GROUP BY city
                       ORDER BY city';
        }

        return array($query, array('%' . $val . '%'));
    }

    private static function autoCompleteEmail($val)
    {
        if (FannieConfig::config('CUST_SCHEMA') == 1) {
            $query = 'SELECT email
                       FROM Customers
                       WHERE email LIKE ?
                       GROUP BY email
                       ORDER BY email';
        } else {
            $query = 'SELECT email_1
                       FROM meminfo
                       WHERE email_1 LIKE ?
                       GROUP BY email_1
                       ORDER BY email_1';
        }

        return array($query, array('%' . $val . '%'));
    }

    public static function getPrimary($json)
    {
        return array_filter($json['customers'], function($i){ return $i['accountHolder']; });
    }

    public static function getHousehold($json)
    {
        return array_filter($json['customers'], function($i){ return !$i['accountHolder']; });
    }
}

