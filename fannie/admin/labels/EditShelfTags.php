<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op

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

require('../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class EditShelfTags extends FanniePage {

	protected $title = 'Fannie - Edit Shelf Tags';
	protected $header = 'Edit Shelf Tags';
	protected $must_authenticate = True;
	protected $auth_classes = array('barcodes');

	private $id;

	function preprocess(){
		global $FANNIE_OP_DB;
		$this->id = FormLib::get_form_value('id',0);

		if (FormLib::get_form_value('submit',False) !== False){
			$upcs = FormLib::get_form_value('upc',array());
			$descs = FormLib::get_form_value('desc',array());
			$prices = FormLib::get_form_value('price',array());
			$brands = FormLib::get_form_value('brand',array());
			$skus = FormLib::get_form_value('sku',array());
			$sizes = FormLib::get_form_value('size',array());
			$units = FormLib::get_form_value('units',array());
			$vendors = FormLib::get_form_value('vendors',array());
			$ppos = FormLib::get_form_value('ppo',array());

			$dbc = FannieDB::get($FANNIE_OP_DB);
			$prep = $dbc->prepare_statement("UPDATE shelftags SET 
					description=?, normal_price=?,
					brand=?, sku=?, size=?,
					units=?, vendor=?,
					pricePerUnit=?
					WHERE upc=? and id=?");
			for ($i = 0; $i < count($upcs); $i++){
				$upc = $upcs[$i];
				$desc = isset($descs[$i]) ? $descs[$i] : '';
				$price = isset($prices[$i]) ? $prices[$i] : 0;
				$brand = isset($brands[$i]) ? $brands[$i] : '';
				$size = isset($sizes[$i]) ? $sizes[$i] : '';
				$unit = isset($units[$i]) ? $units[$i] : 1;
				$vendor = isset($vendors[$i]) ? $vendors[$i] : '';
				$ppo = isset($ppos[$i]) ? $ppos[$i] : '';
			
				$dbc->exec_statement($prep, array($desc, $price, $brand,
					$sku, $size, $unit, $vendor, $ppo, $upc, $this->id));
			}
			header("Location: ShelfTagIndex.php");
			return False;
		}

		return True;
	}

	function css_content(){
		return "
		.one {
			background: #ffffff;
		}
		.two {
			background: #ffffcc;
		}";
	}

	function body_content(){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);

		$ret = "<form action=EditShelfTags.php method=post>";
		$ret .= "<table cellspacing=0 cellpadding=4 border=1>";
		$ret .= "<tr><th>UPC</th><th>Desc</th><th>Price</th><th>Brand</th><th>SKU</th>";
		$ret .= "<th>Size</th><th>Units</th><th>Vendor</th><th>PricePer</th></tr>";

		$class = array("one","two");
		$c = 1;
		$query = $dbc->prepare_statement("select upc,description,normal_price,
			brand,sku,size,units,vendor,pricePerUnit from shelftags
			where id=? order by upc");
		$result = $dbc->exec_statement($query,array($this->id));
		while ($row = $dbc->fetch_row($result)){
			$ret .= "<tr class=$class[$c]>";
			$ret .= "<td>$row[0]</td><input type=hidden name=upc[] value=\"$row[0]\" />";
			$ret .= "<td><input type=text name=desc[] value=\"$row[1]\" size=25 /></td>";
			$ret .= "<td><input type=text name=price[] value=\"$row[2]\" size=5 /></td>";
			$ret .= "<td><input type=text name=brand[] value=\"$row[3]\" size=13 /></td>";
			$ret .= "<td><input type=text name=sku[] value=\"$row[4]\" size=6 /></td>";
			$ret .= "<td><input type=text name=size[] value=\"$row[5]\" size=6 /></td>";
			$ret .= "<td><input type=text name=units[] value=\"$row[6]\" size=4 /></td>";
			$ret .= "<td><input type=text name=vendor[] value=\"$row[7]\" size=7 /></td>";
			$ret .= "<td><input type=text name=ppo[] value=\"$row[8]\" size=10 /></td>";
			$ret .= "</tr>";
			$c = ($c+1)%2;
		}
		$ret .= "</table>";
		$ret .= "<input type=hidden name=id value=\"".$this->id."\" />";
		$ret .= "<input type=submit name=submit value=\"Update Shelftags\" />";
		$ret .= "</form>";

		return $ret;
	}
}

FannieDispatch::conditionalExec(false);

