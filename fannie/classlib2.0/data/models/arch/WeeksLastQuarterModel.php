<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
  @class WeeksLastQuarterModel
*/
class WeeksLastQuarterModel extends BasicModel
{

    protected $name = "weeksLastQuarter";
    protected $preferred_db = 'arch';

    protected $columns = array(
    'weekLastQuarterID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'weekStart' => array('type'=>'DATETIME'),
    'weekEnd' => array('type'=>'DATETIME'),
    );

    public function doc()
    {
        return '
Use:
Keep track of weeks in the last quarter.
This imposes several conventions:
* Weeks start on Monday and end on Sunday, ISO-style
* The current week is ID zero. The previous week is
  ID one. The week before that is ID two, etc.
* The Last Quarter is week IDs one through thirteen

Week #0 is provided for completeness in information.
The other thirteen weeks are used for the last quarter
so any comparisions are between full, 7-day weeks.
        ';
    }
}

