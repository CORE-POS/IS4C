<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

namespace COREPOS\pos\lib\models\op;
use COREPOS\pos\lib\models\BasicModel;

/**
  @class MasterSuperDeptsModel
*/
class MasterSuperDeptsModel extends BasicModel
{

    protected $name = "MasterSuperDepts";

    protected $preferred_db = 'op';

    protected $columns = array(
    'superID' => array('type'=>'INT'),
    'super_name' => array('type'=>'VARCHAR(50)'),
    'dept_ID' => array('type'=>'INT'),
    );

    public function doc()
    {
        return '
Use:
A department may belong to more than one superdepartment, but
has one "master" superdepartment. This avoids duplicating
rows in some reports. By convention, a department\'s
"master" superdepartment is the one with the lowest superID.
        ';
    }
}

