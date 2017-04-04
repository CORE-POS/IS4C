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

class RecallReport extends FannieReportPage 
{
    public $description = '[Recall Report] lists names and contact information for everyone who
        purchased a given product during a date range. Hopefully rarely used.';
    public $themed = true;
    public $report_set = 'Membership';

    protected $report_headers = array('Mem#', 'Name', 'Address', 'City', 'State', 'Zip', 'Phone', 'Alt. Phone', 'Email', 'Qty', 'Amt');
    protected $title = "Fannie : Recall Report";
    protected $header = "Recall Report";
    protected $required_fields = array('date1', 'date2');

    protected $sort_column = 1;

    public function report_description_content()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $upc = BarcodeLib::padUPC(FormLib::get('upc'));

        $q = $dbc->prepare("SELECT description FROM products WHERE upc=?");
        $r = $dbc->execute($q,array($upc));
        $w = $dbc->fetch_row($r);
        $description = $w[0];

        return array("Purchases for $upc ($description)");
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $upc = BarcodeLib::padUPC(FormLib::get('upc'));
        $date1 = $this->form->date1;
        $date2 = $this->form->date2;

        $dlog = DTransactionsModel::selectDlog($date1,$date2);

        $q = $dbc->prepare("
            SELECT d.card_no,
                sum(quantity) as qty,
                sum(total) as amt
            FROM $dlog AS d 
            WHERE d.upc=? AND 
                tdate BETWEEN ? AND ?
            GROUP BY d.card_no
            ORDER BY d.card_no");
        $r = $dbc->execute($q,array($upc,$date1.' 00:00:00',$date2.' 23:59:59'));

        $data = array();
        while($w = $dbc->fetch_row($r)) {
            $account = \COREPOS\Fannie\API\member\MemberREST::get($w['card_no']);
            if ($account == false) {
                continue;
            }
            $customer = array();
            foreach ($account['customers'] as $c) {
                if ($c['accountHolder']) {
                    $customer = $c;
                    break;
                }
            }
            $record = array(
                    $w['card_no'],
                    $customer['lastName'].', '.$customer['firstName'],
                    $account['addressFirstLine'] . ' ' . $account['addressSecondLine'],
                    $account['city'],
                    $account['state'],
                    $account['zip'],
                    $customer['phone'],
                    $customer['altPhone'],
                    $customer['email'],
                    sprintf('%.2f', $w['qty']),
                    sprintf('%.2f', $w['amt']),
            );
            $data[] = $record;
        }
        return $data;
    }
        
    public function form_content()
    {
        $this->add_onload_command('$(\'#upc\').focus();');
        return '
            <form action=RecallReport.php method=get>
            <div class="col-sm-4">
            <div class="form-group">
                <label>UPC</label>
                <input type=text name=upc class="form-control" 
                    id="upc" required />
            </div>
            <div class="form-group">
                <label>Start date</label>
                <input type=text name=date1 id="date1" required
                    class="form-control date-field" />
            </div>
            <div class="form-group">
                <label>End date</label>
                <input type=text name=date2 id="date2" required
                    class="form-control date-field" />
            </div>
            <div class="form-group">
                <button type=submit name=submit value="Get Report" 
                    class="btn btn-default">Get Report</button>
                <input type=checkbox name=excel id=excel value=xls /> <label for=excel>Excel</label>
            </div>
            </div>
            <div class="col-sm-4">
            '.FormLib::date_range_picker().'
            </div>
            </form>';
    }

    public function helpContent()
    {
        return '<p>
            List all members who purchased a specific product
            in the given range. The original use case was as a
            tool to notify members in the event of a product
            recall.
            </p>';
    }
}

FannieDispatch::conditionalExec();

