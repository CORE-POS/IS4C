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
  @class CustomerAccountsModel
*/
class CustomerAccountsModel extends BasicModel
{

    protected $name = "CustomerAccounts";
    /**
      Suppress create offer from updates tab
      until functionality is ready
    protected $preferred_db = 'op';
    */
    protected $unique = array('cardNo');

    protected $columns = array(
    'customerAccountID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'cardNo' => array('type'=>'INT', 'index'=>true),
    'memberStatus' => array('type'=>'VARCHAR(10)', 'default'=>"'PC'"),
    'activeStatus' => array('type'=>'VARCHAR(10)', 'default'=>"''"),
    'customerTypeID' => array('type'=>'INT', 'default'=>1),
    'chargeBalance' => array('type'=>'MONEY', 'default'=>0),
    'chargeLimit' => array('type'=>'MONEY', 'default'=>0),
    'idCardUPC' => array('type'=>'VARCHAR(13)'),
    'startDate' => array('type'=>'DATETIME'),
    'endDate' => array('type'=>'DATETIME'),
    'addressFirstLine' => array('type'=>'VARCHAR(100)'),
    'addressSecondLine' => array('type'=>'VARCHAR(100)'),
    'city' => array('type'=>'VARCHAR(50)'),
    'state' => array('type'=>'VARCHAR(10)'),
    'zip' => array('type'=>'VARCHAR(10)'),
    'contactAllowed' => array('type'=>'TINYINT', 'default'=>1),
    'contactMethod' => array('type'=>'VARCHAR(10)', 'default'=>"'mail'"),
    'modified' => array('type'=>'DATETIME', 'ignore_updates'=>true)
    );

    public function migrateQuery()
    {
        return '
            SELECT c.CardNo AS cardNo,
                CASE WHEN s.memtype2 IS NOT NULL THEN s.memtype2 ELSE c.Type END AS memberStatus,
                CASE WHEN s.memtype2 IS NOT NULL THEN c.Type ELSE \'\' END AS activeStatus,
                CASE WHEN s.memtype1 IS NOT NULL THEN s.memtype1 ELSE c.memType END AS customerTypeID,
                c.Balance AS chargeBalance,
                c.ChargeLimit AS chargeLimit,
                u.upc AS idCardUPC,
                d.start_date AS startDate,
                d.end_date AS endDate,
                m.street AS addressFirstLine,
                m.city,
                m.state,
                m.zip,
                m.ads_OK AS contactAllowed,
                z.pref,
                CASE WHEN c.LastChange > m.modified THEN c.LastChange ELSE m.modified END AS modified
            FROM custdata AS c
                LEFT JOIN meminfo AS m ON c.CardNo=m.card_no
                LEFT JOIN memContact AS z ON c.CardNo=z.card_no
                LEFT JOIN memDates AS d ON c.CardNo=d.card_no
                LEFT JOIN memberCards AS u ON c.CardNo=u.card_no
                LEFT JOIN suspensions AS s ON c.CardNo=s.cardno
            WHERE c.CardNo IN (?)
                AND c.personNum=1';
    }

    /**
      Copy information from existing tables to
      a new CustomerAccounts record. This will NOT
      update an existing record.
      @param $card_no [int] member number
      @return [boolean] success
    */
    public function migrateAccount($card_no)
    {
        $query = $this->migrateQuery();
        $dbc = $this->connection;
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, array($card_no));
        if ($res === false || $dbc->numRows($res) == 0) {
            return false;
        }

        $this->reset();
        $this->cardNo($card_no);
        if ($this->load()) {
            return false; // record already exists
        }

        $w = $dbc->fetchRow($res);
        $this->memberStatus($w['memberStatus']);
        $this->activeStatus($w['activeStatus']);
        $this->customerTypeID($w['customerTypeID']);
        $this->chargeBalance($w['chargeBalance']);
        $this->chargeLimit($w['chargeLimit']);
        $this->idCardUPC($w['idCardUPC']);
        $this->startDate($w['startDate']);
        $this->endDate($w['endDate']);
        if (strstr($w['addressFirstLine'], "\n")) {
            list($addr1, $addr2) = explode("\n", $w['addressFirstLine'], 2);
            $this->addressFirstLine($addr1);
            $this->addressSecondLine($addr2);
        } else {
            $this->addressFirstLine($w['addressFirstLine']);
            $this->addressSecondLine('');
        }
        $this->city($w['city']);
        $this->state($w['state']);
        $this->zip($w['zip']);
        $this->contactAllowed($w['contactAllowed']);
        if ($w['pref'] == 2) {
            $this->contactMethod('email');
        } elseif ($w['pref'] == 3) {
            $this->contactMethod('both');
        }
        $this->modified($w['modified']);
        // preserve change timestamp rather than adding a new one for "now"
        $this->record_changed = false; 

        return $this->save();
    }

    /**
      Update various legacy tables to match an
      existing CustomerAccounts record. 
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
        $memDates = new MemDatesModel($dbc);
        $memDates->card_no($card_no);
        $cards = new MemberCardsModel($dbc);
        $cards->card_no($card_no);
        $contact = new MemContactModel($dbc);
        $contact->card_no($card_no);
        $suspensions = new SuspensionsModel($dbc);
        $suspensions->cardno($card_no);

        $this->reset();
        $this->cardNo($card_no);
        if (!$this->load()) {
            return false;
        }

        if ($this->activeStatus() != '') {
            $suspensions->cardno($card_no);
            $suspensions->memtype1($this->customerTypeID());
            $suspensions->memtype2($this->memberStatus());
            $suspensions->chargelimit($this->chargeLimit());
            $suspensions->mailflag($this->contactAllowed());
            $suspensions->save();
        } else {
            $custdata->Type($this->memberStatus());
            $custdata->memType($this->customerTypeID());
            $custdata->ChargeLimit($this->chargeLimit());
            $custdata->MemDiscountLimit($this->chargeLimit());
            $meminfo->ads_OK($this->contactAllowed());
        }
        $custdata->Balance($this->chargeBalance());
        $allCustdata = new CustdataModel($dbc);
        $allCustdata->CardNo($card_no);
        foreach ($allCustdata as $c) {
            $custdata->personNum($c->personNum());
            $custdata->save();
        }

        $cards->upc($this->idCardUPC());
        $cards->save();

        $memDates->start_date($this->startDate());
        $memDates->end_date($this->endDate());
        $memDates->save();

        if ($this->addressSecondLine() != '') {
            $meminfo->street($this->addressFirstLine() . "\n" . $this->addressSecondLine());
        } else {
            $meminfo->street($this->addressFirstLine());
        }
        $meminfo->city($this->city());
        $meminfo->state($this->state());
        $meminfo->zip($this->zip());
        $meminfo->save();

        if ($this->contactAllowed() == 0) {
            $contact->pref(0);
        } else {
            switch ($this->contactMethod()) {
                case 'mail':
                    $contact->pref(1);
                    break;
                case 'email':
                    $contact->pref(2);
                    break;
                case 'both':
                    $contact->pref(3);
                    break;
            }
        }
        $contact->save();

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
            $log = new UpdateAccountLogModel($this->connection);
            $log->log($this);
        }

        return $saved;
    }
}

