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

class ArReport extends FannieReportPage 
{
    public $description = '[AR/Store Charge] lists all AR/Store Charge transactions for a given member';
    public $report_set = 'Membership';
    public $themed = true;

    protected $report_headers = array('Date', 'Receipt', 'Amount', 'Type');
    protected $sort_direction = 1;
    protected $title = "Fannie : AR Activity Report";
    protected $header = "AR Activity Report";
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
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));
        $q = $dbc->prepare_statement("select charges,trans_num,payments,
                year(tdate),month(tdate),day(tdate)
                from ar_history AS s 
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
                        $this->config->get('URL'),$w[3],$w[4],$w[5],$w[1],$w[1]);
            }
            $record[] = sprintf('%.2f', ($w[0] != 0 ? $w[0] : $w[2]));
            $record[] = $w[0] != 0 ? 'Charge' : 'Payment';
            $data[] = $record;
        }

        return $data;
    }

    public function form_content()
    {
        $this->add_onload_command('$(\'#memNum\').focus()');
        return '<form method="get" action="ArReport.php">
            <label>Member #</label>
            <input type="text" name="memNum" value="" class="form-control"
                required id="memNum" />
            <p>
            <button type="submit" class="btn btn-default">Get Report</button>
            </p>
            </form>';
    }

    public function helpContent()
    {
        return '<p>
            View all Accounts Receivable (AR) activity for a given member.
            Enter the desired member number.
            </p>';
    }

}

FannieDispatch::conditionalExec();

