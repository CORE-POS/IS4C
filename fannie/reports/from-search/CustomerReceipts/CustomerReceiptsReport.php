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

use COREPOS\Fannie\API\lib\Store;

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class CustomerReceiptsReport extends FannieReportPage 
{
    public $discoverable = false; // not directly runnable; must start from search

    protected $title = "Fannie : Customer Receipts";
    protected $header = "Customer Receipts";

    protected $report_headers = array('Date & Time', 'Receipt', 'Account', 'UPC', 'Description', 'Qty', '$');
    protected $required_fields = array('id');
    protected $new_tablesorter = true;

    function report_description_content()
    {
        $FANNIE_URL = $this->config->get('URL');
        $ret = array();
        if ($this->report_format == 'html') {
            $date1 = FormLib::get('date1', date('Y-m-d'));
            $date2 = FormLib::get('date2', date('Y-m-d'));
            $ret[] = '<p><form action="CustomerReceiptsReport.php" method="get" class="form-inline">';
            $ret[] = "<span style=\"color:black; display:inline;\">
                    From: 
                    <input type=\"text\" name=\"date1\" size=\"10\" value=\"$date1\" id=\"date1\" />
                    to: 
                    <input type=\"text\" name=\"date2\" size=\"10\" value=\"$date2\" id=\"date2\" />
                    </span><input type=\"submit\" value=\"Change Dates\" />
                    <style type=\"text/css\">
                    .ui-datepicker {
                        z-index: 999 !important;
                    }
                    </style>";
            foreach(FormLib::get('id', array()) as $id) {
                $ret[] = sprintf('<input type="hidden" name="id[]" value="%d" />', $id);
            }
            $ret[] = '</form></p>';
            $this->addOnloadCommand("\$('#date1').datepicker({dateFormat:'yy-mm-dd'});");
            $this->addOnloadCommand("\$('#date2').datepicker({dateFormat:'yy-mm-dd'});");
        }

        return $ret;
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $date1 = FormLib::get('date1', date('Y-m-d'));
        $date2 = FormLib::get('date2', date('Y-m-d'));
        $dlog = DTransactionsModel::selectDlog($date1, $date2);

        $ids = FormLib::get('id', array());
        list($inStr, $args) = $dbc->safeInClause($ids);
        $args[] = $date1;
        $args[] = $date2 . ' 23:59:59';
        $qtyMix = array();
        $saleMix = array();
        $prep = $dbc->prepare("SELECT
                d.tdate, d.upc, d.description, d.total, d.quantity, d.trans_num, d.card_no
            FROM {$dlog} AS d
            WHERE d.card_no IN ({$inStr})
                AND d.tdate BETWEEN ? AND ?
                AND d.trans_type in ('I', 'D', 'A', 'S', 'T')
            ORDER BY tdate, trans_id");
        $res = $dbc->execute($prep, $args);
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = array(
                $row['tdate'],
                $row['trans_num'],
                $row['card_no'],
                $row['upc'],
                $row['description'],
                sprintf('%.2f', $row['quantity']),
                sprintf('%.2f', $row['total']),
            );
        }

        return $data;
    }

    public function form_content()
    {
        global $FANNIE_URL;
        return "Use <a href=\"{$FANNIE_URL}mem/AdvancedMemSearch.php\">Search</a> to
            select customers for this report";;
    }
}

FannieDispatch::conditionalExec();

