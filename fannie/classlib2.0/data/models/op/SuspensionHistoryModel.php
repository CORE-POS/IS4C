<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
  @class SuspensionHistoryModel
*/
class SuspensionHistoryModel extends BasicModel 
{

    protected $name = "suspension_history";

    protected $preferred_db = 'op';

    protected $columns = array(
    'username' => array('type'=>'VARCHAR(50)'),
    'postdate' => array('type'=>'DATETIME'),
    'post' => array('type'=>'TEXT'),
    'cardno' => array('type'=>'INT'),
    'reasoncode' => array('type'=>'INT')
    );

    public function doc()
    {
        return '
Depends on:
* suspensions (table)

Use:
This table just keeps a record of member accounts
        ';
    }
}

