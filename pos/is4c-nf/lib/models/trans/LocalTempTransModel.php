<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

namespace COREPOS\pos\lib\models\trans;
use COREPOS\pos\lib\models\BasicModel;

/*
if (!class_exists('\\COREPOS\\pos\lib\\models\\trans\\LocalTransModel')) {
    include_once(dirname(__FILE__).'/LocalTransModel.php');
}
*/

/**
  @class LocalTempTransModel
*/
class LocalTempTransModel extends \COREPOS\pos\lib\models\trans\LocalTransModel
{

    protected $name = "localtemptrans";

    // not quite identical to dtransactions
    // need to add increment to trans_id
    public function __construct($con)
    {
        $this->columns['trans_id']['increment'] = true;
        $this->columns['trans_id']['index'] = true;

        parent::__construct($con);
    }

    public function doc()
    {
        return '
Use:
Stores current transaction data. See 
dtransactions for detailed information on
the columns. The only notable difference
is this table has an automatically incremented
trans_id column.
        ';
    }
}

