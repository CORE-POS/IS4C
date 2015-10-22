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

namespace COREPOS\pos\lib\models\op;
use COREPOS\pos\lib\models\BasicModel;

/**
  @class MemChargeBalanceModel
*/
class MemChargeBalanceModel extends BasicModel
{

    protected $name = "memchargebalance";
    protected $preferred_db = 'op';

    protected $columns = array(
    'CardNo' => array('type'=>'INT'),
    'availBal' => array('type'=>'MONEY'),
    'balance' => array('type'=>'MONEY'),
    );

    public function doc()
    {
        return '
Use:
DEPRECATED 4Jan14 no longer used

View showing member charge balance. Authoritative,
up-to-the-second data is on the server but a local
lookup is faster if slightly stale data is acceptable.
        ';
    }

    /* disabled because it's a view */
    public function create(){ return false; }
    public function delete(){ return false; }
    public function save(){ return false; }
    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=False){ return 0; }
}

