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

namespace COREPOS\Fannie\API\member
{

class MemberREST
{
    /**
      Get an array representing a customer account
      @param $id [int] account ID (classically card_no / CardNo)
      @return [array] account info or [boolean] false
    */
    public static function get($id)
    {
        $config = \FannieConfig::factory();
        $dbc = \FannieDB::get($config->get('OP_DB'));

        if ($dbc->tableExists('CustomerAccounts') && $dbc->tableExists('Customers')) {
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
        $account = new \CustomerAccountsModel($dbc);
        $customers = new \CustomersModel($dbc);
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

        $type = new \MemtypeModel($dbc);
        $type->memtype($account->customerTypeID());
        $type->load();
        $ret['customerType'] = $type->memDesc();

        return $ret;
    }

    /**
      Get account using older tables
    */
    private static function getCustdata($dbc, $id)
    {
        $query = '
            SELECT c.CardNo,
                CASE WHEN s.memtype2 IS NOT NULL THEN s.memtype2 ELSE c.Type END AS memberStatus,
                CASE WHEN s.memtype2 IS NOT NULL THEN c.Type ELSE \'\' END AS activeStatus,
                CASE WHEN s.memtype1 IS NOT NULL THEN s.memtype1 ELSE c.memType END AS customerTypeID,
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
            'idCardUPC' => $row['upc'],
            'startDate' => $row['start_date'],
            'endDate' => $row['end_date'],
            'city' => $row['city'],
            'state' => $row['state'],
            'zip' => $row['zip'],
            'contactAllowed' => $row['ads_OK'],
            'contactMethod' => 'mail',
            'addressFirstLine' => $row['street'],
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
        while ($w = $dbc->fetchRow($res)) {
            $customer = array(
                'customerID' => 0, // placeholder for compatibility
                'firstName' => $w['FirstName'],
                'lastName' => $w['LastName'],
                'chargeAllowed' => $w['chargeOk'],
                'checksAllowed' => $w['writeChecks'],
                'discount' => $w['Discount'],
                'staff' => $w['staff'],
                'lowIncomeBenefits' => $w['SSI'],
                'modified' => $w['modified'],
            );
            if ($w['personNum'] == 1) {
                $customer['accountHolder'] = 1;
                $customer['phone'] = $w['phone'];
                $customer['email'] = $w['email_1'];
                $customer['altPhone'] = $w['email_2'];
            } else {
                $customer['accountHolder'] = 0;
                $customer['phone'] = '';
                $customer['email'] = '';
                $customer['altPhone'] = '';
            }
            if ($w['memberStatus'] == 'PC') {
                $customer['memberPricingAllowed'] = 1;
                $customer['memberCouponsAllowed'] = 1;
            }
            $ret['customers'][] = $customer;
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
        $config = \FannieConfig::factory();
        $dbc = \FannieDB::get($config->get('OP_DB'));

        if ($id == 0) {
            $id = self::createAccount($dbc);
        }

        if ($dbc->tableExists('CustomerAccounts') && $dbc->tableExists('Customers')) {
            return self::postAccount($dbc, $id, $json);
        } else {
            return self::postCustdata($dbc, $id, $json);
        }
    }

    private static function createAccount($dbc)
    {
        $max = 1;
        if ($dbc->tableExists('CustomerAccounts')) {
            $query = 'SELECT MAX(cardNo) FROM customerAccounts';
        } else {
            $query = 'SELECT MAX(CardNo) FROM custdata';
        }
        $result = $dbc->query($query);
        if ($result && $dbc->numRows($result)) {
            $row = $dbc->fetchRow($result);
            $max = $row[0] + 1;
        }

        if ($dbc->tableExists('CustomerAccounts')) {
            $ca = new \CustomerAccountsModel($dbc);
            $ca->cardNo($max);
            $ca->save();
        }
        $custdata = new \CustdataModel($dbc);
        $custdata->CardNo($max);
        $custdata->personNum(1);
        $custdata->save();
        $meminfo = new \MeminfoModel($dbc);
        $meminfo->card_no($max);
        $meminfo->save();

        return $max;
    }

    /**
      Update newer tables then sync changes to
      older tables
    */
    private static function postAccount($dbc, $id, $json)
    {
        $ret = array('errors' => 0, 'error-msg' => '');
        $config = \FannieConfig::factory();
        $account = new \CustomerAccountsModel($dbc);
        $customers = new \CustomersModel($dbc);

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

        if (isset($json['customers']) && is_array($json['customers'])) {
            $columns = $customers->getColumns();
            foreach ($json['customers'] as $c_json) {
                $customers->reset();
                $customers->cardNo($id); 
                foreach ($columns as $col_name => $info) {
                    if ($col_name == 'cardNo') continue;
                    if ($col_name == 'modified') continue;

                    if (isset($c_json[$col_name])) {
                        $customers->$col_name($c_json[$col_name]);
                    }
                }
                if (!$customers->save()) {
                    $ret['errors']++;
                }
            }
        }

        // mirror changes to older tables
        $account->legacySync($id);
        $customers->legacySync($id);

        $ret['account'] = self::get($id);

        return $ret;
    }

    /**
      Update older tables.
    */
    private static function postCustdata($dbc, $id, $json)
    {
        $config = \FannieConfig::factory();
        $ret = array('errors' => 0, 'error-msg' => '');

        /** save dates if provided **/
        if (isset($json['startDate']) || isset($json['endDate'])) {
            $dates = new \MemDatesModel($dbc);
            $dates->start_date($json['startDate']); 
            $dates->end_date($json['endDate']); 
            $dates->card_no($id);
            if (!$dates->save()) {
                $ret['errors']++;
                $ret['error-msg'] .= 'ErrDates ';
            }
        }

        /** save UPC if provided **/
        if (isset($json['idCardUPC'])) {
            $cards = new \MemberCardsModel($dbc);
            $cards->card_no($id);
            $cards->upc(\BarcodeLib::padUPC($json['idCardUPC']));
            if (!$cards->save()) {
                $ret['errors']++;
            }
        }

        /** save contact method if provided **/
        if (isset($json['contactMethod'])) {
            $contact = new \MemContactModel($dbc);
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
        }

        /**
          Custdata and meminfo are messier. Start with account-level
          settings.
        */
        $custdata = new \CustdataModel($dbc);
        $custdata->CardNo($id);
        $custdata_changed = false;
        $meminfo = new \MeminfoModel($dbc);
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
            $pn = 2;
            foreach ($json['customers'] as $c_json) {
                if (!isset($c_json['accountHolder'])) {
                    $ret['errors']++;
                    $ret['error-msg'] .= 'ErrAcctHolder ';
                    continue;
                }
                $loopCD = new \CustdataModel($dbc);
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
                } elseif (isset($c_json['firstName']) && isset($c_json['lastName']) && $c_json['firstName'] == '' && $c_json['lastName'] == '') {
                    // blank name fields on non-account holder mean
                    // the customer was removed from the account
                    continue;
                } else {
                    $loopCD->personNum($pn);
                    $pn++;
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
                    $loopCD->chargeOk($c_json['chargeAllowed']);
                    $loopCD_changed = true;
                }
                if (isset($c_json['checksAllowed'])) {
                    $loopCD->writeChecks($c_json['checksAllowed']);
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
            $cleanR = $dbc->execute($cleanP, array($id, $pn));
        }

        if (!$meminfo->save()) {
            $ret['errors']++;
            $ret['error-msg'] .= 'ErrMeminfo ';
        }

        /**
          Finally, apply account-level settings to
          all custdata records for the account.
        */
        if ($custdata_changed) {
            $allCD = new \CustdataModel($dbc);
            $allCD->CardNo($id);
            foreach ($allCD->find() as $c) {
                $custdata->personNum($c->personNum());
                if (!$custdata->save()) {
                    $ret['errors']++;
                    $ret['error-msg'] .= 'ErrGlobal ';
                }
            }
        }
        self::setBlueLines($id);

        $ret['account'] = self::get($id);

        return $ret;
    }

    public static function setBlueLines($id)
    {
        $config = \FannieConfig::factory();
        $template = $config->get('BLUELINE_TEMPLATE');
        if ($template == '') {
            $template = '{{ACCOUNTNO}} {{FIRSTINITIAL}}. {{LASTNAME}}';
        }
        $dbc = \FannieDB::get($config->get('OP_DB'));
        $custdata = new \CustdataModel($dbc);
        $custdata->CardNo($id);
        $account = self::get($id); 
        $pn = 2;
        foreach ($account['customers'] as $c) {
            if (!isset($c['accountHolder'])) {
                continue;
            }
            if ($c['accountHolder']) {
                $custdata->personNum(1);
            } else {
                $custdata->personNum($pn);
                $pn++;
            }
            $bl = $template;
            $bl = str_replace('{{ACCOUNTNO}}', $id, $bl);
            $bl = str_replace('{{ACCOUNTTYPE}}', $account['customerType'], $bl);
            $bl = str_replace('{{FIRSTNAME}}', $c['firstName'], $bl);
            $bl = str_replace('{{LASTNAME}}', $c['lastName'], $bl);
            $bl = str_replace('{{FIRSTINITIAL}}', substr($c['firstName'],0,1), $bl);
            $bl = str_replace('{{LASTINITIAL}}', substr($c['lastName'],0,1), $bl);
            $custdata->blueLine($bl);
            $custdata->save();
        }
    }

    /**
      Search for an account
      @param $json [array] account attributes. All fields are optional, but
        if no search fields are provided this returns zero results rather than
        every single account.
      @return [array] of account structures
    */
    public static function search($json)
    {
        $config = \FannieConfig::factory();
        $dbc = \FannieDB::get($config->get('OP_DB'));

        if ($dbc->tableExists('CustomerAccounts') && $dbc->tableExists('Customers')) {
            return self::searchAccount($dbc, $json);
        } else {
            return self::searchCustdata($dbc, $json);
        }
    }

    private static function searchAccount($dbc, $json)
    {
        $query = '
            SELECT a.cardNo
            FROM CustomerAccounts AS a
                LEFT JOIN Customers AS c ON a.customerAccountID=c.customerAccountID
            WHERE 1=1 ';
        $params = array();
        if (isset($json['cardNo'])) {
            $query .= ' AND a.cardNo=? ';
            $params[] = $json['cardNo'];
        }
        if (isset($json['memberStatus'])) {
            $query .= ' AND a.memberStatus=? ';
            $params[] = $json['memberStatus'];
        }
        if (isset($json['customerTypeID'])) {
            $query .= ' AND a.customerTypeID=? ';
            $params[] = $json['customerTypeID'];
        }
        if (isset($json['chargeBalance'])) {
            $query .= ' AND a.chargeBalance=? ';
            $params[] = $json['chargeBalance'];
        }
        if (isset($json['chargeLimit'])) {
            $query .= ' AND a.chargeLimit=? ';
            $params[] = $json['chargeBalance'];
        }
        if (isset($json['idCardUPC'])) {
            $query .= ' AND a.idCardUPC=? ';
            $params[] = \BarcodeLib::padUPC($json['idCardUPC']);
        }
        if (isset($json['startDate'])) {
            $query .= ' AND a.startDate BETWEEN ? AND ? ';
            $params[] = $json['startDate'] . ' 00:00:00';
            $params[] = $json['startDate'] . ' 23:59:59';
        }
        if (isset($json['endDate'])) {
            $query .= ' AND a.endDate BETWEEN ? AND ? ';
            $params[] = $json['endDate'] . ' 00:00:00';
            $params[] = $json['endDate'] . ' 23:59:59';
        }
        if (isset($json['addressFirstLine'])) {
            $query .= ' AND a.addressFirstLine LIKE ? ';
            $params[] = '%' . $json['addressFirstLine'] . '%';
        }
        if (isset($json['addressSecondLine'])) {
            $query .= ' AND a.addressSecondLine LIKE ? ';
            $params[] = '%' . $json['addressSecondLine'] . '%';
        }
        if (isset($json['city'])) {
            $query .= ' AND a.city LIKE ? ';
            $params[] = '%' . $json['city'] . '%';
        }
        if (isset($json['state'])) {
            $query .= ' AND a.state LIKE ? ';
            $params[] = '%' . $json['state'] . '%';
        }
        if (isset($json['zip'])) {
            $query .= ' AND a.zip LIKE ? ';
            $params[] = '%' . $json['zip'] . '%';
        }
        if (isset($json['contactAllowed'])) {
            $query .= ' AND a.contactAllowed = ? ';
            $params[] = $json['contactAllowed'];
        }
        foreach ($json['customers'] as $j) {
            if (isset($j['lastName'])) {
                $query .= ' AND c.lastName LIKE ? ';
                $params[] = '%' . $j['lastName'] . '%';
            }
            if (isset($j['firstName'])) {
                $query .= ' AND c.firstName LIKE ? ';
                $params[] = '%' . $j['firstName'] . '%';
            }
            if (isset($j['chargeAllowed'])) {
                $query .= ' AND c.chargeAllowed = ? ';
                $params[] = $j['chargeAllowed'];
            }
            if (isset($j['checksAllowed'])) {
                $query .= ' AND c.checksAllowed = ? ';
                $params[] = $j['checksAllowed'];
            }
            if (isset($j['accountHolder'])) {
                $query .= ' AND c.accountHolder = ? ';
                $params[] = $j['accountHolder'];
            }
            if (isset($j['discount'])) {
                $query .= ' AND c.discount = ? ';
                $params[] = $j['discount'];
            }
            if (isset($j['staff'])) {
                $query .= ' AND c.staff = ? ';
                $params[] = $j['staff'];
            }
            if (isset($j['phone'])) {
                $query .= ' AND (c.phone LIKE ? OR c.altPhone LIKE ?) ';
                $params[] = '%' . $j['phone'] . '%';
                $params[] = '%' . $j['phone'] . '%';
            }
            if (isset($j['email'])) {
                $query .= ' AND c.email LIKE ? ';
                $params[] = '%' . $j['email'] . '%';
            }
            if (isset($j['lowIncomeBenefits'])) {
                $query .= ' AND c.lowIncomeBenefits = ? ';
                $params[] = $j['lowIncomeBenefits'];
            }
        }

        if (count($params) == 0) {
            return array();
        }

        $query .= ' GROUP BY c.CardNo';
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $params);
        $ret = array();
        while ($w = $dbc->fetchRow($res)) {
            // this is not efficient
            $ret[] = self::get($w['CardNo']);
        }

        return $ret;
    }

    private static function searchCustdata($dbc, $json)
    {
        $query = '
            SELECT c.CardNo
            FROM custdata AS c
                LEFT JOIN meminfo AS m ON c.CardNo=m.card_no
                LEFT JOIN memDates AS d ON c.CardNo=d.card_no
                LEFT JOIN memberCards AS u ON c.CardNo=u.card_no
                LEFT JOIN memContact AS t ON c.CardNo=t.card_no
            WHERE 1=1 ';
        $params = array();
        if (isset($json['cardNo'])) {
            $query .= ' AND c.CardNo=? ';
            $params[] = $json['cardNo'];
        }
        if (isset($json['memberStatus'])) {
            $query .= ' AND c.Type=? ';
            $params[] = $json['memberStatus'];
        }
        if (isset($json['customerTypeID'])) {
            $query .= ' AND c.memType=? ';
            $params[] = $json['customerTypeID'];
        }
        if (isset($json['chargeBalance'])) {
            $query .= ' AND c.Balance=? ';
            $params[] = $json['chargeBalance'];
        }
        if (isset($json['chargeLimit'])) {
            $query .= ' AND (c.ChargeLimit=? OR c.MemDiscountLimit=?) ';
            $params[] = $json['chargeBalance'];
            $params[] = $json['chargeBalance'];
        }
        if (isset($json['idCardUPC'])) {
            $query .= ' AND u.upc=? ';
            $params[] = \BarcodeLib::padUPC($json['idCardUPC']);
        }
        if (isset($json['startDate'])) {
            $query .= ' AND d.start_date BETWEEN ? AND ? ';
            $params[] = $json['startDate'] . ' 00:00:00';
            $params[] = $json['startDate'] . ' 23:59:59';
        }
        if (isset($json['endDate'])) {
            $query .= ' AND d.end_date BETWEEN ? AND ? ';
            $params[] = $json['endDate'] . ' 00:00:00';
            $params[] = $json['endDate'] . ' 23:59:59';
        }
        if (isset($json['addressFirstLine'])) {
            $query .= ' AND m.street LIKE ? ';
            $params[] = '%' . $json['addressFirstLine'] . '%';
        }
        if (isset($json['addressSecondLine'])) {
            $query .= ' AND m.street LIKE ? ';
            $params[] = '%' . $json['addressSecondLine'] . '%';
        }
        if (isset($json['city'])) {
            $query .= ' AND m.city LIKE ? ';
            $params[] = '%' . $json['city'] . '%';
        }
        if (isset($json['state'])) {
            $query .= ' AND m.state LIKE ? ';
            $params[] = '%' . $json['state'] . '%';
        }
        if (isset($json['zip'])) {
            $query .= ' AND m.zip LIKE ? ';
            $params[] = '%' . $json['zip'] . '%';
        }
        if (isset($json['contactAllowed'])) {
            $query .= ' AND m.ads_OK = ? ';
            $params[] = $json['contactAllowed'];
        }
        foreach ($json['customers'] as $j) {
            if (isset($j['lastName'])) {
                $query .= ' AND c.LastName LIKE ? ';
                $params[] = '%' . $j['lastName'] . '%';
            }
            if (isset($j['firstName'])) {
                $query .= ' AND c.FirstName LIKE ? ';
                $params[] = '%' . $j['firstName'] . '%';
            }
            if (isset($j['chargeAllowed'])) {
                $query .= ' AND c.ChargeOk = ? ';
                $params[] = $j['chargeAllowed'];
            }
            if (isset($j['checksAllowed'])) {
                $query .= ' AND c.writeChecks = ? ';
                $params[] = $j['checksAllowed'];
            }
            if (isset($j['accountHolder'])) {
                if ($j['accountHolder']) {
                    $query .= ' AND c.personNum = ? ';
                    $params[] = 1;
                } else {
                    $query .= ' AND c.personNum <> ? ';
                    $params[] = 1;
                }
            }
            if (isset($j['discount'])) {
                $query .= ' AND c.Discount = ? ';
                $params[] = $j['discount'];
            }
            if (isset($j['staff'])) {
                $query .= ' AND c.staff = ? ';
                $params[] = $j['staff'];
            }
            if (isset($j['phone'])) {
                $query .= ' AND (m.phone LIKE ? OR m.email_2 LIKE ?) ';
                $params[] = '%' . $j['phone'] . '%';
                $params[] = '%' . $j['phone'] . '%';
            }
            if (isset($j['email'])) {
                $query .= ' AND m.email LIKE ? ';
                $params[] = '%' . $j['email'] . '%';
            }
            if (isset($j['lowIncomeBenefits'])) {
                $query .= ' AND c.SSI = ? ';
                $params[] = $j['lowIncomeBenefits'];
            }
        }

        if (count($params) == 0) {
            return array();
        }

        $query .= ' GROUP BY c.CardNo';
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $params);
        $ret = array();
        while ($w = $dbc->fetchRow($res)) {
            // this is not efficient
            $ret[] = self::get($w['CardNo']);
        }

        return $ret;
    }
}

}

namespace 
{
    // global namespace wrapper class
    class MemberREST extends \COREPOS\Fannie\API\member\MemberREST {}
}
