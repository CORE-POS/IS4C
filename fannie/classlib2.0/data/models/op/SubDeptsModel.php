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
  @class SubDeptsModel
*/
class SubDeptsModel extends BasicModel
{

    protected $name = "subdepts";
    protected $preferred_db = 'op';

    protected $columns = array(
    'subdept_no' => array('type'=>'SMALLINT', 'primary_key'=>true),
    'subdept_name' => array('type'=>'VARCHAR(30)'),
    'dept_ID' => array('type'=>'SMALLINT'),
    );

    public function doc()
    {
        return '
Depends on:
* departments (table)

Use:
A department may contain multiple subdepartments.
In most implementations I\'ve seen, invidual products
can be tagged with a subdepartment, but that
setting doesn\'t go into the final transaction log
        ';
    }
}

