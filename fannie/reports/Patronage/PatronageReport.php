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

class PatronageReport extends FannieReportPage 
{
    public $description = '[Patronage] show per-member patronage information by fiscal year. Note this is
    calculated and entered annually, not assembled on the fly from transaction information.';
    public $report_set = 'Membership';

    protected $header = "Patronage Report";
    protected $title = "Fannie : Patronage Report";

    protected $content_function = 'both_content';

    protected $report_headers = array('#', 'Gross Purchases', 'Discounts', 'Rewards', 'Net Purchases', 'Cash Portion', 'Equity Portion', 'Total Rebate');

    public function preprocess()
    {
        $this->formatCheck();

        return true;
    }

    public function form_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $fyQ = $dbc->prepare_statement("SELECT FY FROM patronage GROUP BY FY ORDER BY FY DESC");
        $fyR = $dbc->exec_statement($fyQ);
        $fy = FormLib::get('fy');

        $ret = '<form action="PatronageReport.php" id="reportForm" method="get">
            <select name="fy" onchange="$(\'#reportForm\').submit();">
            <option value="">Select FY</option>';
        while($fyW = $dbc->fetch_row($fyR)){
            $ret .= sprintf('<option value="%d" %s>%d</option>',
                $fyW['FY'],
                ($fyW['FY']==$fy?'selected':''),
                $fyW['FY']
            );
        }
        $ret .= '</select> <input type="submit" value="Submit" /></form>';

        return $ret;
    }

    public function report_description_content()
    {
        $fy = FormLib::get('fy');
        if ($fy === '') {
            return array();
        } else {
            return array('Patronage Rebate for '.$fy);
        }
    }

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $fy = FormLib::get('fy');
        if ($fy === '') {
            return array();
        }

        $pQ = $dbc->prepare_statement("SELECT cardno,purchase,discounts,rewards,net_purch,
            tot_pat,cash_pat,equit_pat,0 as type,0 as ttl FROM patronage as p
            WHERE p.FY=? ORDER BY cardno");
        $pR = $dbc->exec_statement($pQ,array($fy));

        $data = array();
        while($row = $dbc->fetch_row($pR)) {
            $record = array(
                $row['cardno'],
                sprintf('%.2f',$row['purchase']),
                sprintf('%.2f',$row['discounts']),
                sprintf('%.2f',$row['rewards']),
                sprintf('%.2f',$row['net_purch']),
                sprintf('%.2f',$row['cash_pat']),
                sprintf('%.2f',$row['equit_pat']),
                sprintf('%.2f',$row['tot_pat']),
            );
            $data[] = $record;
        }

        return $data;
    }

}

FannieDispatch::conditionalExec();

?>
