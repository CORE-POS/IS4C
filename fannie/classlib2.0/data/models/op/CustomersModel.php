<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

/**
  @class CustomersModel
*/
class CustomersModel extends BasicModel
{
    protected $name = "Customers";
    /**
      Suppress create offer from updates tab
      until functionality is ready
    protected $preferred_db = 'op';
    */

    protected $columns = array(
    'customerID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'customerAccountID' => array('type'=>'INT'),
    'cardNo' => array('type'=>'INT'),
    'firstName' => array('type'=>'VARCHAR(50)'),
    'lastName' => array('type'=>'VARCHAR(50)'),
    'chargeAllowed' => array('type'=>'TINYINT', 'default'=>1),
    'checksAllowed' => array('type'=>'TINYINT', 'default'=>1),
    'discount' => array('type'=>'TINYINT', 'default'=>0),
    'accountHolder' => array('type'=>'TINYINT', 'default'=>0),
    'staff' => array('type'=>'TINYINT', 'default'=>0),
    'phone' => array('type'=>'VARCHAR(20)'),
    'altPhone' => array('type'=>'VARCHAR(20)'),
    'email' => array('type'=>'VARCHAR(50)'),
    'memberPricingAllowed' => array('type'=>'TINYINT', 'default'=>0),
    'memberCouponsAllowed' => array('type'=>'TINYINT', 'default'=>0),
    'lowIncomeBenefits' => array('type'=>'TINYINT', 'default'=>0),
    'modified' => array('type'=>'DATETIME'),
    );

    public function migrateQuery()
    {
        return '
            SELECT c.CardNo AS cardNo,
                c.personNum AS accountHolder,
                c.FirstName AS firstName,
                c.LastName AS lastName, 
                c.chargeOk AS chargeAllowed,
                c.writeChecks AS checksAllowed,
                c.Discount AS discount,
                c.staff,
                m.phone,
                m.email_1 AS email,
                m.email_2 AS altPhone,
                CASE WHEN s.memtype2 IS NOT NULL THEN s.memtype2 ELSE c.Type END AS memberStatus,
                c.SSI AS lowIncomeBenefits,
                CASE WHEN c.LastChange > m.modified THEN c.LastChange ELSE m.modified END AS modified
            FROM custdata AS c
                LEFT JOIN meminfo AS m ON c.CardNo=m.card_no
                LEFT JOIN suspensions AS s ON c.CardNo=s.cardno
            WHERE c.CardNo IN (?)';
    }

    /**
      Copy information from existing tables to
      a new customer records. This will NOT
      update an existing records.
      @param $card_no [int] member number
      @return [boolean] success
    */
    public function migrateAccount($card_no)
    {
        $dbc = $this->connection;
        $query = $this->migrateQuery();
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, array($card_no));
        if ($res === false || $dbc->numRows($res) == 0) {
            return false;
        }

        $this->reset();
        $this->cardNo($card_no);
        if (count($this->find()) > 0) {
            return false; // record(s) already exist
        }

        /**
          Get the related account ID, migrating it too
          if needed
        */
        $account = new CustomerAccountsModel($dbc);
        $account->cardNo($card_no);
        if (!$account->load()) {
            $id = $account->migrateAccount($card_no);
            if ($id === false) {
                return false;
            } else {
                $this->customerAccountID($id);
            }
        } else {
            $this->customerAccountID($account->customerAccountID());
        }

        while ($w = $dbc->fetchRow($res)) {
            $this->firstName($w['firstName']);
            $this->lastName($w['lastName']);
            $this->chargeAllowed($w['chargeAllowed']);
            $this->checksAllowed($w['checksAllowed']);
            $this->discount($w['discount']);
            $this->staff($w['staff']);
            $this->lowIncomeBenefits($w['lowIncomeBenefits']);
            if ($w['accountHolder'] == 1) {
                $this->accountHolder(1);
                $this->phone($w['phone']);
                $this->altPhone($w['altPhone']);
                $this->email($w['email']);
            } else {
                $this->accountHolder(0);
                $this->phone('');
                $this->email('');
            }
            if ($w['memberStatus'] == 'PC') {
                $this->memberPricingAllowed(1);
                $this->memberCouponsAllowed(1);
            }
            $this->modified($w['modified']);
            // preserve change timestamp rather than adding a new one for "now"
            $this->record_changed = false; 
            $this->save();
        }

        return true;
    }

    /**
      Update various legacy tables to match 
      existing Customer records. 
      @param $card_no [int] member number
      @return [boolean] success
    */
    public function legacySync($card_no)
    {
        $dbc = $this->connection;
        $custdata = new CustdataModel($dbc);
        $custdata->CardNo($card_no);
        $meminfo = new MeminfoModel($dbc);
        $meminfo->card_no($card_no);

        $this->reset();
        $this->cardNo($card_no);
        $this->accountHolder(1);
        if (count($this->find()) != 1) {
            // no customer records
            // or invalid customer records
            return false;
        }

        $account = new CustomerAccountsModel($dbc);
        $account->cardNo($card_no);
        if (!$account->load()) {
            return false;
        }

        foreach ($this->find() as $c) {
            $meminfo->phone($c->phone());
            $meminfo->email_2($c->altPhone());
            $meminfo->email_1($c->email());
            $meminfo->save();
            $custdata->personNum(1);
            $custdata->FirstName($c->firstName());
            $custdata->LastName($c->lastName());
            $custdata->blueLine($card_no . ' ' . $c->lastName());
            $custdata->ChargeOk($c->chargeAllowed());
            $custdata->WriteChecks($c->checksAllowed());
            $custdata->staff($c->staff());
            $custdata->SSI($c->lowIncomeBenefits());
            $custdata->Discount($c->discount());
            $custdata->save();
        }

        $person = 2;
        $this->accountHolder(0);
        foreach ($this->find() as $c) {
            $custdata->personNum($person);
            $custdata->FirstName($c->firstName());
            $custdata->LastName($c->lastName());
            $custdata->blueLine($card_no . ' ' . $c->lastName());
            $custdata->ChargeOk($c->chargeAllowed());
            $custdata->WriteChecks($c->checksAllowed());
            $custdata->staff($c->staff());
            $custdata->SSI($c->lowIncomeBenefits());
            $custdata->Discount($c->discount());
            $custdata->save();
            $person++;
        }

        return true;
    }

    public function save()
    {
        $stack = debug_backtrace();
        $lane_push = false;
        if (isset($stack[1]) && $stack[1]['function'] == 'pushToLanes') {
            $lane_push = true;
        }

        $changed = $this->record_changed;
        if ($changed && !$lane_push) {
            $this->modified(date('Y-m-d H:i:s'));
        }
        $saved = parent::save();

        if ($saved && $changed && !$lane_push) {
            $log = new UpdateCustomerLogModel($this->connection);
            $log->log($this);
        }

        return $saved;
    }
}

