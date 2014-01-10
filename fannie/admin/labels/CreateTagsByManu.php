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

include('../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class CreateTagsByManu extends FanniePage {
	protected $title = "Fannie : Manufacturer Shelf Tags";
	protected $header = "Manufacturer Shelf Tags";

	private $msgs = '';

	function preprocess(){
		global $FANNIE_OP_DB;
		if (FormLib::get_form_value('manufacturer',False) !== False){
			$manu = FormLib::get_form_value('manufacturer');
			$pageID = FormLib::get_form_value('sID',0);
			$cond = "";
			if (is_numeric($_REQUEST['manufacturer']))
				$cond = " p.upc LIKE ? ";
			else
				$cond = " x.manufacturer LIKE ? ";
			$dbc = FannieDB::get($FANNIE_OP_DB);
			$q = $dbc->prepare_statement("select p.upc,p.description,p.normal_price,
				x.manufacturer,x.distributor,v.sku,v.size,
				CASE WHEN v.units IS NULL THEN 1 ELSE v.units END as units
				FROM products as p
				left join prodExtra as x on p.upc=x.upc
				left join vendorItems as v ON p.upc=v.upc
				left join vendors as n on v.vendorID=n.vendorID
				where $cond AND (
					x.distributor=n.vendorName
					or (x.distributor='' and n.vendorName='UNFI')
					or (x.distributor is null and n.vendorName='UNFI')
					or (n.vendorName is NULL)
				)");
			$r = $dbc->exec_statement($q,array('%'.$manu.'%'));
			$ins = $dbc->prepare_statement("INSERT INTO shelftags (id,upc,description,normal_price,
				brand,sku,size,units,vendor,pricePerUnit) VALUES (?,?,?,?,
				?,?,?,?,?,?)");
			while($w = $dbc->fetch_row($r)){
				$args = array($pageID,$w['upc'],
					$w['description'],$w['normal_price'],
					$w['manufacturer'],
					$w['sku'],$w['units'],$w['size'],
                    $w['distributor'],
					PriceLib::pricePerUnit($w['normal_price'],$w['size'])
				);
				$dbc->exec_statement($ins,$args);
			}
			$this->msgs = '<em>Created tags for manufacturer</em>
					<br /><a href="ShelfTagIndex.php">Home</a>';
		}
		return True;
	}

	function body_content(){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);
		$deptSubQ = $dbc->prepare_statement("SELECT superID,super_name FROM MasterSuperDepts
				GROUP BY superID,super_name
				ORDER BY superID");
		$deptSubR = $dbc->exec_statement($deptSubQ);

		$deptSubList = "";
		while($deptSubW = $dbc->fetch_array($deptSubR)){
			$deptSubList .=" <option value=$deptSubW[0]>$deptSubW[1]</option>";
		}

		$ret = '';
		if (!empty($this->msgs)){
			$ret .= '<blockquote style="border:solid 1px black; padding:5px;
					margin:5px;">';
			$ret .= $this->msgs;
			$ret .= '</blockquote>';
		}

		ob_start();
		?>
		<form action="CreateTagsByManu.php" method="get">
		<table>
		<tr> 
			<td align="right"> <p><b>Name or UPC prefix</b></p></td>
			<td> 
			</p>
			<input type=text name=manufacturer />
			</p></td>
		</tr>
		<tr>
			<td><p><b>Page:</b> <select name="sID"><?php echo $deptSubList; ?></select></p></td>
			<td align="right"><input type="submit" value="Create Shelftags" />
		</tr>
		</table>
		</form>
		<?php
		return $ret.ob_get_clean();
	}
}

FannieDispatch::conditionalExec(false);

?>
