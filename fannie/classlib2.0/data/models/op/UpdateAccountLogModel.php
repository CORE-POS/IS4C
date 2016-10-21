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
  @class UpdateAccountLogModel
*/
class UpdateAccountLogModel extends BasicModel
{
    protected $name = "UpdateAccountLog";
    protected $preferred_db = 'op';

    protected $columns = array(
    'updateAccountLogID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'updateType' => array('type'=>'VARCHAR(20)'),
    'userID' => array('type'=>'INT'),
    'modified' => array('type'=>'DATETIME'),
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
    );

    public function log($account, $type='UPDATE', $user=false)
    {
        if (!$user) {
            $user = FannieAuth::getUID(FannieAuth::checkLogin());
        }
        $this->reset();
        foreach ($this->columns as $name=>$info) {
            if ($name === 'updateAccountLogID') {
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

