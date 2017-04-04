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

class MemberPatronageReport extends FannieReportPage 
{
    public $description = '[Member Patronage] shows a member\'s patronage information each fiscal year. Note this is
    calculated and entered annually, not assembled on the fly from transaction information.';
    public $report_set = 'Membership :: Patronage';
    public $themed = true;

    protected $header = "Patronage Report";
    protected $title = "Fannie : Patronage Report";

    protected $new_tablesorter = true;

    protected $report_headers = array('FY', 'Gross Purchases', 'Discounts', 'Rewards', 'Net Purchases', 'Cash Portion', 'Equity Portion', 'Total Rebate');
    protected $required_fields = array('id');

    public function form_content()
    {
        return <<<HTML
<form method="get">
    <div class="form-group">
        <label>Member Number</label>
        <input type="text" name="id" id="member-id" class="form-control" />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Get Report</button>
    </div>
</form>
HTML;
    }

    public function report_description_content()
    {
        $id = $this->form->id;
        if ($id === '') {
            return array();
        } else {
            return array('Patronage Rebate for #' . $id);
        }
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $id = $this->form->id;
        if ($id === '') {
            return array();
        }

        $pQ = $dbc->prepare("
            SELECT cardno,
                purchase,
                discounts,
                rewards,
                net_purch,
                tot_pat,
                cash_pat,
                equit_pat,
                FY,
                0 as type,
                0 as ttl 
            FROM patronage as p
            WHERE p.cardno=? 
            ORDER BY FY");
        $pR = $dbc->execute($pQ,array($id));

        $data = array();
        while ($row = $dbc->fetch_row($pR)) {
            $record = array(
                $row['FY'],
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

    public function helpContent()
    {
        return '<p>
            Lists all patronage distribution information
            for a given membership
            </p>';
    }

}

FannieDispatch::conditionalExec();

