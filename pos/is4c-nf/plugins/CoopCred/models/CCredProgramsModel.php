<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op
    Copyright 2014 West End Food Co-op, Toronto, Ontario

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

use COREPOS\pos\lib\models\BasicModel;

/**
  @class CCredProgramsModel
*/
class CCredProgramsModel extends BasicModel
{

    // The actual name of the table.
    protected $name = 'CCredPrograms';
    protected $preferred_db = 'plugin:CoopCredLaneDatabase';

    protected $columns = array(
        'programID' => array('type'=>'SMALLINT(6)', 'default'=>0, 'primary_key'=>True,
            'increment'=>True),
        'programName' => array('type'=>'VARCHAR(100)', 'not_null'=>True,
            'default'=>"''"),
        // default is ignored
        'active' => array('type'=>'TINYINT', 'not_null'=>True, 'default'=>0),
        'startDate' => array('type'=>'DATE', 'not_null'=>True,
            'default'=>"'0000-00-00'"),
        'endDate' => array('type'=>'DATE', 'default'=>'NULL'),
        // FK to op.custdata and CoopCred.Members
        'bankID' => array('type'=>'INT(11)', 'not_null'=>True, 'default'=>0),
        // FK to op.departments
        'paymentDepartment' => array('type'=>'INT(11)', 'not_null'=>True, 'default'=>0),
        'tenderType' => array('type'=>'VARCHAR(2)', 'not_null'=>True, 'default'=>"''"),
        'inputTenderType' => array('type'=>'VARCHAR(2)', 'not_null'=>True, 'default'=>"''"),
        'creditOK' => array('type'=>'TINYINT', 'not_null'=>True, 'default'=>0),
        'inputOK' => array('type'=>'TINYINT', 'not_null'=>True, 'default'=>0),
        'transferOK' => array('type'=>'TINYINT', 'not_null'=>True, 'default'=>0),
        // The most that can be deposited in a Member's account.
        // Is negative, so CCredMemberships.creditBalance must be larger.
        // This is the default for the Program, can be overridden per-member.
        'maxCreditBalance' => array('type'=>'MONEY', 'not_null'=>True, 'default'=>0),
        'modified' => array('type'=>'DATETIME', 'not_null'=>True,
            'default'=>"'0000-00-00 00:00:00'"),
        'modifiedBy' => array('type'=>'INT(11)', 'not_null'=>True, 'default'=>0),
        // Text for departments.dept_name
        'paymentName' => array('type'=>'VARCHAR(30)', 'not_null'=>True, 'default'=>"''"),
        // Text for payment/input keycap, or very short-form references.
        'paymentKeyCap' => array('type'=>'VARCHAR(25)', 'not_null'=>True, 'default'=>"''"),
        // Text for tenders.TenderName
        'tenderName' => array('type'=>'VARCHAR(25)', 'not_null'=>True, 'default'=>"''"),
        // Text for tender keycap, or very short-form references.
        'tenderKeyCap' => array('type'=>'VARCHAR(25)', 'not_null'=>True, 'default'=>"''")
    );

    public function name()
    {
        return $this->name;
    }
}

