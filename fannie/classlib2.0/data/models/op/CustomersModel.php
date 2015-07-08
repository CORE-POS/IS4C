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
    protected $preferred_db = 'op';

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
            WHERE c.CardNo=?';
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, array($card_no));
        if ($res === false || $dbc->numRows($res) == 0) {
            return false;
        }

        $this->reset();
        $this->CardNo($card_no);
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
            $this->firstName($w['FirstName']);
            $this->lastName($w['LastName']);
            $this->chargeAllowed($w['chargeOk']);
            $this->checksAllowed($w['writeChecks']);
            $this->discount($w['Discount']);
            $this->staff($w['staff']);
            $this->lowIncomeBenefits($w['SSI']);
            if ($w['personNum'] == 1) {
                $this->accountHolder(1);
                $this->phone($w['phone']);
                $this->altPhone($w['email_2']);
                $this->email($w['email_1']);
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
        $this->CardNo($card_no);
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
            $custdata->chargeOk($c->chargeAllowed());
            $custdata->writeChecks($c->checksAllowed());
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
            $custdata->chargeOk($c->chargeAllowed());
            $custdata->writeChecks($c->checksAllowed());
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

        if ($this->record_changed && !$lane_push) {
            $this->modified(date('Y-m-d H:i:s'));
        }

        return parent::save();
    }

    /* START ACCESSOR FUNCTIONS */

    public function customerID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["customerID"])) {
                return $this->instance["customerID"];
            } else if (isset($this->columns["customerID"]["default"])) {
                return $this->columns["customerID"]["default"];
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
                'left' => 'customerID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["customerID"]) || $this->instance["customerID"] != func_get_args(0)) {
                if (!isset($this->columns["customerID"]["ignore_updates"]) || $this->columns["customerID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["customerID"] = func_get_arg(0);
        }
        return $this;
    }

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

    public function firstName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["firstName"])) {
                return $this->instance["firstName"];
            } else if (isset($this->columns["firstName"]["default"])) {
                return $this->columns["firstName"]["default"];
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
                'left' => 'firstName',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["firstName"]) || $this->instance["firstName"] != func_get_args(0)) {
                if (!isset($this->columns["firstName"]["ignore_updates"]) || $this->columns["firstName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["firstName"] = func_get_arg(0);
        }
        return $this;
    }

    public function lastName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["lastName"])) {
                return $this->instance["lastName"];
            } else if (isset($this->columns["lastName"]["default"])) {
                return $this->columns["lastName"]["default"];
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
                'left' => 'lastName',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["lastName"]) || $this->instance["lastName"] != func_get_args(0)) {
                if (!isset($this->columns["lastName"]["ignore_updates"]) || $this->columns["lastName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["lastName"] = func_get_arg(0);
        }
        return $this;
    }

    public function chargeAllowed()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["chargeAllowed"])) {
                return $this->instance["chargeAllowed"];
            } else if (isset($this->columns["chargeAllowed"]["default"])) {
                return $this->columns["chargeAllowed"]["default"];
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
                'left' => 'chargeAllowed',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["chargeAllowed"]) || $this->instance["chargeAllowed"] != func_get_args(0)) {
                if (!isset($this->columns["chargeAllowed"]["ignore_updates"]) || $this->columns["chargeAllowed"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["chargeAllowed"] = func_get_arg(0);
        }
        return $this;
    }

    public function checksAllowed()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["checksAllowed"])) {
                return $this->instance["checksAllowed"];
            } else if (isset($this->columns["checksAllowed"]["default"])) {
                return $this->columns["checksAllowed"]["default"];
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
                'left' => 'checksAllowed',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["checksAllowed"]) || $this->instance["checksAllowed"] != func_get_args(0)) {
                if (!isset($this->columns["checksAllowed"]["ignore_updates"]) || $this->columns["checksAllowed"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["checksAllowed"] = func_get_arg(0);
        }
        return $this;
    }

    public function discount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discount"])) {
                return $this->instance["discount"];
            } else if (isset($this->columns["discount"]["default"])) {
                return $this->columns["discount"]["default"];
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
                'left' => 'discount',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["discount"]) || $this->instance["discount"] != func_get_args(0)) {
                if (!isset($this->columns["discount"]["ignore_updates"]) || $this->columns["discount"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["discount"] = func_get_arg(0);
        }
        return $this;
    }

    public function accountHolder()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["accountHolder"])) {
                return $this->instance["accountHolder"];
            } else if (isset($this->columns["accountHolder"]["default"])) {
                return $this->columns["accountHolder"]["default"];
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
                'left' => 'accountHolder',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["accountHolder"]) || $this->instance["accountHolder"] != func_get_args(0)) {
                if (!isset($this->columns["accountHolder"]["ignore_updates"]) || $this->columns["accountHolder"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["accountHolder"] = func_get_arg(0);
        }
        return $this;
    }

    public function staff()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["staff"])) {
                return $this->instance["staff"];
            } else if (isset($this->columns["staff"]["default"])) {
                return $this->columns["staff"]["default"];
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
                'left' => 'staff',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["staff"]) || $this->instance["staff"] != func_get_args(0)) {
                if (!isset($this->columns["staff"]["ignore_updates"]) || $this->columns["staff"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["staff"] = func_get_arg(0);
        }
        return $this;
    }

    public function phone()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["phone"])) {
                return $this->instance["phone"];
            } else if (isset($this->columns["phone"]["default"])) {
                return $this->columns["phone"]["default"];
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
                'left' => 'phone',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["phone"]) || $this->instance["phone"] != func_get_args(0)) {
                if (!isset($this->columns["phone"]["ignore_updates"]) || $this->columns["phone"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["phone"] = func_get_arg(0);
        }
        return $this;
    }

    public function altPhone()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["altPhone"])) {
                return $this->instance["altPhone"];
            } else if (isset($this->columns["altPhone"]["default"])) {
                return $this->columns["altPhone"]["default"];
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
                'left' => 'altPhone',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["altPhone"]) || $this->instance["altPhone"] != func_get_args(0)) {
                if (!isset($this->columns["altPhone"]["ignore_updates"]) || $this->columns["altPhone"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["altPhone"] = func_get_arg(0);
        }
        return $this;
    }

    public function email()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["email"])) {
                return $this->instance["email"];
            } else if (isset($this->columns["email"]["default"])) {
                return $this->columns["email"]["default"];
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
                'left' => 'email',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["email"]) || $this->instance["email"] != func_get_args(0)) {
                if (!isset($this->columns["email"]["ignore_updates"]) || $this->columns["email"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["email"] = func_get_arg(0);
        }
        return $this;
    }

    public function memberPricingAllowed()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memberPricingAllowed"])) {
                return $this->instance["memberPricingAllowed"];
            } else if (isset($this->columns["memberPricingAllowed"]["default"])) {
                return $this->columns["memberPricingAllowed"]["default"];
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
                'left' => 'memberPricingAllowed',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["memberPricingAllowed"]) || $this->instance["memberPricingAllowed"] != func_get_args(0)) {
                if (!isset($this->columns["memberPricingAllowed"]["ignore_updates"]) || $this->columns["memberPricingAllowed"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["memberPricingAllowed"] = func_get_arg(0);
        }
        return $this;
    }

    public function memberCouponsAllowed()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memberCouponsAllowed"])) {
                return $this->instance["memberCouponsAllowed"];
            } else if (isset($this->columns["memberCouponsAllowed"]["default"])) {
                return $this->columns["memberCouponsAllowed"]["default"];
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
                'left' => 'memberCouponsAllowed',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["memberCouponsAllowed"]) || $this->instance["memberCouponsAllowed"] != func_get_args(0)) {
                if (!isset($this->columns["memberCouponsAllowed"]["ignore_updates"]) || $this->columns["memberCouponsAllowed"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["memberCouponsAllowed"] = func_get_arg(0);
        }
        return $this;
    }

    public function lowIncomeBenefits()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["lowIncomeBenefits"])) {
                return $this->instance["lowIncomeBenefits"];
            } else if (isset($this->columns["lowIncomeBenefits"]["default"])) {
                return $this->columns["lowIncomeBenefits"]["default"];
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
                'left' => 'lowIncomeBenefits',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["lowIncomeBenefits"]) || $this->instance["lowIncomeBenefits"] != func_get_args(0)) {
                if (!isset($this->columns["lowIncomeBenefits"]["ignore_updates"]) || $this->columns["lowIncomeBenefits"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["lowIncomeBenefits"] = func_get_arg(0);
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

