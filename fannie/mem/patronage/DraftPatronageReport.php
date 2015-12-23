<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op, Duluth, MN

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
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class DraftPatronageReport extends FannieReportPage
{
    protected $title = "Fannie :: Patronage Tools";
    protected $header = "Working Copy Report";

    public $description = '[Draft Patronage Report] shows work-in-progress calculated annual patronage.';
    public $themed = true;

    protected $content_function = 'report_content';
    protected $report_headers = array('#', 'Last Name', 'First Name', 'Active', 'Type',
        'Gross Patronage', 'Discounts', 'Rewards', 'Net Patronage', 'Bad Address');
    protected $no_sort_but_style = true;

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $q = $dbc->prepare("
            SELECT p.cardno,
                c.LastName,
                c.FirstName,
                c.Type,
                CASE WHEN c.Type IN ('REG','PC') THEN a.memDesc 
                    ELSE b.memDesc END AS memDesc,
                p.purchase,
                p.discounts,
                p.rewards,
                p.net_purch,
                CASE WHEN s.reasoncode IS NULL THEN 'No' 
                    WHEN s.reasoncode & 16 <> 0 THEN 'Yes' 
                    ELSE 'No' END AS badAddress
            FROM patronage_workingcopy AS p 
                LEFT JOIN custdata AS c ON p.cardno=c.CardNo AND c.personNum=1
                LEFT JOIN suspensions AS s ON p.cardno=s.cardno
                LEFT JOIN memtype AS a ON c.memType=a.memtype
                LEFT JOIN memtype AS b ON s.memtype1=b.memtype
            ORDER BY p.cardno");
        $r = $dbc->execute($q);
        $data = array();
        while ($w = $dbc->fetch_row($r)) {
            $record = array(
                $w['cardno'],
                $w['LastName'],
                $w['FirstName'],
                $w['Type'],
                $w['memDesc'],
                sprintf('%.2f', $w['purchase']),
                sprintf('%.2f', -1*$w['discounts']),
                sprintf('%.2f', -1*$w['rewards']),
                sprintf('%.2f', $w['net_purch']),
                $w['badAddress'],
            );
            $data[] = $record;
        }

        return $data;
    }

    public function helpContent()
    {
        return '<p>
            Show the in-progress data used for calculating
            patronage rebate info for a given year.
            </p>';
    }

}

FannieDispatch::conditionalExec();

