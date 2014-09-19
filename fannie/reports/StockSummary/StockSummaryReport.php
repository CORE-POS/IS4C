<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class StockSummaryReport extends FannieReportPage
{
    public $discoverable = false; // probably belongs in WFC specific

    protected $header = 'Stock Summary';
    protected $title = 'Fannie : Stock Summary';

    protected $report_headers = array('Mem#', 'Name', 'Status', 'A', 'B', 'Unknown');
    protected $report_cache = 'day';

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $q = $dbc->prepare_statement("select 
            card_no,
            LastName,FirstName,Type,
            sum(case when tdate <= '2005-11-26 23:59:59' then stockPurchase else 0 end) as unknown,
            sum(case when tdate > '2005-11-26 23:59:59' and dept=992 then stockPurchase else 0 end) as classA,
            sum(case when tdate > '2005-11-26 23:59:59' and dept=991 then stockPurchase else 0 end) as classB
            from ".$FANNIE_TRANS_DB.$dbc->sep()."stockpurchases as s
            left join custdata as c
            on s.card_no=c.CardNo and c.personNum=1
            where card_no > 0
            group by card_no,LastName,FirstName,Type
            order by card_no");
        $r = $dbc->exec_statement($q);

        $types = array('PC'=>'Member','REG'=>'NonMember',
            'TERM'=>'Termed','INACT'=>'Inactive',
            'INACT2'=>'Term Pending');
        $data = array();
        while($w = $dbc->fetch_row($r)){
            if (!isset($types[$w['Type']])) {
                $w['Type'] = 'REG';
            }
            $record = array(
                    $w['card_no'],
                    $w['LastName'].', '.$w['FirstName'],
                    $types[$w['Type']],
                    $w['classA'],
                    $w['classB'],
                    $w['unknown'],
            );
            $data[] = $record;
        }

        return $data;
    }
}

FannieDispatch::conditionalExec(false);

