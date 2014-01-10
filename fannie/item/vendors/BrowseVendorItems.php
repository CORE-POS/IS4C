<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

include('../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class BrowseVendorItems extends FanniePage {
	protected $title = "Fannie : Browse Vendor Catalog";
	protected $header = "Browse Vendor Catalog";
	protected $window_dressing = False;

	function preprocess(){

		$ajax = FormLib::get_form_value('action');
		if ($ajax !== ''){
			$this->ajax_callbacks($ajax);
			return False;
		}		

		return True;
	}

	function ajax_callbacks($action){
		global $FANNIE_OP_DB;
		switch($action){
		case 'getCategoryBrands':
			$this->getCategoryBrands(FormLib::get_form_Value('vid'),FormLib::get_form_value('deptID'));
			break;
		case 'showCategoryItems':
			$this->showCategoryItems(
				FormLib::get_form_value('vid'),
				FormLib::get_form_value('deptID'),
				FormLib::get_form_value('brand')
			);
			break;
		case 'addPosItem':
			$this->add_to_pos(
				FormLib::get_form_value('upc'),
				FormLib::get_form_value('vid'),
				FormLib::get_form_value('price'),
				FormLib::get_form_value('dept')
			);
			break;
		default:
			echo 'bad request';
			break;
		}
	}

	private function getCategoryBrands($vid,$did){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);

		$query = "SELECT brand FROM vendorItems AS v
			LEFT JOIN vendorDepartments AS d ON
			v.vendorDept=d.deptID WHERE v.vendorID=?";
		$args = array($vid);
		if($did != 'All'){
			$query .= ' AND vendorDept=? ';
			$args[] = $did;
		}
		$query .= "GROUP BY brand ORDER BY brand";
		$ret = "<option value=\"\">Select a brand...</option>";
		$p = $dbc->prepare_statement($query);
		$result = $dbc->exec_statement($p,$args);
		while($row=$dbc->fetch_row($result))
			$ret .= "<option>$row[0]</option>";

		echo $ret;
	}

	private function showCategoryItems($vid,$did,$brand){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);

		$depts = "";
		$p = $dbc->prepare_statement("SELECT dept_no,dept_name FROM departments ORDER BY dept_no");
		$rp = $dbc->exec_statement($p);
		while($rw = $dbc->fetch_row($rp))
			$depts .= "<option value=$rw[0]>$rw[0] $rw[1]</option>";

		$query = "SELECT v.upc,v.brand,v.description,v.size,
			v.cost/v.units as cost,
			CASE WHEN d.margin IS NULL THEN 0 ELSE d.margin END as margin,
			CASE WHEN p.upc IS NULL THEN 0 ELSE 1 END as inPOS,
			s.srp
			FROM vendorItems AS v LEFT JOIN products AS p
			ON v.upc=p.upc LEFT JOIN vendorDepartments AS d
			ON d.deptID=v.vendorDept
			LEFT JOIN vendorSRPs AS s 
			ON v.upc=s.upc AND v.vendorID=s.vendorID
			WHERE v.vendorID=? AND brand=?";
		$args = array($vid,$brand);
		if ($did != 'All'){
			$query .= ' AND vendorDept=? ';
			$args[] = $dept;
		}
		$query .= "ORDER BY v.upc";
		
		$ret = "<table cellspacing=0 cellpadding=4 border=1>";
		$ret .= "<tr><th>UPC</th><th>Brand</th><th>Description</th>";
		$ret .= "<th>Size</th><th>Cost</th><th colspan=3>&nbsp;</th></tr>";
		$p = $dbc->prepare_statement($query);
		$result = $dbc->exec_statement($p,$args);
		while($row = $dbc->fetch_row($result)){
			if ($row['inPOS'] == 1){
				$ret .= sprintf("<tr style=\"background:#ffffcc;\">
					<td>%s</td><td>%s</td><td>%s</td>
					<td>%s</td><td>\$%.2f</td><td colspan=3>&nbsp;
					</td></tr>",$row['upc'],$row['brand'],
					$row['description'],$row['size'],$row['cost']);
			}
			else {
				$srp = !empty($row['srp']) ? $row['srp'] : $this->getSRP($row['cost'],$row['margin']);
				$ret .= sprintf("<tr id=row%s><td>%s</td><td>%s</td><td>%s</td>
					<td>%s</td><td>\$%.2f</td><td>
					<input type=text size=5 value=%.2f id=price%s />
					</td><td><select id=\"dept%s\">%s</select></td>
					<td id=button%s>
					<input type=submit value=\"Add to POS\"
					onclick=\"addToPos('%s');\" /></td></tr>",$row['upc'],
					$row['upc'],$row['brand'],$row['description'],
					$row['size'],$row['cost'],$srp,$row['upc'],
					$row['upc'],$depts,$row['upc'],$row['upc']);
			}
		}
		$ret .= "</table>";

		echo $ret;
	}

	private function add_to_pos($upc,$vid,$price,$dept){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);

		$p = $dbc->prepare_statement("SELECT i.*,v.vendorName FROM vendorItems AS i
			LEFT JOIN vendors AS v ON v.vendorID=i.vendorID
			WHERE i.vendorID=? AND upc=?");
		$vinfo = $dbc->exec_statement($p, array($vid,$upc));
		$vinfo = $dbc->fetch_row($vinfo);
		$p = $dbc->prepare_statement("SELECT * FROM departments WHERE dept_no=?");
		$dinfo = $dbc->exec_statement($p,array($dept));
		$dinfo = $dbc->fetch_row($dinfo);
		
		ProductsModel::update($upc,array(
			'description' => $vinfo['description'],
			'normal_price' => $price,
			'department' => $dept,
			'tax' => $dinfo['dept_tax'],
			'foodstamp' => $dinfo['dept_fs'],
			'cost' => ($vinfo['cost']/$vinfo['units'])
		));

		$xInsQ = $dbc->prepare_statement("INSERT INTO prodExtra (upc,distributor,manufacturer,cost,margin,variable_pricing,location,
				case_quantity,case_cost,case_info) VALUES
				(?,?,?,?,0.00,0,'','',0.00,'')");
		$args = array($upc,$vinfo['brand'],
				$vinfo['vendorName'],($vinfo['cost']/$vinfo['units']));
		$dbc->exec_statement($xInsQ,$args);

		echo "Item added";
	}

	private function getSRP($cost,$margin){
		$srp = sprintf("%.2f",$cost/(1-$margin));
		while (substr($srp,strlen($srp)-1,strlen($srp)) != "5" &&
		       substr($srp,strlen($srp)-1,strlen($srp)) != "9")
			$srp += 0.01;
		return $srp;
	}

	function body_content(){
		global $FANNIE_OP_DB, $FANNIE_URL;
		$vid = FormLib::get_form_value('vid');
		if ($vid === ''){
			return "<i>Error: no vendor selected</i>";
		}

		$dbc = FannieDB::get($FANNIE_OP_DB);
		$cats = "";
		$p = $dbc->prepare_statement("SELECT deptID,name FROM vendorDepartments
				WHERE vendorID=?");
		$rp = $dbc->exec_statement($p,array($vid));
		while($rw = $dbc->fetch_row($rp))
			$cats .= "<option value=$rw[0]>$rw[0] $rw[1]</option>";

		if ($cats =="") $cats = "<option value=\"\">Select a department...</option><option>All</option>";
		else $cats = "<option value=\"\">Select a department...</option>".$cats;

		ob_start();
		?>
		<div id="categorydiv">
		<select id=categoryselect onchange="catchange();">
		<?php echo $cats ?>
		</select>
		&nbsp;&nbsp;&nbsp;
		<select id=brandselect onchange="brandchange();">
		<option>Select a department first...</option>
		</select>
		</div>
		<hr />
		<div id="contentarea">
		<?php if (isset($_REQUEST['did'])){
			showCategoryItems($vid,$_REQUEST['did']);
		}
		?>
		</div>
		<input type="hidden" id="vendorID" value="<?php echo $vid; ?>" />
		<input type="hidden" id="urlpath" value="<?php echo $FANNIE_URL; ?>" />
		<?php
		
		$this->add_script($FANNIE_URL.'src/jquery/jquery.js');
		$this->add_script('browse.js');

		return ob_get_clean();
	}
}

FannieDispatch::conditionalExec(false);

?>
