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

class RecallReport extends FannieReportPage 
{
    public $description = '[Recall Report] lists names and contact information for everyone who
        purchased a given product. Hopefully rarely used.';

    protected $report_headers = array('Mem#', 'Name', 'Address', 'City', 'State', 'Zip', 'Phone', 'Alt. Phone', 'Email', 'Qty', 'Amt');
    protected $title = "Fannie : Recall Movement";
    protected $header = "Recall Report";
    protected $required_fields = array('date1', 'date2');

    protected $sort_column = 1;

    public function report_description_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $upc = BarcodeLib::padUPC(FormLib::get('upc'));

        $q = $dbc->prepare_statement("SELECT description FROM products WHERE upc=?");
        $r = $dbc->exec_statement($q,array($upc));
        $w = $dbc->fetch_row($r);
        $description = $w[0];

        return array("Purchases for $upc ($description)");
    }

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $upc = BarcodeLib::padUPC(FormLib::get('upc'));
        $date1 = FormLib::get_form_value('date1',date('Y-m-d'));
        $date2 = FormLib::get_form_value('date2',date('Y-m-d'));

        $dlog = DTransactionsModel::selectDlog($date1,$date2);

        $q = $dbc->prepare_statement("SELECT d.card_no,c.LastName,c.FirstName,m.street,m.city,m.state,
                m.zip,m.phone,m.email_2,m.email_1,sum(quantity) as qty,
                sum(total) as amt
            FROM $dlog AS d LEFT JOIN custdata AS c
            ON c.CardNo=d.card_no AND c.personNum=1
            LEFT JOIN meminfo AS m ON m.card_no=c.CardNo
            WHERE d.upc=? AND 
            tdate BETWEEN ? AND ?
            GROUP BY d.card_no,c.FirstName,c.LastName,m.street,m.city,
            m.state,m.zip,m.phone,m.email_1,m.email_2
            ORDER BY c.LastName,c.FirstName");
        $r = $dbc->exec_statement($q,array($upc,$date1.' 00:00:00',$date2.' 23:59:59'));

        $data = array();
        while($w = $dbc->fetch_row($r)) {
            $record = array(
                    $w['card_no'],
                    $w['LastName'].', '.$w['FirstName'],
                    $w['street'],
                    $w['city'],
                    $w['state'],
                    $w['zip'],
                    $w['phone'],
                    $w['email_2'],
                    $w['email_1'],
                    sprintf('%.2f', $w['qty']),
                    sprintf('%.2f', $w['amt']),
            );
            $data[] = $record;
        }

        return $data;
    }
        
    public function form_content()
    {
        $this->add_onload_command("\$('#date1').datepicker({dateFormat:'yy-mm-dd'});\n");
        $this->add_onload_command("\$('#date2').datepicker({dateFormat:'yy-mm-dd'});\n");
        return '
            <form action=RecallReport.php method=get>
            <table><tr>
            <th>UPC</th><td><input type=text name=upc /></td>
            <td rowspan="4">'.FormLib::date_range_picker().'</td>
            </tr><tr>
            <th>Start date</th><td><input type=text name=date1 id="date1" /></td>
            </tr><tr>
            <th>End date</th><td><input type=text name=date2 id="date2" /></td>
            </tr><tr>
            <td><input type=submit name=submit value="Get Report" /></td>
            <td><input type=checkbox name=excel id=excel value=xls /><label for=excel>Excel</label></td>
            </tr></table>
            </form>';
    }
}

FannieDispatch::conditionalExec();

?>
