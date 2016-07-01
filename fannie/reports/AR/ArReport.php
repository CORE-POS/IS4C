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

    protected $report_headers = array('Date', 'Receipt', 'Amount', 'Type');
    protected $sort_direction = 1;
    protected $title = "Fannie : AR Activity Report";
    protected $header = "AR Activity Report";
    protected $required_fields = array('memNum');

    public function report_description_content()
    {
        return array('Activity for account #'.$this->form->memNum);
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));
        $data = array();
        foreach (array('ar_history_today', 'ar_history') as $table) {
            $prep = $dbc->prepare("
                SELECT charges,trans_num,payments,
                    year(tdate),month(tdate),day(tdate)
                FROM {$table}
                WHERE card_no=? 
            ORDER BY tdate DESC");
            $res = $dbc->execute($prep,array($this->form->memNum));

            while ($row = $dbc->fetchRow($res)) {
                $data[] = $this->rowToRecord($row);
            }
        }

        return $data;
    }

    private function rowToRecord($row)
    {
        $record = array();
        $record[] = sprintf('%d/%d/%d',$row[4],$row[5],$row[3]);
        if (FormLib::get('excel') !== '') {
            $record[] = $row[1];
        } else {
            $record[] = sprintf('<a href="%sadmin/LookupReceipt/RenderReceiptPage.php?year=%d&month=%d&day=%d&receipt=%s">%s</a>',
                    $this->config->get('URL'),$row[3],$row[4],$row[5],$row[1],$row[1]);
        }
        $record[] = sprintf('%.2f', ($row[0] != 0 ? $row[0] : $row[2]));
        $record[] = $row[0] != 0 ? 'Charge' : 'Payment';

        return $record;
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

    public function unitTest($phpunit)
    {
        $data = array(0, '1-1-1', 0, 2000, 1, 1);
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
    }
}

FannieDispatch::conditionalExec();

