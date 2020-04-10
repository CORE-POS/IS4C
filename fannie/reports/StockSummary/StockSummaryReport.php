<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class StockSummaryReport extends FannieReportPage
{
    public $discoverable = false; // probably belongs in WFC specific

    protected $header = 'Stock Summary';
    protected $title = 'Fannie : Stock Summary';

    protected $report_headers = array('Mem#', 'Name', 'Effective Status', 'Status', 'A', 'B', 'Unknown');
    protected $report_cache = 'none';
    protected $required_fields = array('date');
    protected $sortable = false;
    protected $no_sort_but_style = true;

    public function fetch_report_data()
    {
        global $FANNIE_TRANS_DB;
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $date = FormLib::get('date');
        if ($date == '') {
            $date = date('Y-m-d', strtotime('tomorrow'));
        }

        $q = $dbc->prepare("select 
            card_no,
            LastName,FirstName,Type,
            m.memDesc,
            sum(case when tdate <= '2005-11-26 23:59:59' then stockPurchase else 0 end) as unknown,
            sum(case when tdate > '2005-11-26 23:59:59' and dept=992 then stockPurchase else 0 end) as classA,
            sum(case when tdate > '2005-11-26 23:59:59' and dept=991 then stockPurchase else 0 end) as classB
            from ".$FANNIE_TRANS_DB.$dbc->sep()."stockpurchases as s
                left join custdata as c on s.card_no=c.CardNo and c.personNum=1
                LEFT JOIN memtype AS m ON c.memType=m.memtype
            where card_no > 0
                AND s.tdate <= ?
            group by card_no,LastName,FirstName,Type,m.memDesc
            order by card_no");
        $r = $dbc->execute($q, array($date . ' 23:59:59'));

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
                    $w['memDesc'],
                    $w['classA'],
                    $w['classB'],
                    $w['unknown'],
            );
            $data[] = $record;
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        $sums = array(0, 0, 0);
        foreach ($data as $row) {
            $sums[0] += $row[4];
            $sums[1] += $row[5];
            $sums[2] += $row[6];
        }

        return array('Total', null, null, null, $sums[0], $sums[1], $sums[2]);
    }

    public function form_content()
    {
        return <<<HTML
<form method="get">
    <div class="form-group">
        <label>As of</label>
        <input type="text" name="date" class="form-control date-field" placeholder="Leave blank for current" />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Get Report</button>
    </div>
HTML;
    }
}

FannieDispatch::conditionalExec();

