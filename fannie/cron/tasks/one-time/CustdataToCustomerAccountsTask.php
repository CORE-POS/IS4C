<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

class CustdataToCustomerAccountsTask extends FannieTask
{

    public $name = 'One-time: Fix store_row_id';

    public $description = '
Load every custdata account once to trigger migration
so that it doesn\'t happen during routine usage
and cause delays.';

    public $schedulable = false;

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        
        $r = $dbc->query('SELECT CardNo FROM custdata WHERE personNum=1 ORDER BY CardNo DESC');
        while ($w = $dbc->fetchRow($r)) {
            echo "Migrating account " . $w['CardNo'] . "\n";
            \COREPOS\Fannie\API\member\MemberREST::get($w['CardNo']);
        }
    }
}
