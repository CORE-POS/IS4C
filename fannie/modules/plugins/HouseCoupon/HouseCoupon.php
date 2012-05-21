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

/**
  Stopgap; work even if module system isn't configured
*/
if (basename($_SERVER['PHP_SELF']) == 'HouseCoupon.php'){
	include('../../../config.php');
	include($FANNIE_ROOT.'class-lib/FannieModule.php');
	include($FANNIE_ROOT.'class-lib/FannieFunctions.php');
	include($FANNIE_ROOT.'class-lib/FanniePage.php');
	include($FANNIE_ROOT.'class-lib/db/SQLManager.php');
	include($FANNIE_ROOT.'class-lib/db/DatabaseFunctions.php');
}

/**
  @class HouseCoupon
*/
class HouseCoupon extends FanniePage {

	public $required = False;

	public $description = "
	Module for managing in store coupons
	";

	protected $header = "Fannie :: House Coupons";
	protected $title = "House Coupons";

	private $display_function;
	private $coupon_id;

	function preprocess(){
		global $FANNIE_SERVER_DBMS;
		$this->display_function = 'list_house_coupons';

		if (isset($_REQUEST['edit_id'])){
			$this->coupon_id = (int)$_REQUEST['edit_id'];
			$this->display_function = 'edit_coupon';
		}
		else if (isset($_REQUEST['new_coupon_submit'])){
			$dbc = op_connect();

			$maxQ = "SELECT max(coupID) from houseCoupons";
			$max = array_pop($dbc->fetch_row($dbc->query($maxQ)));
			$this->coupon_id = $max+1;
			
			$insQ = sprintf("INSERT INTO houseCoupons (coupID) values (%d)",
				$this->coupon_id);;
			$dbc->query($insQ);

			$this->display_function='edit_coupon';
			
			$dbc->close();
		}
		else if (isset($_REQUEST['explain_submit'])){
			include(dirname(__FILE__).'/explainify.html');
			return False;
		}
		else if (isset($_REQUEST['submit_save']) || isset($_REQUEST['submit_add_upc'])
		  || isset($_REQUEST['submit_delete_upc']) || isset($_REQUEST['submit_add_dept'])
		  || isset($_REQUEST['submit_delete_dept']) ){

			$dbc = op_connect();

			$this->coupon_id = isset($_REQUEST['cid']) ? (int)$_REQUEST['cid'] : 0;
			$expires = isset($_REQUEST['expires'])?$_REQUEST['expires']:'';
			if ($expires == '') $expires = "NULL";
			else $expires = $dbc->escape($expires);
			$limit = isset($_REQUEST['limit'])?$_REQUEST['limit']:1;
			$mem = isset($_REQUEST['memberonly'])?1:0;
			$dept = isset($_REQUEST['dept'])?$_REQUEST['dept']:800;
			$dtype = isset($_REQUEST['dtype'])?$_REQUEST['dtype']:'Q';
			$dval = isset($_REQUEST['dval'])?$_REQUEST['dval']:0;
			$mtype = isset($_REQUEST['mtype'])?$_REQUEST['mtype']:'Q';
			$mval = isset($_REQUEST['mval'])?$_REQUEST['mval']:0;

			$query =sprintf("UPDATE houseCoupons SET endDate=%s,
				limit=%d,memberOnly=%d,discountType=%s,
				discountValue=%f,minType=%s,minValue=%f,
				department=%d WHERE coupID=%d",
				$expires,$limit,$mem,$dbc->escape($dtype),
				$dval,$dbc->escape($mtype),
				$mval,$dept,$this->coupon_id);
			if ($FANNIE_SERVER_DBMS == 'MYSQL')
				$query = str_replace("limit","`limit`",$query);
			$dbc->query($query);
			
			$this->display_function = 'edit_coupon';

			if (isset($_REQUEST['submit_add_upc']) && !empty($_REQUEST['new_upc'])){
				/**
				  Add (or update) a UPC
				*/
				$upc = str_pad($_REQUEST['new_upc'],13,'0',STR_PAD_LEFT);
				$type = isset($_REQUEST['newtype']) ? $_REQUEST['newtype'] : 'BOTH';
				$check = sprintf("SELECT upc FROM houseCouponItems WHERE
					upc=%s and coupID=%d",$dbc->escape($upc),$this->coupon_id);
				$check = $dbc->query($check);
				if ($dbc->num_rows($check) == 0){
					$query = sprintf("INSERT INTO houseCouponItems VALUES (
						%d,%s,%s)",$this->coupon_id,$dbc->escape($upc),$dbc->escape($type));
					$dbc->query($query);
				}
				else {
					$query = sprintf("UPDATE houseCouponItems SET type=%s
						WHERE upc=%s AND coupID=%d",$dbc->escape($type),
						$dbc->escape($upc),$this->coupon_id);
					$dbc->query($query);
				}
			}
			if (isset($_REQUEST['submit_add_dept']) && !empty($_REQUEST['new_dept'])){
				/**
				  Add (or update) a department
				*/
				$dept = (int)$_REQUEST['new_dept'];
				$type = isset($_REQUEST['newtype']) ? $_REQUEST['newtype'] : 'BOTH';
				$check = sprintf("SELECT upc FROM houseCouponItems WHERE
					upc=%s and coupID=%d",$dbc->escape($dept),$this->coupon_id);
				$check = $dbc->query($check);
				if ($dbc->num_rows($check) == 0){
					$query = sprintf("INSERT INTO houseCouponItems VALUES (
						%d,%s,%s)",$this->coupon_id,$dbc->escape($dept),$dbc->escape($type));
					$dbc->query($query);
				}
				else {
					$query = sprintf("UPDATE houseCouponItems SET type=%s
						WHERE upc=%s AND coupID=%d",$dbc->escape($type),
						$dbc->escape($dept),$this->coupon_id);
					$dbc->query($query);
				}
			}
			elseif (isset($_REQUEST['submit_delete_upc']) || isset($_REQUEST['submit_delete_dept'])){
				/**
				  Delete UPCs and departments
				*/
				foreach($_REQUEST['del'] as $upc){
					$query = sprintf("DELETE FROM houseCouponItems
						WHERE upc=%s AND coupID=%d",
						$dbc->escape($upc),$this->coupon_id);
					$dbc->query($query);
				}
			}

			$dbc->close();
		}

		return True;
	}

	function body_content(){
		$func = $this->display_function;
		return $this->$func();
	}

	function list_house_coupons(){
		$dbc = op_connect();
		
		$ret = $this->form_tag('get');
		$ret .= '<input type="submit" name="new_coupon_submit" value="New Coupon" />';
		$ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		$ret .= '<input type="submit" name="explain_submit" value="Explanation of Settings" />';
		$ret .= '</form>';
		$ret .= '<table cellpadding="4" cellspacing="0" border="1" />';
		$ret .= '<tr><th>ID</th><th>Value</th><th>Expires</th></tr>';
		$q = "SELECT coupID, discountValue, discountType, endDate FROM houseCoupons ORDER BY coupID";
		$r = $dbc->query($q);
		while($w = $dbc->fetch_row($r)){
			$ret .= sprintf('<tr><td>#%d <a href="%s&edit_id=%d">Edit</a></td>
					<td>%.2f%s</td><td>%s</td></tr>',
					$w['coupID'],$this->module_url(),$w['coupID'],
					$w['discountValue'],$w['discountType'],$w['endDate']);
		}
		$ret .= '</table>';
		
		$dbc->close();
		return $ret;
	}

	function edit_coupon(){
		global $FANNIE_URL;
		$dbc = op_connect();
		
		$depts = array();
		$query = "SELECT dept_no,dept_name FROM departments ORDER BY dept_no";
		$result = $dbc->query($query);
		while($row = $dbc->fetch_row($result)){
			$depts[$row[0]] = $row[1];
		}

		$cid = $this->coupon_id;

		$q1 = "SELECT * FROM houseCoupons WHERE coupID=$cid";
		$r1 = $dbc->query($q1);
		$row = $dbc->fetch_row($r1);

		$expires = $row['endDate'];
		if (strstr($expires,' '))
			$expires = array_shift(explode(' ',$expires));
		$limit = $row['limit'];
		$mem = $row['memberOnly'];
		$dType = $row['discountType'];
		$dVal = $row['discountValue'];
		$mType = $row['minType'];
		$mVal = $row['minValue'];
		$dept = $row['department'];

		$ret = $this->form_tag('post');
		$ret .= '<input type="hidden" name="cid" value="'.$cid.'" />';

		$ret .= sprintf('<table cellspacing=0 cellpadding=4><tr>
			<th>Coupon ID#</th><td>%s</td><th>UPC</th>
			<td>%s</td></tr><tr><th>Expires</th>
			<td><input type=text name=expires value="%s" size=12 
			onclick="showCalendarControl(this);" />
			</td><th>Limit</th><td><input type=text name=limit size=3
			value="%s" /></td></tr><tr><th>Member-only</th><td>
			<input type=checkbox name=memberonly %s /></td><th>
			Department</th><td><select name=dept>',
			$cid,"00499999".str_pad($cid,5,'0',STR_PAD_LEFT),
			$expires,$limit,($mem==1?'checked':'') );
		foreach($depts as $k=>$v){
			$ret .= "<option value=\"$k\"";
			if ($k == $dept) $ret .= " selected";
			$ret .= ">$k $v</option>";
		}
		$ret .= "</select></td></tr>";

		$dts = array('Q'=>'Quantity Discount',
			'P'=>'Set Price Discount',
			'FI'=>'Scaling Discount (Item)',
			'FD'=>'Scaling Discount (Department)',
			'F'=>'Flat Discount',
			'%'=>'Percent Discount (Transaction)',
			'AD'=>'All Discount (Department)'
		);
		$ret .= "<tr><th>Discount Type</th><td>
			<select name=dtype>";
		foreach($dts as $k=>$v){
			$ret .= "<option value=\"$k\"";
			if ($k == $dType) $ret .= " selected";
			$ret .= ">$v</option>";
		}
		$ret .= "</select></td><th>Discount value</th>
			<td><input type=text name=dval value=\"$dVal\"
			size=5 /></td></tr>";

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
		$ret .= "<tr><th>Minimum Type</th><td>
			<select name=mtype>";
		foreach($mts as $k=>$v){
			$ret .= "<option value=\"$k\"";
			if ($k == $mType) $ret .= " selected";
			$ret .= ">$v</option>";
		}
		$ret .= "</select></td><th>Minimum value</th>
			<td><input type=text name=mval value=\"$mVal\"
			size=5 /></td></tr>";

		$ret .= "</table>";
		$ret .= "<br /><input type=submit name=submit_save value=Save />";

		if ($mType == "Q" || $mType == "Q+" || $mType == "M"){
			$ret .= "<hr />";
			$ret .= "<b>Add UPC</b>: <input type=text size=13 name=new_upc />
			<select name=newtype><option>BOTH</option><option>QUALIFIER</option>
			<option>DISCOUNT</option></select>
			<input type=submit name=submit_add_upc value=Add />";
			$ret .= "<br /><br />";
			$ret .= "<table cellspacing=0 cellpadding=4 border=1>
			<tr><th colspan=4>Items</th></tr>";
			$query = "SELECT h.upc,p.description,h.type FROM
				houseCouponItems as h LEFT JOIN products AS
				p ON h.upc = p.upc WHERE coupID=$cid";
			$result = $dbc->query($query);
			while($row = $dbc->fetch_row($result)){
				$ret .= sprintf("<tr><td>%s</td><td>%s</td><td>%s</td>
					<td><input type=checkbox name=del[] 
					value=\"%s\" /></tr>",
					$row[0],$row[1],$row[2],$row[0]);
			}
			$ret .= "</table>";
			$ret .= "<br />";
			$ret .= "<input type=submit name=submit_delete_upc value=\"Delete Selected Items\" />";
		}
		else if ($mType == "D" || $mType == "D+"){
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
			$query = "SELECT h.upc,d.dept_name,h.type FROM
				houseCouponItems as h LEFT JOIN departments as d
				ON h.upc = d.dept_no WHERE coupID=$cid";
			$result = $dbc->query($query);
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

		$dbc->close();
		$this->add_script($FANNIE_URL.'src/CalendarControl.js');
		return $ret;
	}
}

/**
  More stopgap; load self if needed
*/
if (basename($_SERVER['PHP_SELF']) == 'HouseCoupon.php'){
	$obj = new HouseCoupon();
	$obj->run_module();
}

