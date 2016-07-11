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
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class PriceHistoryReport extends FannieReportPage
{
    protected $title = "Price Change Report";
    protected $header = "Fannie : Price Change Report";

    protected $report_headers = array('UPC', 'Description', 'Price', 'Date');

    protected $required_fields = array();

    public $description = '[Price History] shows what prices an item as been assigned over a given time period.';
    public $themed = true;
    public $report_set = 'Operational Data';

    protected $sort_column = 3;
    protected $sort_direction = 1;

    /**
      Report has variable inputs so change
      required fields before calling default preprocess
    */
    public function preprocess()
    {
        if (FormLib::get('upc') !== '') {
            $this->required_fields[] = 'upc';
        }
        if (FormLib::get('manufacturer') !== '') {
            $this->required_fields[] = 'manufacturer';
        }
        if (FormLib::get('dept1') !== '') {
            $this->required_fields[] = 'dept1';
        }

        if (count($this->required_fields) == 0) {
            $this->required_fields[] = '_no_valid_input';
        }

        return parent::preprocess();
    }

    public function report_description_content()
    {
        if ($this->report_format == 'html') {
            return array(
                '',
                '<a href="../ProductHistory/ProductHistoryReport.php?upc=' . FormLib::get('upc') . '">Full History of this Item</a>',
            );
        } else {
            return array();
        }
    }

    public function fetch_report_data()
    {
        /* provide a department range and date range to
           get history for all products in those departments
           for that time period AND current price

           provide just a upc to get history for that upc
        */
        $dept1 = FormLib::get('dept1');
        $dept2 = FormLib::get('dept2');
        $upc = FormLib::get('upc');
        if ($upc !== '') {
            $upc = BarcodeLib::padUPC($upc);
        }
        $start_date = FormLib::get('date1', date('Y-m-d'));
        $end_date = FormLib::get('date2', date('Y-m-d'));
        $manu = FormLib::get('manufacturer');
        $mtype = FormLib::get('mtype', 'upc');

        $q = "";
        $args = array();
        $sql = $this->connection;
        $sql->selectDB($this->config->get('OP_DB'));
        $type = FormLib::get('type');
        if ($type === '') { // not set
            $q = "
                SELECT h.upc,
                    p.description,
                    price,
                    h.modified,
                    p.normal_price 
                FROM prodPriceHistory AS h 
                    " . DTrans::joinProducts('h') . "
                WHERE h.upc = ?
                ORDER BY h.upc,
                    h.modified DESC";
            $args = array($upc);
        } else if ($type == 'upc') {
            $q = "
                SELECT h.upc,
                    p.description,
                    price,
                    h.modified,
                    p.normal_price 
                FROM prodPriceHistory AS h 
                    " . DTrans::joinProducts('h') . "
                WHERE h.upc = ?
                    AND h.modified BETWEEN ? AND ?
                ORDER BY h.upc,
                    h.modified DESC";
            $args = array($upc,$start_date.' 00:00:00',$end_date.' 23:59:59');
        } else if ($type == 'department') {
            $q = "
                SELECT h.upc,
                    p.description,
                    price,
                    h.modified,
                    p.normal_price 
                FROM prodPriceHistory AS h 
                    " . DTrans::joinProducts('h') . "
                WHERE department BETWEEN ? AND ?
                    AND h.modified BETWEEN ? AND ?
                ORDER BY h.upc,
                    h.modified DESC";
            $args = array($dept1,$dept2,$start_date.' 00:00:00',$end_date.' 23:59:59');
            $upc = ''; // if UPC and dept submitted, unset UPC
        } else {
            if ($mtype == 'upc') {
                $q = "
                    SELECT h.upc,
                        p.description,
                        price,
                        h.modified,
                        p.normal_price 
                    FROM prodPriceHistory AS h 
                        " . DTrans::joinProducts('h') . "
                    WHERE h.upc LIKE ?
                        AND h.modified BETWEEN ? AND ?
                    ORDER BY h.upc,
                        h.modified DESC";
                $args = array('%'.$manu.'%',$start_date.' 00:00:00',$end_date.' 23:59:59');
            } else {
                $q = "
                    SELECT h.upc,
                        p.description,
                        price,
                        h.modified,
                        p.normal_price 
                    FROM prodPriceHistory AS h 
                        " . DTrans::joinProducts('h') . "
                    WHERE x.brand LIKE ?
                        AND h.modified BETWEEN ? AND ?
                    ORDER BY h.upc,
                        h.modified DESC";
                    $args = array($manu,$start_date.' 00:00:00',$end_date.' 23:59:59');
            }
            $upc = ''; // if UPC and manu submitted, unset UPC
        }
        $def = $sql->tableDefinition('prodPriceHistory');
        if (isset($def['storeID']) && $this->config->get('STORE_ID')) {
            $q = str_replace('h.upc=p.upc', 'h.upc=p.upc AND h.storeID=p.store_id', $q);
        }
        $p = $sql->prepare($q);
        $r = $sql->execute($p,$args);

        if ($upc !== '') {
            $this->report_headers[] = 'Current Price';
        }

        $data = array();
        while ($row = $sql->fetchRow($r)) {
            $record = array(
                    $row['upc'],
                    $row['description'],
                    sprintf('%.2f', $row['price']),
                    $row['modified'],
            );
            if ($upc !== '') {
                $record[] = $row['normal_price'];
            }
            $data[] = $record;
        }

        return $data;
    }

    public function form_content()
    {
        $sql = $this->connection;
        $sql->selectDB($this->config->get('OP_DB'));

        $deptsQ = $sql->prepare("select dept_no,dept_name from departments order by dept_no");
        $deptsR = $sql->execute($deptsQ);
        $deptsList = "";
        while ($deptsW = $sql->fetchRow($deptsR)) {
            $deptsList .= "<option value=$deptsW[0]>$deptsW[0] $deptsW[1]</option>";
        }
        
        $this->add_onload_command("showUPC();\n");
        ob_start();
        ?>
<form method=get action="<?php echo $_SERVER['PHP_SELF']; ?>">
<input type="hidden" name="type" id="type-field" value="upc" />
<div class="col-sm-6">
    <ul class="nav nav-tabs">
        <li class="active"><a href="#upc-tab" role="tab"
            onclick="$(this).tab('show'); $('#type-field').val('upc'); return false;">UPC</a></li>
        <li><a href="#dept-tab" role="tab"
            onclick="$(this).tab('show'); $('#type-field').val('department'); return false;">Department</a></li>
        <li><a href="#manu-tab" role="tab"
            onclick="$(this).tab('show'); $('#type-field').val('manufacturer'); return false;"><?php echo _('Manufacturer'); ?></a></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane active" id="upc-tab">
            <label>UPC</label>
            <input type=text name=upc class="form-control" />
        </div>
        <div class="tab-pane" id="dept-tab">
            <p>
                <label class="col-sm-3">Start</label>
                <div class="col-sm-2">
                    <input type=text id=dept1 name=dept1 class="form-control" />
                </div>
                <div class="col-sm-7">
                    <select onchange="$('#dept1').val(this.value);" class="form-control">
                    <?php echo $deptsList; ?>
                    </select>
                </div>
            </p>
            <p>
                <label class="col-sm-3">End</label>
                <div class="col-sm-2">
                    <input type=text id=dept2 name=dept2 class="form-control" />
                </div>
                <div class="col-sm-7">
                    <select onchange="$('#dept2').val(this.value);" class="form-control">
                    <?php echo $deptsList; ?>
                    </select>
                </div>
            </p>
        </div>
        <div class="tab-pane" id="manu-tab">
            <label><?php echo _('Manufacturer'); ?></label>
            <input type=text name=manufacturer class="form-control" />
            <p>
                <label><input type=radio name=mtype value=upc checked /> UPC prefix</label>
                <label><input type=radio name=mtype value=name /> <?php echo _('Manufacturer name'); ?></label>
            </p>
        </div>
    </div>
    <br />
    <p>
        <button type=submit name=Submit class="btn btn-default">Submit</button>
        <label><input type=checkbox name=excel value="xls" /> Excel</label>
    </p>
</div>
<div class="col-sm-6">
    <p>
        <label>Start Date</label>
        <input type="text" id="date1" name="date1" class="form-control date-field" required />
    </p>
    <p>
        <label>End Date</label>
        <input type="text" id="date2" name="date2" class="form-control date-field" required />
    </p>
    <p>
        <?php echo FormLib::dateRangePicker(); ?>
    </p>
</div>
</form>
        <?php
        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>
            List price changes for a given item.
            </p>';
    }
}

FannieDispatch::conditionalExec();

