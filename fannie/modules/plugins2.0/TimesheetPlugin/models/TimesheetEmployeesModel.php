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
  @class TimesheetEmployeesModel
*/
class TimesheetEmployeesModel extends BasicModel
{

    protected $name = "TimesheetEmployees";
    protected $preferred_db = 'plugin:TimesheetDatabase';

    protected $columns = array(
    'timesheetEmployeeID' => array('type'=>'INT', 'primary_key'=>true),
    'firstName' => array('type'=>'VARCHAR(50)'),
    'lastName' => array('type'=>'VARCHAR(50)'),
    'username' => array('type'=>'VARCHAR(50)'),
    'posMemberID' => array('type'=>'INT'),
    'payrollProviderID' => array('type'=>'VARCHAR(50)'),
    'timeclockToken' => array('type'=>'VARCHAR(255)'),
    'timesheetDepartmentID' => array('type'=>'INT'),
    'primaryShiftID' => array('type'=>'INT'),
    'wage' => array('type'=>'MONEY'),
    'hireDate' => array('type'=>'DATETIME'),
    'active' => array('type'=>'TINYINT', 'default'=>1),
    'clockedIn' => array('type'=>'TINYINT', 'default'=>0),
    'clockInDateTime' => array('type'=>'DATETIME'),
    'clockInShiftID' => array('type'=>'INT'),
    );
}

