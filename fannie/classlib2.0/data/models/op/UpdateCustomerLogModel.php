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
  @class UpdateCustomerLogModel
*/
class UpdateCustomerLogModel extends BasicModel
{
    protected $name = "UpdateCustomerLog";
    protected $preferred_db = 'op';

    protected $columns = array(
    'updateCustomerLogID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'updateType' => array('type'=>'VARCHAR(20)'),
    'userID' => array('type'=>'INT'),
    'modified' => array('type'=>'DATETIME'),
    'customerID' => array('type'=>'INT', 'index'=>true),
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
    );

    public function log($account, $type='UPDATE', $user=false)
    {
        if (!$user) {
            $user = FannieAuth::getUID(FannieAuth::checkLogin());
        }
        $this->reset();
        foreach ($this->columns as $name=>$info) {
            if ($name === 'updateCustomerLogID') {
            } elseif ($name === 'updateType') {
                $this->updateType($type);
            } elseif ($name === 'userID') {
                $this->userID($user);
            } else {
                $this->$name($account->$name());
            }
        }

        return $this->save();
    }

}

