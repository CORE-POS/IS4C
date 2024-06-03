<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class SingleItemDDDReport extends FannieReportPage 
{
    public $description = '[Single Item Shrink Report] lists items marked as DDD/shrink at the registers.';
    public $report_set = 'Single Item Shrink';
    public $themed = true;

    protected $title = "Fannie : Single Item Shrink Report";
    protected $header = "Single Item Shrink Report";
    protected $report_headers = array('Store', 'Week 1 (last 7 days)', 'Week 2', 'Week 3', 'Week 4', 'Total (last 28 days)');
    protected $required_fields = array('upc');
    protected $sortable = false;
    protected $no_sort_but_style = true;

    public function report_description_content()
    {
        if ($this->report_format != 'html') {
            return array();
        }

        $url = $this->config->get('URL');
        $this->addScript($url . 'src/javascript/jquery.js');
        $this->addScript($url . 'src/javascript/jquery-ui.js');
        $this->addCssFile($url . 'src/javascript/jquery-ui.css');

        $dates_form = '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">';
        foreach ($_GET as $key => $value) {
            if ($key != 'date1' && $key != 'date2' && $key != 'store') {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $dates_form .= sprintf('<input type="hidden" name="%s[]" value="%s" autocomplete="false"  />', $key, $v);
                    }
                } else {
                    $dates_form .= sprintf('<input type="hidden" name="%s" value="%s" autocomplete="false"  />', $key, $value);
                }
            }
        }
        foreach ($_POST as $key => $value) {
            if ($key != 'date1' && $key != 'date2' && $key != 'store') {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $dates_form .= sprintf('<input type="hidden" name="%s[]" value="%s" autocomplete="false" />', $key, $v);
                    }
                } else {
                    $dates_form .= sprintf('<input type="hidden" name="%s" value="%s" autocomplete="false"  />', $key, $value);
                }
            }
        }
        //$stores = FormLib::storePicker();
        $dates_form .= '
            <label>Start Date</label>
            <input class="date-field" type="text" name="date1" value="' . FormLib::get('date1') . '" /> 
            <label>End Date</label>
            <input class="date-field" type="text" name="date2" value="' . FormLib::get('date2') . '" /> 
            <input type="hidden" name="excel" value="" id="excel" />
            <button type="submit" onclick="$(\'#excel\').val(\'\');return true;">Change Dates</button>
            </form>
            <style type="text/css">
            .ui-datepicker {
                z-index: 999 !important;
            }
            </style>';

        $this->add_onload_command("\$('.date-field').datepicker({dateFormat:'yy-mm-dd'});");
        
        return array($dates_form);
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $tx_dbc = $dbc;
        $op_dbc = $dbc;
        $op_dbc->selectDB($this->config->get('OP_DB'));
        $upc = FormLib::get('upc');
        $upc = BarcodeLib::padUPC($upc);
        $data = array();

        $date1 = FormLib::get('date1', false);
        $date2 = FormLib::get('date2', false);

        $stores = array();
        $args = array($upc);
        $prep = $op_dbc->prepare("SELECT storeID, description FROM Stores WHERE hasOwnItems = 1;");
        $res = $op_dbc->execute($prep);
        while ($row = $op_dbc->fetchRow($res)) {
            $stores[$row['storeID']] = $row['description'];
        }

        $tx_dbc->selectDB($this->config->get('TRANS_DB'));
        
        if ($date1 == false) {
            // No date selected, view loss by weeks going back 28 days
            foreach($stores as $id => $storeName) {
                $args = array($upc, $id);
                $prep = $tx_dbc->prepare("
                    SELECT 
                        SUM(CASE WHEN DATE(datetime) >= DATE(NOW()) - INTERVAL 7 DAY THEN ItemQtty ELSE 0 END) AS Week1,
                        SUM(CASE WHEN DATE(datetime) < DATE(NOW()) - INTERVAL 7 DAY AND DATE(datetime) >= DATE(NOW()) - INTERVAL 14 DAY THEN ItemQtty ELSE 0 END) AS Week2,
                        SUM(CASE WHEN DATE(datetime) < DATE(NOW()) - INTERVAL 14 DAY AND DATE(datetime) >= DATE(NOW()) - INTERVAL 21 DAY THEN ItemQtty ELSE 0 END) AS Week3,
                        SUM(CASE WHEN DATE(datetime) < DATE(NOW()) - INTERVAL 21 DAY AND DATE(datetime) >= DATE(NOW()) - INTERVAL 28 DAY THEN ItemQtty ELSE 0 END) AS Week4,
                        SUM(CASE WHEN DATE(datetime) > DATE(NOW()) - INTERVAL 28 DAY THEN ItemQtty ELSE 0 END) AS Total 
                    FROM transarchive 
                    WHERE trans_status =  'Z' 
                        AND upc = ? 
                        AND store_id = ?;");
                $res = $tx_dbc->execute($prep, $args);
                $row = $tx_dbc->fetchRow($res);
                $w1 = ($row['Week1'] > 0) ? $row['Week1'] : 0;
                $w2 = ($row['Week2'] > 0) ? $row['Week2'] : 0;
                $w3 = ($row['Week3'] > 0) ? $row['Week3'] : 0;
                $w4 = ($row['Week4'] > 0) ? $row['Week4'] : 0;
                $w1 = round($w1, 2);
                $w2 = round($w2, 2);
                $w3 = round($w3, 2);
                $w4 = round($w4, 2);
                $w1 = round($w1, 2);
                $sum = ($row['Total'] > 0) ? $row['Total'] : 0;
                $sum = round($sum, 2);
                $data[] = array($storeName, $w1, $w2, $w3, $w4, "<b>".$sum."</b>");
            }
        } else {
            // give loss over date range as sum
            foreach($stores as $id => $storeName) {
                $args = array($upc, $id, $date1, $date2);
                $prep = $tx_dbc->prepare("
                    SELECT 
                        SUM(ItemQtty) AS total
                    FROM transarchive 
                    WHERE trans_status =  'Z' 
                        AND upc = ? 
                        AND store_id = ?
                        AND datetime >= ? 
                        AND datetime < ?
                    ;");
                $res = $tx_dbc->execute($prep, $args);
                $row = $tx_dbc->fetchRow($res);
                $total = $row['total'];
                $data[] = array($storeName, "<b>".$total."</b>");
            }

            $this->report_headers = array('Store', 'Total');
        }

        return $data;
    }
    
    public function form_content()
    {
        return <<<HTML
<form action="SingleItemDDDReport.php" method="get">
    <div class="row">
        <div class="col-lg-2">
            <div class="form-group">
                <label for="name">UPC</label>
                <input class="form-control" type="text" name="upc"/>
            </div>
            <div class="form-group">
                <button class="btn btn-default">Submit</button>
            </div>
        </div>
    </div>
</form>
HTML;
    }

    public function helpContent()
    {
        return '<p>
        Enter a UPC to view product loss over the past 4 week4 weekss.
            </p>';
    }

}

FannieDispatch::conditionalExec();

