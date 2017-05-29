<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

class VoidedTransactionsReport extends FannieReportPage 
{
    public $description = '[Voided Transactions] lists all transactions that were created using
    the lane UNDO command';
    public $report_set = 'Cashiering';
    protected $header = 'Voided Transactions';
    protected $title = 'Voided Transactions';

    protected $report_headers = array('Date', 'Receipt', 'Amount', 'Original');
    protected $required_fields = array('date1', 'date2');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));

        $prep = $dbc->prepare('
            SELECT tdate,
                description,
                trans_num,
                total
            FROM voidTransHistory 
            WHERE tdate BETWEEN ? AND ?
            ORDER BY tdate
        ');
        $res = $dbc->execute($prep, array(
            $this->form->date1 . ' 00:00:00',
            $this->form->date2 . ' 23:59:59',
        ));
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $original = substr($row['description'], 20);
            $data[] = array(
                $row['tdate'],
                $row['trans_num'],
                $row['total'],
                \COREPOS\Fannie\API\lib\FannieUI::receiptLink($row['tdate'], $original),
            );
        }

        return $data;
    }

    public function form_content()
    {
        return '<form method="get">
            <div class="container row">'
            . FormLib::standardDateFields() . '
            </div>
            <p>
                <button type="submit" class="btn btn-default btn-core">Submit</button>
            </p>
            </form>';
    }
}

FannieDispatch::conditionalExec();

