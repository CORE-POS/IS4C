<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op
    Copyright 2014 West End Food Co-op, Toronto, Ontario

    This file is part of Fannie.

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

use COREPOS\pos\lib\models\BasicModel;

/**
  @class CCredMembershipsModel
*/
class CCredMembershipsModel extends BasicModel
{

    protected $name = "CCredMemberships";
    protected $preferred_db = 'plugin:CoopCredLaneDatabase';

    protected $columns = array(
        // FK to coop_cred.CCredPrograms
        'programID' => array('type'=>'SMALLINT(6)', 'not_null'=>True,
            'default'=>0, 'index'=>True),
        // FK to custdata
        'cardNo' => array('type'=>'INT(11)', 'not_null'=>True, 'default'=>0,
            'index'=>True),
        'creditBalance' => array('type'=>'MONEY', 'not_null'=>True, 'default'=>0),
        // Always 0 in CoopCred; means Member may not be in debt to store.
        'creditLimit' => array('type'=>'MONEY', 'not_null'=>True, 'default'=>0),
        // The most that can be deposited in a Member's account.
        // Is negative, so CCredMemberships.creditBalance must be larger.
        // Defaults to CCredPrograms.maxCreditBalance.
        'maxCreditBalance' => array('type'=>'MONEY', 'not_null'=>True, 'default'=>0),
        'creditOK' => array('type'=>'TINYINT', 'not_null'=>True, 'default'=>0),
        'inputOK' => array('type'=>'TINYINT', 'not_null'=>True, 'default'=>0),
        'transferOK' => array('type'=>'TINYINT', 'not_null'=>True, 'default'=>0),
        'isBank' => array('type'=>'TINYINT', 'not_null'=>True, 'default'=>0),
        'modified' => array('type'=>'DATETIME', 'not_null'=>True,
            'default'=>"'0000-00-00 00:00:00'"),
        'modifiedBy' => array('type'=>'INT(11)', 'not_null'=>True, 'default'=>0),
        'membershipID' => array('type'=>'INT(11)','primary_key'=>True,
            'increment'=>True)
    );
}

