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

class EquityReport extends FannieReportPage 
{
    public $description = '[Member Equity] lists all equity transactions for a given member';
    public $report_set = 'Membership';

    protected $report_headers = array('Date', 'Receipt', 'Amount', 'Type');
    protected $sort_direction = 1;
    protected $title = "Fannie : Equity Activity Report";
    protected $header = "Equity Activity Report";
    protected $required_fields = array('memNum');

    public function preprocess()
    {
        $this->card_no = FormLib::get('memNum','');

        return parent::preprocess();
    }

    public function report_description_content()
    {
        return array('Activity for account #'.$this->card_no);
    }

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB,$FANNIE_TRANS_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $q = $dbc->prepare_statement("select stockPurchase,trans_num,dept_name,
                year(tdate),month(tdate),day(tdate)
                from ".$FANNIE_TRANS_DB.$dbc->sep()."stockpurchases AS s 
                LEFT JOIN departments AS d ON s.dept=d.dept_no
                WHERE s.card_no=? ORDER BY tdate DESC");
        $r = $dbc->exec_statement($q,array($this->card_no));

        $data = array();
        while($w = $dbc->fetch_row($r)) {
            $record = array();
            $record[] = sprintf('%d/%d/%d',$w[4],$w[5],$w[3]);
            if (FormLib::get('excel') !== '') {
                $record[] = $w[1];
            } else {
                $record[] = sprintf('<a href="%sadmin/LookupReceipt/RenderReceiptPage.php?year=%d&month=%d&day=%d&receipt=%s">%s</a>',
                        $FANNIE_URL,$w[3],$w[4],$w[5],$w[1],$w[1]);
            }
            $record[] = sprintf('%.2f', ($w[0] != 0 ? $w[0] : $w[2]));
            $record[] = $w[2];
            $data[] = $record;
        }

        return $data;
    }

    public function form_content()
    {
        return '<form method="get" action="EquityReport.php">
            <b>Member #</b> <input type="text" name="memNum" value="" size="6" />
            <br /><br />
            <input type="submit" value="Get Report" />
            </form>';
    }

}

FannieDispatch::conditionalExec();

