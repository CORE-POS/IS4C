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
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class SmartDDDReport extends FannieReportPage 
{
    protected $report_cache = 'none';
    protected $title = "Fannie :  Extended DDD Report";
    protected $header = "Extended Report";

    protected $required_fields = array('date1', 'date2');

    public $themed = true;
    private $mode = 'PLU';

    public function preprocess()
    {
        $this->mode = FormLib::get('sort', 'PLU');

        return parent::preprocess();
    }

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
                        $dates_form .= sprintf('<input type="hidden" name="%s[]" value="%s" />', $key, $v);
                    }
                } else {
                    $dates_form .= sprintf('<input type="hidden" name="%s" value="%s" />', $key, $value);
                }
            }
        }
        foreach ($_POST as $key => $value) {
            if ($key != 'date1' && $key != 'date2' && $key != 'store') {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $dates_form .= sprintf('<input type="hidden" name="%s[]" value="%s" />', $key, $v);
                    }
                } else {
                    $dates_form .= sprintf('<input type="hidden" name="%s" value="%s" />', $key, $value);
                }
            }
        }
        $stores = FormLib::storePicker();
        $dates_form .= '
            <label>Start Date</label>
            <input class="date-field" type="text" name="date1" value="' . FormLib::get('date1') . '" /> 
            <label>End Date</label>
            <input class="date-field" type="text" name="date2" value="' . FormLib::get('date2') . '" /> 
            <input type="hidden" name="excel" value="" id="excel" />
            ' . $stores['html'] . '
            <button type="submit" onclick="$(\'#excel\').val(\'\');return true;">Change Dates</button>
            <button type="submit" onclick="$(\'#excel\').val(\'csv\');return true;">Download</button>
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
        $dbc->selectDB($this->config->get('OP_DB'));
        $query = '';
        $from_where = FormLib::standardItemFromWhere();
        $dlog = DTransactionsModel::selectDlog($this->form->date1, $this->form->date2);
        $dtrans = DTransactionsModel::selectDTrans($this->form->date1, $this->form->date2);
        $from_where['query'] = str_replace($dlog, $dtrans, $from_where['query']);
        $from_where['query'] = str_replace('tdate', 'datetime', $from_where['query']);
        $query = "
            SELECT t.upc,
                COALESCE(p.brand, '') AS brand,
                CASE WHEN p.description IS NULL THEN t.description ELSE p.description END as description, "
                . DTrans::sumQuantity('t')." as qty,
                SUM(t.cost) AS total,
                t.department,
                d.dept_name,
                m.super_name,
                v.vendorName AS distributor,
                i.sku
            " . $from_where['query'] . "
                AND trans_status='Z'
                AND emp_no <> 9999
                AND register_no <> 99
                AND t.upc <> '0'
            GROUP BY t.upc,
                COALESCE(p.brand, ''),
                CASE WHEN p.description IS NULL THEN t.description ELSE p.description END,
                t.department,
                d.dept_name,
                m.super_name,
                v.vendorName
            ORDER BY SUM(t.total) DESC";
        $prep = $dbc->prepare($query);
        $saleQ = str_replace("='Z'", "<>'Z'", $query);
        $saleQ = str_replace("SUM(t.cost)", "SUM(t.total)", $saleQ);
        $saleP = $dbc->prepare($saleQ);
        try {
            $result = $dbc->execute($prep, $from_where['args']);
            $saleR = $dbc->execute($saleP, $from_where['args']);
        } catch (Exception $ex) {
            // MySQL 5.6 GROUP BY problem
            return array();
        }
        $sales = array();
        while ($row = $dbc->fetchRow($saleR)) {
            $upc = $row['upc'];
            if ($upc == '0068031488891') echo $row['total'];
            $sales[$upc] = array(
                'qty' => $row['qty'],
                'ttl' => $row['total'],
            );
        }
        $data = array();
        while ($row = $dbc->fetch_row($result)) {
                $data[] = array(
                    $row['upc'],
                    $row['brand'],
                    $row['description'],
                    sprintf('%.2f', $row['qty']),
                    sprintf('%.2f', $row['total']),
                    //sprintf('%.2f', isset($sales[$row['upc']]['qty']) ? $sales[$row['upc']]['qty'] : 0),
                    //sprintf('%.2f', isset($sales[$row['upc']]['ttl']) ? $sales[$row['upc']]['ttl'] : 0),
                    $row['department'],
                    $row['dept_name'],
                    $row['super_name'],
                    $row['distributor'],
                    $row['sku'] == $row['upc'] ? '' : $row['sku'],
                );
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        $this->report_headers = array('UPC','Brand','Description','Shrink Qty','Shrink $',
            //'Sales Qty', 'Sales $',
            'Dept#','Department','Super','Vendor', 'SKU');
        $this->sort_column = 4;
        $this->sort_direction = 1;
        $sumQty = 0.0;
        $sumSales = 0.0;
        foreach($data as $row) {
            $sumQty += $row[3];
            $sumSales += $row[4];
        }
        return array('Total',null,null,$sumQty,$sumSales,'',null,null,null);
    }

    public function form_content()
    {
        ob_start();
        ?>
        <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <div class="row">
            <?php echo FormLib::standardItemFields(); ?>
            <?php echo FormLib::standardDateFields(); ?>
        </div>
        <div class="row form-horizontal">
            <div class="form-group">
                <label class="col-sm-1 control-label">Store</label>
                <div class="col-sm-2">
                    <?php $s = FormLib::storePicker(); echo $s['html']; ?>
                </div>
                <label class="col-sm-2 control-label">
                    <input type="checkbox" name="excel" value="csv" />
                    Excel
                </label>
            </div>
        </div>
        <p>
            <button type="submit" class="btn btn-default btn-core">Get Report</button>
            <button type="reset" class="btn btn-default btn-reset">Reset Form</button>
        </p>
        </form>
        <?php

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>
            This tool performs a variety of different movement reports.
            Select the set of items by department, brand, vendor, or
            like code using the appropriate tabs. 
            </p>
            <p>
            Identifying items by department provides a super department
            choice and/or a range of departments and/or a range of
            sub departments. Identifying by brand allows either a text
            brand name or a numeric UPC prefix. Vendor simply allows
            a choice of vendor. Like code provides a range of like
            codes.
            </p>
            <p>
            After choosing a method for identifying a set of items
            and defining a date range, there is also an option to choose
            how the report is summed. PLU will show one row for each
            item sold. Date will show one row for each day in the period.
            Department will show one row for each department that
            has sales. Weekday is similar to date but combines each Monday,
            Tuesday, etc into a single row.
            </p>'; 
    }
}

FannieDispatch::conditionalExec();


