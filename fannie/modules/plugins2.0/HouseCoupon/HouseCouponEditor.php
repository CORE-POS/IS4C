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

    protected $header = "Fannie :: House Coupons";
    protected $title = "House Coupons";

    private $display_function;
    private $coupon_id;

    function preprocess(){
        global $FANNIE_OP_DB;
        $this->display_function = 'list_house_coupons';

        if (FormLib::get_form_value('edit_id','') !== ''){
            $this->coupon_id = (int)FormLib::get_form_value('edit_id',0);
            $this->display_function = 'edit_coupon';
        }
        else if (FormLib::get_form_value('new_coupon_submit') !== ''){
            $dbc = FannieDB::get($FANNIE_OP_DB);

            $maxQ = $dbc->prepare_statement("SELECT max(coupID) from houseCoupons");
            $max = array_pop($dbc->fetch_row($dbc->exec_statement($maxQ)));
            $this->coupon_id = $max+1;
            
            $insQ = $dbc->prepare_statement("INSERT INTO houseCoupons (coupID) values (?)");
            $dbc->exec_statement($insQ,array($this->coupon_id));

            $this->display_function='edit_coupon';
            
            $dbc->close();
        }
        else if (FormLib::get_form_value('explain_submit') !== ''){
            include(dirname(__FILE__).'/explainify.html');
            return False;
        }
        else if (FormLib::get_form_value('submit_save') !== '' 
          || FormLib::get_form_value('submit_add_upc') !== ''
          || FormLib::get_form_value('submit_delete_upc') !== '' 
          || FormLib::get_form_value('submit_add_dept') !== ''
          || FormLib::get_form_value('submit_delete_dept') !== '' ){

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

            $this->display_function = 'edit_coupon';

            if (FormLib::get_form_value('submit_add_upc') !== '' && FormLib::get_form_value('new_upc') !== ''){
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
                }
                else {
                    $query = $dbc->prepare_statement("UPDATE houseCouponItems SET type=?
                        WHERE upc=? AND coupID=?");
                    $dbc->exec_statement($query,array($type,$upc,$this->coupon_id));
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
                }
                else {
                    $query = $dbc->prepare_statement("UPDATE houseCouponItems SET type=?
                        WHERE upc=? AND coupID=?");
                    $dbc->exec_statement($query,array($type,$dept,$this->coupon_id));
                }
            }
            elseif (FormLib::get_form_value('submit_delete_upc') !== '' || FormLib::get_form_value('submit_delete_dept') !== ''){
                /**
                  Delete UPCs and departments
                */
                $query = $dbc->prepare_statement("DELETE FROM houseCouponItems
                    WHERE upc=? AND coupID=?");
                foreach(FormLib::get_form_value('del',array()) as $upc){
                    $dbc->exec_statement($query,array($upc,$this->coupon_id));
                }
            }
        }

        return True;
    }

    function body_content(){
        $func = $this->display_function;
        return $this->$func();
    }

    function list_house_coupons(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        
        $ret = '<form action="HouseCouponEditor.php" method="get">';
        $ret .= '<input type="submit" name="new_coupon_submit" value="New Coupon" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="submit" name="explain_submit" value="Explanation of Settings" />';
        $ret .= '</form>';
        $ret .= '<table cellpadding="4" cellspacing="0" border="1" />';
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

        $ret = '<form action="HouseCouponEditor.php" method="post">';
        $ret .= '<input type="hidden" name="cid" value="'.$cid.'" />';

        $ret .= sprintf('<table cellspacing=0 cellpadding=4 border=0>
            <tr>
                <th>Coupon ID#</th>
                <td colspan="3">%s</td>
                <th>UPC</th>
                <td>%s</td>
            </tr>
            <tr>
                <th>Label</th>
                <td colspan=3><input type=text name=description value="%s" size=30 /></td>
                <th>Limit</th>
                <td><input type=text name=limit size=3 value="%s" /></td>
            </tr>
            <tr>
                <th>Begins</th>
                <td colspan="3">
                    <input type=text name=starts value="%s" size=12 
                        id="starts"
                </td>
                <th>Expires</th>
                <td>
                    <input type=text name=expires value="%s" size=12 
                        id="expires"
                </td>
            </tr>
            <tr>
                <th><label for="memberonly">Member-only</label></th>
                <td><input type=checkbox name=memberonly id=memberonly value="1" %s /></td>
                <th align="right"><label for="autoapply">Auto-apply</label></th>
                <td><input type=checkbox name=autoapply id=autoapply value="1" %s /></td>
                <th>Department</th><td><select name=dept>',
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
        $ret .= "</select></td></tr>";

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
        $ret .= "<tr><th>Minimum Type</th><td colspan=3>
            <select name=mtype>";
        foreach($mts as $k=>$v){
            $ret .= "<option value=\"$k\"";
            if ($k == $mType) $ret .= " selected";
            $ret .= ">$v</option>";
        }
        $ret .= "</select></td><th>Minimum value</th>
            <td><input type=text name=mval value=\"$mVal\"
            size=5 /></td></tr>";

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
        $ret .= "<tr><th>Discount Type</th><td colspan=3>
            <select name=dtype>";
        foreach($dts as $k=>$v){
            $ret .= "<option value=\"$k\"";
            if ($k == $dType) $ret .= " selected";
            $ret .= ">$v</option>";
        }
        $ret .= "</select></td><th>Discount value</th>
            <td><input type=text name=dval value=\"$dVal\"
            size=5 /></td></tr>";

        $ret .= "</table>";
        $ret .= "<br /><input type=submit name=submit_save value=Save />";
        $ret .= ' | <input type="submit" value="Back" onclick="location=\'HouseCouponEditor.php\';return false;" />';

        if ($mType == "Q" || $mType == "Q+" || $mType == "M"){
            $ret .= "<hr />";
            $ret .= "<b>Add UPC</b>: <input type=text size=13 name=new_upc />
            <select name=newtype><option>BOTH</option><option>QUALIFIER</option>
            <option>DISCOUNT</option></select>
            <input type=submit name=submit_add_upc value=Add />";
            $ret .= "<br /><br />";
            $ret .= "<table cellspacing=0 cellpadding=4 border=1>
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
            $ret .= "<br />";
            $ret .= "<input type=submit name=submit_delete_upc value=\"Delete Selected Items\" />";
        } else if ($mType == "D" || $mType == "D+" || $dType == '%D'){
            $ret .= "<hr />";
            $ret .= "<b>Add Dept</b>: <select name=new_dept>";
            foreach($depts as $k=>$v){
                $ret .= "<option value=\"$k\"";
                $ret .= ">$k $v</option>";
            }   
            $ret .= "</select> ";
            $ret .= "<select name=newtype><option>BOTH</option>
            </select>
            <input type=submit name=submit_add_dept value=Add />";
            $ret .= "<br /><br />";
            $ret .= "<table cellspacing=0 cellpadding=4 border=1>
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
            $ret .= "<br />";
            $ret .= "<input type=submit name=submit_delete_dept value=\"Delete Selected Delete\" />";
        }

        $this->add_onload_command("\$('#starts').datepicker();\n");
        $this->add_onload_command("\$('#expires').datepicker();\n");

        return $ret;
    }
}

FannieDispatch::conditionalExec(false);

