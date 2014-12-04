<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__).'/../../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

/**
  @class HouseCouponEditor
*/
class HouseCouponEditor extends FanniePage 
{

    public $description = "
    Module for managing in store coupons
    ";
    public $themed = true;

    protected $header = "Fannie :: House Coupons";
    protected $title = "House Coupons";

    private $display_function;
    private $coupon_id;

    function preprocess(){
        global $FANNIE_OP_DB;
        $this->display_function = 'list_house_coupons';

        $msgs = array();
        if (FormLib::get_form_value('edit_id','') !== '') {
            $this->coupon_id = (int)FormLib::get_form_value('edit_id',0);
            $this->display_function = 'edit_coupon';
        } elseif (FormLib::get_form_value('new_coupon_submit') !== '') {
            $dbc = FannieDB::get($FANNIE_OP_DB);

            $maxQ = $dbc->prepare_statement("SELECT max(coupID) from houseCoupons");
            $max = array_pop($dbc->fetch_row($dbc->exec_statement($maxQ)));
            $this->coupon_id = $max+1;
            
            $insQ = $dbc->prepare_statement("INSERT INTO houseCoupons (coupID) values (?)");
            $dbc->exec_statement($insQ,array($this->coupon_id));

            $this->display_function='edit_coupon';

            $msgs[] = array('type'=>'success', 'text'=>'Created new coupon');
            
            $dbc->close();
        } elseif (FormLib::get_form_value('submit_save') !== '' 
          || FormLib::get_form_value('submit_add_upc') !== ''
          || FormLib::get_form_value('submit_delete_upc') !== '' 
          || FormLib::get_form_value('submit_add_dept') !== ''
          || FormLib::get_form_value('submit_delete_dept') !== '' ) {

            $dbc = FannieDB::get($FANNIE_OP_DB);

            $this->coupon_id = FormLib::get_form_value('cid',0);
            $expires = FormLib::get_form_value('expires');
            if ($expires == '') $expires = null;
            $limit = FormLib::get_form_value('limit',1);
            $mem = FormLib::get_form_value('memberonly',0);
            $dept = FormLib::get_form_value('dept',0);
            $dtype = FormLib::get_form_value('dtype','Q');
            $dval = FormLib::get_form_value('dval',0);
            $mtype = FormLib::get_form_value('mtype','Q');
            $mval = FormLib::get_form_value('mval',0);
            $descript = FormLib::get_form_value('description',0);
            $auto = FormLib::get('autoapply', 0);
            $starts = FormLib::get('starts');

            $model = new HouseCouponsModel($dbc);
            $model->coupID($this->coupon_id);
            $model->startDate($starts);
            $model->endDate($expires);
            $model->limit($limit);
            $model->discountType($dtype);
            $model->discountValue($dval);
            $model->minType($mtype);
            $model->minValue($mval);
            $model->department($dept);
            $model->description($descript);
            $model->memberOnly($mem);
            $model->auto($auto);
            $model->save();

            $msgs[] = array('type'=>'success', 'text'=>'Updated coupon settings');

            $this->display_function = 'edit_coupon';

            if (FormLib::get_form_value('submit_add_upc') !== '' && FormLib::get_form_value('new_upc') !== '') {
                /**
                  Add (or update) a UPC
                */
                $upc = BarcodeLib::padUPC(FormLib::get('new_upc'));
                $type = FormLib::get_form_value('newtype','BOTH');
                $checkP = $dbc->prepare_statement('SELECT upc FROM houseCouponItems WHERE upc=? and coupID=?');
                $check = $dbc->exec_statement($checkP,array($upc,$this->coupon_id));
                if ($dbc->num_rows($check) == 0){
                    $query = $dbc->prepare_statement("INSERT INTO houseCouponItems VALUES (?,?,?)");
                    $dbc->exec_statement($query,array($this->coupon_id,$upc,$type));
                    $msgs[] = array('type'=>'success', 'text'=>'Added item ' . $upc);
                } else {
                    $query = $dbc->prepare_statement("UPDATE houseCouponItems SET type=?
                        WHERE upc=? AND coupID=?");
                    $dbc->exec_statement($query,array($type,$upc,$this->coupon_id));
                    $msgs[] = array('type'=>'success', 'text'=>'Updated item ' . $upc);
                }
            }
            if (FormLib::get_form_value('submit_add_dept') !== '' && FormLib::get_form_value('new_dept') !== ''){
                /**
                  Add (or update) a department
                */
                $dept = (int)FormLib::get_form_value('new_dept',0);
                $type = FormLib::get_form_value('newtype','BOTH');
                $checkP = $dbc->prepare_statement('SELECT upc FROM houseCouponItems WHERE upc=? and coupID=?');
                $check = $dbc->exec_statement($checkP,array($dept,$this->coupon_id));
                if ($dbc->num_rows($check) == 0){
                    $query = $dbc->prepare_statement("INSERT INTO houseCouponItems VALUES (?,?,?)");
                    $dbc->exec_statement($query,array($this->coupon_id,$dept,$type));
                    $msgs[] = array('type'=>'success', 'text'=>'Added department ' . $dept);
                } else {
                    $query = $dbc->prepare_statement("UPDATE houseCouponItems SET type=?
                        WHERE upc=? AND coupID=?");
                    $dbc->exec_statement($query,array($type,$dept,$this->coupon_id));
                    $msgs[] = array('type'=>'success', 'text'=>'Updated department ' . $dept);
                }
            } elseif (FormLib::get_form_value('submit_delete_upc') !== '' || FormLib::get_form_value('submit_delete_dept') !== '') {
                /**
                  Delete UPCs and departments
                */
                $query = $dbc->prepare_statement("DELETE FROM houseCouponItems
                    WHERE upc=? AND coupID=?");
                foreach (FormLib::get_form_value('del',array()) as $upc) {
                    $dbc->exec_statement($query,array($upc,$this->coupon_id));
                    $msgs[] = array('type'=>'success', 'text'=>'Deleted ' . $upc);
                }
            }

            foreach ($msgs as $msg) {
                $alert = '<div class="alert alert-' . $msg['type'] . '" role="alert">'
                    . '<button type="button" class="close" data-dismiss="alert">'
                    . '<span>&times;</span></button>'
                    . $msg['text'] . '</div>';
                $this->add_onload_command("\$('div.navbar-default').after('{$alert}');");
            }
        }

        return true;
    }

    function body_content(){
        $func = $this->display_function;
        return $this->$func();
    }

    function list_house_coupons(){
        global $FANNIE_OP_DB, $FANNIE_URL;
        $this->add_script($FANNIE_URL . 'src/javascript/fancybox/jquery.fancybox-1.3.4.js?v=1');
        $this->add_css_file($FANNIE_URL . 'src/javascript/fancybox/jquery.fancybox-1.3.4.css');
        $dbc = FannieDB::get($FANNIE_OP_DB);
        
        $ret = '<form action="HouseCouponEditor.php" method="get">';
        $ret .= '<p>';
        $ret .= '<button type="submit" name="new_coupon_submit" 
            class="btn btn-default" value="New Coupon">New Coupon</button>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<button type="button" class="fancybox-btn btn btn-default"
            href="explainify.html">Explanation of Settings</button>';
        $this->add_onload_command('$(\'.fancybox-btn\').fancybox();');
        $ret .= '</p>';
        $ret .= '</form>';
        $ret .= '<table class="table">';
        $ret .= '<tr><th>ID</th><th>Value</th><th>Expires</th></tr>';
        $model = new HouseCouponsModel($dbc);
        foreach($model->find('coupID') as $obj) {
            $ret .= sprintf('<tr><td>#%d <a href="HouseCouponEditor.php?edit_id=%d">Edit</a></td>
                    <td>%s</td><td>%.2f%s</td><td>%s</td></tr>',
                    $obj->coupID(),$obj->coupID(),$obj->description(),
                    $obj->discountValue(), $obj->discountType(), $obj->endDate());
        }
        $ret .= '</table>';
        
        $dbc->close();
        return $ret;
    }

    function edit_coupon(){
        global $FANNIE_URL;
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        
        $depts = array();
        $query = $dbc->prepare_statement("SELECT dept_no,dept_name FROM departments ORDER BY dept_no");
        $result = $dbc->exec_statement($query);
        while($row = $dbc->fetch_row($result)){
            $depts[$row[0]] = $row[1];
        }

        $cid = $this->coupon_id;
        $model = new HouseCouponsModel($dbc);
        $model->coupID($cid);
        $model->load();

        $starts = $model->startDate();
        if (strstr($starts,' ')) {
            list($starts, $time) = explode(' ',$starts, 2);
        }
        $expires = $model->endDate();
        if (strstr($expires,' ')) {
            list($expires, $time) = explode(' ',$expires, 2);
        }
        $limit = $model->limit();
        $mem = $model->memberOnly();
        $dType = $model->discountType();
        $dVal = $model->discountValue();
        $mType = $model->minType();
        $mVal = $model->minValue();
        $dept = $model->department();
        $description = $model->description();
        $auto = $model->auto();

        $ret = '<form class="form-horizontal" action="HouseCouponEditor.php" method="post">';
        $ret .= '<input type="hidden" name="cid" value="'.$cid.'" />';

        $ret .= sprintf('
            <div class="row">
                <div class="col-sm-1 text-right">Coupon ID#</div>
                <div class="col-sm-3 text-left">%s</div>
                <div class="col-sm-1 text-right">UPC</div>
                <div class="col-sm-3 text-left">%s</div>
            </div>
            <div class="row">
                <label class="col-sm-1 control-label">Label</label>
                <div class="col-sm-3"><input type=text name=description value="%s" class="form-control" /></div>
                <label class="col-sm-1 control-label">Limit</label>
                <div class="col-sm-3"><input type=text name=limit class="form-control" value="%s" /></div>
            </div>
            <div class="row">
                <label class="col-sm-1 control-label">Begins</label>
                <div class="col-sm-3">
                    <input type=text name=starts value="%s" 
                        id="starts" class="form-control date-field" />
                </div>
                <label class="col-sm-1 control-label">Expires</label>
                <div class="col-sm-3">
                    <input type=text name=expires value="%s" 
                        id="expires" class="form-control date-field" />
                </div>
            </div>
            <div class="row">
                <label class="col-sm-2">Member Only
                <input type=checkbox name=memberonly id=memberonly value="1" %s />
                </label>
                <label class="col-sm-2">Auto-apply
                <input type=checkbox name=autoapply id=autoapply value="1" %s />
                </label>
                <label class="col-sm-1 control-label">Department</label>
                <div class="col-sm-3"><select class="form-control" name=dept>',
            $cid,"00499999".str_pad($cid,5,'0',STR_PAD_LEFT),$description,
            $limit,
            $starts, $expires,
            ($mem==1?'checked':''),
            ($auto==1?'checked':'') 
        );
        foreach($depts as $k=>$v){
            $ret .= "<option value=\"$k\"";
            if ($k == $dept) $ret .= " selected";
            $ret .= ">$k $v</option>";
        }
        $ret .= "</select></div>
            </div>";

        $mts = array(
            'Q'=>'Quantity (at least)',
            'Q+'=>'Quantity (more than)',
            'D'=>'Department (at least $)',
            'D+'=>'Department (more than $)',
            'M'=>'Mixed',
            '$'=>'Total (at least $)',
            '$+'=>'Total (more than $)',
            ''=>'No minimum'
        );
        $ret .= '<div class="row">
            <label class="col-sm-1 control-label">Minimum Type</label>
            <div class="col-sm-3">
            <select class="form-control" name=mtype>';
        foreach($mts as $k=>$v){
            $ret .= "<option value=\"$k\"";
            if ($k == $mType) $ret .= " selected";
            $ret .= ">$v</option>";
        }
        $ret .= "</select></div>
            <label class=\"col-sm-1 control-label\">Minimum value</label>
            <div class=\"col-sm-3\"><input class=\"form-control\" type=text name=mval value=\"$mVal\"
             /></div>
             </div>";

        $dts = array('Q'=>'Quantity Discount',
            'P'=>'Set Price Discount',
            'FI'=>'Scaling Discount (Item)',
            'FD'=>'Scaling Discount (Department)',
            'MD'=>'Capped Discount (Department)',
            'F'=>'Flat Discount',
            '%'=>'Percent Discount (End of transaction)',
            '%D'=>'Percent Discount (Department)',
            'PD'=>'Percent Discount (Anytime)',
            '%C'=>'Percent Discount (Capped)',
            'AD'=>'All Discount (Department)',
        );
        $ret .= '<div class="row">
            <label class="col-sm-1 control-label">Discount Type</label>
            <div class="col-sm-3">
            <select class="form-control" name=dtype>';
        foreach($dts as $k=>$v){
            $ret .= "<option value=\"$k\"";
            if ($k == $dType) $ret .= " selected";
            $ret .= ">$v</option>";
        }
        $ret .= "</select></div>
            <label class=\"col-sm-1 control-label\">Discount value</label>
            <div class=\"col-sm-3\"><input type=text name=dval value=\"$dVal\"
            class=\"form-control\" /></div>
            </div>";

        $ret .= "<br /><button type=submit name=submit_save value=Save class=\"btn btn-default\">Save</button>";
        $ret .= ' | <button type="button" value="Back" class="btn btn-default" 
            onclick="location=\'HouseCouponEditor.php\';return false;">Back</button>';

        if ($mType == "Q" || $mType == "Q+" || $mType == "M") {
            $ret .= "<hr />";
            $ret .= '<div class="form-group form-inline col-sm-6">
                <label class="control-label">Add UPC</label>
                <input type=text class="form-control" name=new_upc />
                <select class="form-control"name=newtype><option>BOTH</option><option>QUALIFIER</option>
                    <option>DISCOUNT</option></select>
                <button type=submit name=submit_add_upc value=Add class="btn btn-default">Add</button>
                </div>';
            $ret .= "<table class=\"table\"
            <tr><th colspan=4>Items</th></tr>";
            $query = $dbc->prepare_statement("SELECT h.upc,p.description,h.type FROM
                houseCouponItems as h LEFT JOIN products AS
                p ON h.upc = p.upc WHERE coupID=?");
            $result = $dbc->exec_statement($query,array($cid));
            while($row = $dbc->fetch_row($result)){
                $ret .= sprintf("<tr><td>%s</td><td>%s</td><td>%s</td>
                    <td><input type=checkbox name=del[] 
                    value=\"%s\" /></tr>",
                    $row[0],$row[1],$row[2],$row[0]);
            }
            $ret .= "</table>";
            $ret .= "<p><button type=submit name=submit_delete_upc value=\"1\"
                class=\"btn btn-default\">Delete Selected Items</button></p>";
        } elseif ($mType == "D" || $mType == "D+" || $dType == '%D') {
            $ret .= "<hr />";
            $ret .= '<div class="form-group form-inline col-sm-6">
                <label class="control-label">Add Dept</label>
                <select class="form-control" name=new_dept>';
            foreach($depts as $k=>$v){
                $ret .= "<option value=\"$k\"";
                $ret .= ">$k $v</option>";
            }   
            $ret .= "</select> ";
            $ret .= '<select class="form-control" name=newtype><option>BOTH</option>
                </select>
                <button type=submit name=submit_add_dept value=Add class="btn btn-default">Add</button>
                </div>';
            $ret .= "<table class=\"table\">
            <tr><th colspan=4>Items</th></tr>";
            $query = $dbc->prepare_statement("SELECT h.upc,d.dept_name,h.type FROM
                houseCouponItems as h LEFT JOIN departments as d
                ON h.upc = d.dept_no WHERE coupID=?");
            $result = $dbc->exec_statement($query,array($cid));
            while($row = $dbc->fetch_row($result)){
                $ret .= sprintf("<tr><td>%s</td><td>%s</td><td>%s</td>
                    <td><input type=checkbox name=del[] 
                    value=\"%s\" /></tr>",
                    $row[0],$row[1],$row[2],$row[0]);
            }
            $ret .= "</table>";
            $ret .= "<p><button type=submit name=submit_delete_dept value=\"1\"
                class=\"btn btn-default\">Delete Selected Departments</button></p>";
        }

        return $ret;
    }

    public function helpContent()
    {
        $help = file_get_contents('explainify.html');
        $extract = preg_match('/<body>(.*)<\/body>/ms', $help, $matches);
        if ($extract) {
            return $matches[1];
        } else {
            return $help;
        }
    }
}

FannieDispatch::conditionalExec(false);

