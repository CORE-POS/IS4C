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
  @class DepartmentsModel
*/
class DepartmentsModel extends BasicModel 
{

    protected $name = "departments";

    protected $preferred_db = 'op';

    protected $normalize_lanes = true;

    protected $columns = array(
    'dept_no' => array('type'=>'SMALLINT','primary_key'=>True),
    'dept_name' => array('type'=>'VARCHAR(30)','index'=>True),
    'dept_tax' => array('type'=>'TINYINT'),
    'dept_fs' => array('type'=>'TINYINT'),
    'dept_limit' => array('type'=>'MONEY'),
    'dept_minimum' => array('type'=>'MONEY'),
    'dept_discount' => array('type'=>'TINYINT'),
    'dept_see_id' => array('type'=>'TINYINT', 'default'=>0),
    'modified' => array('type'=>'DATETIME'),
    'modifiedby' => array('type'=>'INT'),
    'margin' => array('type'=>'DOUBLE'),
    'salesCode' => array('type'=>'INT'),
    'memberOnly' => array('type'=>'SMALLINT', 'default'=>0),
    'line_item_discount' => array('type'=>'TINYINT', 'default'=>1),
    'dept_wicable' => array('type'=>'TINYINT', 'default'=>0),
    );

    protected function hookAddColumnmargin()
    {
        if ($this->connection->table_exists('deptMargin')) {
            $dataR = $this->connection->query('SELECT dept_ID, margin FROM deptMargin');
            $tempModel = new DepartmentsModel($this->connection);
            while($dataW = $this->connection->fetch_row($dataR)) {
                $tempModel->reset();
                $tempModel->dept_no($dataW['dept_ID']);
                if ($tempModel->load()) {
                    $tempModel->margin($dataW['margin']);
                    $tempModel->save();
                }
            }
        }
    }

    protected function hookAddColumnsalesCode()
    {
        if ($this->connection->table_exists('deptSalesCodes')) {
            $dataR = $this->connection->query('SELECT dept_ID, salesCode FROM deptSalesCodes');
            $tempModel = new DepartmentsModel($this->connection);
            while($dataW = $this->connection->fetch_row($dataR)) {
                $tempModel->reset();
                $tempModel->dept_no($dataW['dept_ID']);
                if ($tempModel->load()) {
                    $tempModel->salesCode($dataW['salesCode']);
                    $tempModel->save();
                }
            }
        }
    }

    public function doc()
    {
        return '
Use:
Departments are the primary level of granularity
for products. Each product may belong to one department,
and when items are rung up the department setting
is what\'s saved in the transaction log

dept_no and dept_name identify a department

dept_tax,dept_fs, and dept_discount indicate whether
items in that department are taxable, foodstampable,
and discountable (respectively). Mostly these affect
open rings at the register, although WFC also uses
them to speed up new item entry. dept_see_id is for
departments where customers should show ID (e.g., alcohol).
The value is the age required for purchase.

dept_limit and dept_minimum are the highest and lowest
sales allowed in the department. These also affect open
rings. The prompt presented if limits are exceeded is
ONLY a warning, not a full stop.

margin is desired margin for products in the department.
It can be used for calculating retail pricing based
on costs. By convention, values are less than one.
A value of 0.35 means 35% margin. This value has
no meaning on the lane.

salesCode is yet another way of categorizing items.
It is typically used for chart of account numbers.
Often the financial accounting side of the business
wants to look at sales figures differently than
the operational side of the business. It\'s an organizational
and reporting field with no meaning on the lane.

memberOnly restricts sales based on customer membership
status. Values 0 through 99 are reserved. 100 and above
may be used for custom settings. Currently defined values:
    0 => No restrictions
    1 => Active members only (custdata.Type = \'PC\')
    2 => Active members only but cashier can override
    3 => Any custdata account *except* the default non-member account
        ';
    }
}

