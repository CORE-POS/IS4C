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

    /**
      Copy information from existing tables to
      a new CustomerAccounts record. This will NOT
      update an existing record.
      @param $card_no [int] member number
      @return [boolean] success
    */
    public function migrateAccount($card_no)
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
                CASE WHEN c.LastChange > m.modified THEN c.LastChange ELSE m.modified END AS modified
            FROM custdata AS c
                LEFT JOIN meminfo AS m ON c.CardNo=m.card_no
                LEFT JOIN memContact AS z ON c.CardNo=z.card_no
                LEFT JOIN memDates AS d ON c.CardNo=d.card_no
                LEFT JOIN memberCards AS u ON c.CardNo=u.card_no
                LEFT JOIN suspensions AS s ON c.CardNo=s.cardno
            WHERE c.CardNo=?
                AND c.personNum=1';
        $dbc = $this->connection;
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, array($card_no));
        if ($res === false || $dbc->numRows($res) == 0) {
            return false;
        }

        $this->reset();
        $this->CardNo($card_no);
        if ($this->load()) {
            return false; // record already exists
        }

        $w = $dbc->fetchRow($res);
        $this->memberStatus($w['memberStatus']);
        $this->activeStatus($w['activeStatus']);
        $this->customerTypeID($w['customerTypeID']);
        $this->chargeBalance($w['Balance']);
        $this->chargeLimit($w['ChargeLimit']);
        $this->idCardUPC($w['upc']);
        $this->startDate($w['start_date']);
        $this->endDate($w['end_date']);
        if (strstr($w['street'], "\n")) {
            list($addr1, $addr2) = explode("\n", $w['street'], 2);
            $this->addressFirstLine($addr1);
            $this->addressSecondLine($addr2);
        } else {
            $this->addressFirstLine($w['street']);
            $this->addressSecondLine('');
        }
        $this->city($w['city']);
        $this->state($w['state']);
        $this->zip($w['zip']);
        $this->contactAllowed($w['ads_OK']);
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
        $this->CardNo($card_no);
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

        if ($this->record_changed && !$lane_push) {
            $this->modified(date('Y-m-d H:i:s'));
        }

        return parent::save();
    }

    /* START ACCESSOR FUNCTIONS */

    public function customerAccountID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["customerAccountID"])) {
                return $this->instance["customerAccountID"];
            } else if (isset($this->columns["customerAccountID"]["default"])) {
                return $this->columns["customerAccountID"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'customerAccountID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["customerAccountID"]) || $this->instance["customerAccountID"] != func_get_args(0)) {
                if (!isset($this->columns["customerAccountID"]["ignore_updates"]) || $this->columns["customerAccountID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["customerAccountID"] = func_get_arg(0);
        }
        return $this;
    }

    public function cardNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["cardNo"])) {
                return $this->instance["cardNo"];
            } else if (isset($this->columns["cardNo"]["default"])) {
                return $this->columns["cardNo"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'cardNo',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["cardNo"]) || $this->instance["cardNo"] != func_get_args(0)) {
                if (!isset($this->columns["cardNo"]["ignore_updates"]) || $this->columns["cardNo"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["cardNo"] = func_get_arg(0);
        }
        return $this;
    }

    public function memberStatus()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memberStatus"])) {
                return $this->instance["memberStatus"];
            } else if (isset($this->columns["memberStatus"]["default"])) {
                return $this->columns["memberStatus"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'memberStatus',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["memberStatus"]) || $this->instance["memberStatus"] != func_get_args(0)) {
                if (!isset($this->columns["memberStatus"]["ignore_updates"]) || $this->columns["memberStatus"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["memberStatus"] = func_get_arg(0);
        }
        return $this;
    }

    public function activeStatus()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["activeStatus"])) {
                return $this->instance["activeStatus"];
            } else if (isset($this->columns["activeStatus"]["default"])) {
                return $this->columns["activeStatus"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'activeStatus',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["activeStatus"]) || $this->instance["activeStatus"] != func_get_args(0)) {
                if (!isset($this->columns["activeStatus"]["ignore_updates"]) || $this->columns["activeStatus"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["activeStatus"] = func_get_arg(0);
        }
        return $this;
    }

    public function customerTypeID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["customerTypeID"])) {
                return $this->instance["customerTypeID"];
            } else if (isset($this->columns["customerTypeID"]["default"])) {
                return $this->columns["customerTypeID"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'customerTypeID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["customerTypeID"]) || $this->instance["customerTypeID"] != func_get_args(0)) {
                if (!isset($this->columns["customerTypeID"]["ignore_updates"]) || $this->columns["customerTypeID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["customerTypeID"] = func_get_arg(0);
        }
        return $this;
    }

    public function chargeBalance()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["chargeBalance"])) {
                return $this->instance["chargeBalance"];
            } else if (isset($this->columns["chargeBalance"]["default"])) {
                return $this->columns["chargeBalance"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'chargeBalance',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["chargeBalance"]) || $this->instance["chargeBalance"] != func_get_args(0)) {
                if (!isset($this->columns["chargeBalance"]["ignore_updates"]) || $this->columns["chargeBalance"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["chargeBalance"] = func_get_arg(0);
        }
        return $this;
    }

    public function chargeLimit()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["chargeLimit"])) {
                return $this->instance["chargeLimit"];
            } else if (isset($this->columns["chargeLimit"]["default"])) {
                return $this->columns["chargeLimit"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'chargeLimit',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["chargeLimit"]) || $this->instance["chargeLimit"] != func_get_args(0)) {
                if (!isset($this->columns["chargeLimit"]["ignore_updates"]) || $this->columns["chargeLimit"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["chargeLimit"] = func_get_arg(0);
        }
        return $this;
    }

    public function idCardUPC()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["idCardUPC"])) {
                return $this->instance["idCardUPC"];
            } else if (isset($this->columns["idCardUPC"]["default"])) {
                return $this->columns["idCardUPC"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'idCardUPC',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["idCardUPC"]) || $this->instance["idCardUPC"] != func_get_args(0)) {
                if (!isset($this->columns["idCardUPC"]["ignore_updates"]) || $this->columns["idCardUPC"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["idCardUPC"] = func_get_arg(0);
        }
        return $this;
    }

    public function startDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["startDate"])) {
                return $this->instance["startDate"];
            } else if (isset($this->columns["startDate"]["default"])) {
                return $this->columns["startDate"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'startDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["startDate"]) || $this->instance["startDate"] != func_get_args(0)) {
                if (!isset($this->columns["startDate"]["ignore_updates"]) || $this->columns["startDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["startDate"] = func_get_arg(0);
        }
        return $this;
    }

    public function endDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["endDate"])) {
                return $this->instance["endDate"];
            } else if (isset($this->columns["endDate"]["default"])) {
                return $this->columns["endDate"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'endDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["endDate"]) || $this->instance["endDate"] != func_get_args(0)) {
                if (!isset($this->columns["endDate"]["ignore_updates"]) || $this->columns["endDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["endDate"] = func_get_arg(0);
        }
        return $this;
    }

    public function addressFirstLine()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["addressFirstLine"])) {
                return $this->instance["addressFirstLine"];
            } else if (isset($this->columns["addressFirstLine"]["default"])) {
                return $this->columns["addressFirstLine"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'addressFirstLine',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["addressFirstLine"]) || $this->instance["addressFirstLine"] != func_get_args(0)) {
                if (!isset($this->columns["addressFirstLine"]["ignore_updates"]) || $this->columns["addressFirstLine"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["addressFirstLine"] = func_get_arg(0);
        }
        return $this;
    }

    public function addressSecondLine()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["addressSecondLine"])) {
                return $this->instance["addressSecondLine"];
            } else if (isset($this->columns["addressSecondLine"]["default"])) {
                return $this->columns["addressSecondLine"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'addressSecondLine',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["addressSecondLine"]) || $this->instance["addressSecondLine"] != func_get_args(0)) {
                if (!isset($this->columns["addressSecondLine"]["ignore_updates"]) || $this->columns["addressSecondLine"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["addressSecondLine"] = func_get_arg(0);
        }
        return $this;
    }

    public function city()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["city"])) {
                return $this->instance["city"];
            } else if (isset($this->columns["city"]["default"])) {
                return $this->columns["city"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'city',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["city"]) || $this->instance["city"] != func_get_args(0)) {
                if (!isset($this->columns["city"]["ignore_updates"]) || $this->columns["city"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["city"] = func_get_arg(0);
        }
        return $this;
    }

    public function state()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["state"])) {
                return $this->instance["state"];
            } else if (isset($this->columns["state"]["default"])) {
                return $this->columns["state"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'state',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["state"]) || $this->instance["state"] != func_get_args(0)) {
                if (!isset($this->columns["state"]["ignore_updates"]) || $this->columns["state"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["state"] = func_get_arg(0);
        }
        return $this;
    }

    public function zip()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["zip"])) {
                return $this->instance["zip"];
            } else if (isset($this->columns["zip"]["default"])) {
                return $this->columns["zip"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'zip',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["zip"]) || $this->instance["zip"] != func_get_args(0)) {
                if (!isset($this->columns["zip"]["ignore_updates"]) || $this->columns["zip"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["zip"] = func_get_arg(0);
        }
        return $this;
    }

    public function contactAllowed()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["contactAllowed"])) {
                return $this->instance["contactAllowed"];
            } else if (isset($this->columns["contactAllowed"]["default"])) {
                return $this->columns["contactAllowed"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'contactAllowed',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["contactAllowed"]) || $this->instance["contactAllowed"] != func_get_args(0)) {
                if (!isset($this->columns["contactAllowed"]["ignore_updates"]) || $this->columns["contactAllowed"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["contactAllowed"] = func_get_arg(0);
        }
        return $this;
    }

    public function contactMethod()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["contactMethod"])) {
                return $this->instance["contactMethod"];
            } else if (isset($this->columns["contactMethod"]["default"])) {
                return $this->columns["contactMethod"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'contactMethod',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["contactMethod"]) || $this->instance["contactMethod"] != func_get_args(0)) {
                if (!isset($this->columns["contactMethod"]["ignore_updates"]) || $this->columns["contactMethod"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["contactMethod"] = func_get_arg(0);
        }
        return $this;
    }

    public function modified()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["modified"])) {
                return $this->instance["modified"];
            } else if (isset($this->columns["modified"]["default"])) {
                return $this->columns["modified"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'modified',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["modified"]) || $this->instance["modified"] != func_get_args(0)) {
                if (!isset($this->columns["modified"]["ignore_updates"]) || $this->columns["modified"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["modified"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

