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
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class PatronageReport extends FannieReportPage 
{
    public $description = '[Patronage] show per-member patronage information by fiscal year. Note this is
    calculated and entered annually, not assembled on the fly from transaction information.';
    public $report_set = 'Membership :: Patronage';
    public $themed = true;

    protected $header = "Patronage Report";
    protected $title = "Fannie : Patronage Report";

    protected $content_function = 'both_content';

    protected $report_headers = array('#', 'Gross Purchases', 'Discounts', 'Rewards', 'Net Purchases', 'Cash Portion', 'Equity Portion', 'Total Rebate');
    protected $no_jquery = true;

    public function preprocess()
    {
        $this->formatCheck();

        return true;
    }

    public function form_content()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $fyearQ = $dbc->prepare("SELECT FY FROM patronage GROUP BY FY ORDER BY FY DESC");
        $fyearR = $dbc->execute($fyearQ);
        $fyear = FormLib::get('fy');

        $ret = '<form action="PatronageReport.php" id="reportForm" 
                    class="form form-inline" method="get">
            <select name="fy" class="form-control" 
                onchange="$(\'#reportForm\').submit();">
            <option value="">Select FY</option>';
        while($fyearW = $dbc->fetch_row($fyearR)){
            $ret .= sprintf('<option value="%d" %s>%d</option>',
                $fyearW['FY'],
                ($fyearW['FY']==$fyear?'selected':''),
                $fyearW['FY']
            );
        }
        $ret .= '</select> <button type="submit" class="btn btn-default">Submit</button></form>';

        return $ret;
    }

    public function report_description_content()
    {
        $fyear = FormLib::get('fy');
        if ($fyear === '') {
            return array();
        } else {
            return array('Patronage Rebate for '.$fyear);
        }
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $fyear = FormLib::get('fy');
        if ($fyear === '') {
            return array();
        }

        $patQ = $dbc->prepare("SELECT cardno,purchase,discounts,rewards,net_purch,
            tot_pat,cash_pat,equit_pat,0 as type,0 as ttl FROM patronage as p
            WHERE p.FY=? ORDER BY cardno");
        $patR = $dbc->execute($patQ,array($fyear));

        $data = array();
        while ($row = $dbc->fetchRow($patR)) {
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

    private function rowToRecord($row)
    {
        return array(
            $row['cardno'],
            sprintf('%.2f',$row['purchase']),
            sprintf('%.2f',$row['discounts']),
            sprintf('%.2f',$row['rewards']),
            sprintf('%.2f',$row['net_purch']),
            sprintf('%.2f',$row['cash_pat']),
            sprintf('%.2f',$row['equit_pat']),
            sprintf('%.2f',$row['tot_pat']),
        );
    }

    public function helpContent()
    {
        return '<p>
            Lists total patronage distribution information
            for all members for a given fiscal year.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $data = array('cardno'=>1, 'purchase'=>10, 'discounts'=>1, 'rewards'=>1,
            'net_purch'=>8, 'cash_pat'=>1, 'equit_pat'=>1, 'tot_pat'=>2);
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
    }

}

FannieDispatch::conditionalExec();

