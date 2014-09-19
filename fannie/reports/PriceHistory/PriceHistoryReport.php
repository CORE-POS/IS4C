<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

class PriceHistoryReport extends FannieReportPage
{
    protected $title = "Price Change Report";
    protected $header = "Fannie : Price Change Report";

    protected $report_headers = array('UPC', 'Description', 'Price', 'Date');

    protected $required_fields = array();

    public $description = '[Price History] shows what prices an item as been assigned over a given time period.';

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

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
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
        $sql = FannieDB::get($FANNIE_OP_DB);
        $type = FormLib::get('type');
        if ($type === '') { // not set
            $q = "select h.upc,p.description,price,h.modified,p.normal_price from prodPriceHistory
            as h left join products as p on h.upc=p.upc
              where h.upc = ?
              order by h.upc,h.modified desc";
            $args = array($upc);
        } else if ($type == 'upc') {
            $q = "select h.upc,p.description,price,h.modified from prodPriceHistory
            as h left join products as p on h.upc=p.upc
              where h.upc = ? and h.modified between ? AND ?
              order by h.upc,h.modified";
            $args = array($upc,$start_date.' 00:00:00',$end_date.' 23:59:59');
        } else if ($type == 'department') {
            $q = "select h.upc,p.description,price,h.modified,p.normal_price from prodPriceHistory
            as h left join products as p on h.upc=p.upc
              where department between ? and ? and h.modified BETWEEN ? AND ?
              order by h.upc, h.modified";
            $args = array($dept1,$dept2,$start_date.' 00:00:00',$end_date.' 23:59:59');
            $upc = ''; // if UPC and dept submitted, unset UPC
        } else {
            if ($mtype == 'upc') {
                $q = "select h.upc,p.description,price,h.modified,p.normal_price from prodPriceHistory
                    as h left join products as p on h.upc=p.upc
                    where h.upc like ? and h.modified BETWEEN ? AND ?
                    order by h.upc,h.modified";
                $args = array('%'.$manu.'%',$start_date.' 00:00:00',$end_date.' 23:59:59');
            } else {
                $q = "select p.upc,b.description,p.price,p.modified,b.normal_price
                    from prodPriceHistory as p left join products as x
                    on p.upc = x.upc left join products as b on
                    p.upc=b.upc where x.brand ? and
                    p.modified between ? AND ?
                    order by p.upc,p.modified";
                    $args = array($manu,$start_date.' 00:00:00',$end_date.' 23:59:59');
            }
            $upc = ''; // if UPC and manu submitted, unset UPC
        }
        $p = $sql->prepare_statement($q);
        $r = $sql->exec_statement($p,$args);

        if ($upc !== '') {
            $this->report_headers[] = 'Current Price';
        }

        $data = array();
        while ($row = $sql->fetch_array($r)) {
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
        global $FANNIE_OP_DB;
        $sql = FannieDB::get($FANNIE_OP_DB);

        $deptsQ = $sql->prepare_statement("select dept_no,dept_name from departments order by dept_no");
        $deptsR = $sql->exec_statement($deptsQ);
        $deptsList = "";
        while ($deptsW = $sql->fetch_array($deptsR)) {
            $deptsList .= "<option value=$deptsW[0]>$deptsW[0] $deptsW[1]</option>";
        }
        
        $this->add_onload_command("showUPC();\n");
        ob_start();
        ?>
<form method=get action="<?php echo $_SERVER['PHP_SELF']; ?>">
Type: <input type=radio id=radioU name=type value=upc onclick=showUPC() checked /> UPC 
<input type=radio id=radioD name=type value=department onclick=showDept() /> Department 
<input type=radio id=radioM name=type value=manufacturer onclick=showManu() /> <?php echo _('Manufacturer'); ?>
<br />

<div id=upcfields>
UPC: <input type=text name=upc /><br />
</div>

<div id=departmentfields>
Department Start: <input type=text id=dept1 size=4 name=dept1 />
<select id=d1s><?php echo $deptsList; ?></select><br />
Department End: <input type=text id=dept2 size=4 name=dept2 />
<select id=d2s><?php echo $deptsList; ?></select><br />
</div>

<div id=manufacturerfields>
<?php echo _('Manufacturer'); ?>: <input type=text name=manufacturer /><br />
<input type=radio name=mtype value=upc checked /> UPC prefix 
<input type=radio name=mtype value=name /> <?php echo _('Manufacturer name'); ?><br />
</div>

<table>
<tr>
<th>Start Date</th><td><input type=text id=date1 name=date1 /></td>
<td rowspan="2">
<?php echo FormLib::dateRangePicker(); ?>
</td>
</tr>
<tr>
<th>End Date</th><td><input type=text id=date2 name=date2 /></td>
</table>
<input type=submit name=Submit /> <input type=checkbox name=excel value="xls" /> Excel
</form>
        <?php
        return ob_get_clean();
    }

    public function javascript_content()
    {
        ob_start();
        ?>
function showUPC(){
    $('#radioU').attr('checked',true);
    document.getElementById('upcfields').style.display='block';
    document.getElementById('departmentfields').style.display='none';
    document.getElementById('manufacturerfields').style.display='none';
}
function showDept(){
    $('#radioD').attr('checked',true);
    document.getElementById('upcfields').style.display='none';
    document.getElementById('departmentfields').style.display='block';
    document.getElementById('manufacturerfields').style.display='none';
}
function showManu(){
    $('#radioM').attr('checked',true);
    document.getElementById('upcfields').style.display='none';
    document.getElementById('departmentfields').style.display='none';
    document.getElementById('manufacturerfields').style.display='block';
}
$(document).ready(function(){
    showUPC();
    $('#date1').datepicker();
    $('#date2').datepicker();
    $('#d1s').change(function(){
        $('#dept1').val($('#d1s').val());
    });
    $('#d2s').change(function(){
        $('#dept2').val($('#d2s').val());
    });
});
        <?php
        return ob_get_clean();
    }

    public function css_content()
    {
        ob_start();
        ?>
<style type=text/css>
#departmentfields{
    display:none;
}
#manufacturerfields{
    display:none;
}
        <?php
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

