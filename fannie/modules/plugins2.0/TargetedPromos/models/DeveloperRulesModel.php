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
  @class DeveloperRulesModel
*/
class DeveloperRulesModel extends BasicModel
{

    protected $name = "DeveloperRules";

    protected $columns = array(
    'developerRulesID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'examineMonths' => array('type'=>'TINYINT', 'default'=>2),
    'minVisits' => array('type'=>'TINYINT', 'default'=>1),
    'minVisitAvg' => array('type'=>'MONEY', 'default'=>20),
    'minMonthAvg' => array('type'=>'MONEY', 'default'=>100),
    'activeDays' => array('type'=>'TINYINT', 'default'=>60),
    'couponUPC' => array('type'=>'VARCHAR(13)'),
    'couponExpireDays' => array('type'=>'TINYINT', 'default'=>28),
    'maxIssue' => array('type'=>'TINYINT', 'default'=>3),
    'memberOnly' => array('type'=>'TINYINT', 'default'=>1),
    'includeStaff' => array('type'=>'TINYINT', 'default'=>0),
    );

    protected $preferred_db = 'plugin:TargetedPromosDB';
}

