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
include($FANNIE_ROOT.'classlib2.0/FanniePage.php');
include($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');
include($FANNIE_ROOT.'classlib2.0/lib/FormLib.php');
include($FANNIE_ROOT.'classlib2.0/lib/PriceLib.php');

class CreateTagsByDept extends FanniePage {

	protected $title = "Fannie : Department Shelf Tags";
	protected $header = "Department Shelf Tags";

	private $msgs = '';

	function preprocess(){
		global $FANNIE_OP_DB;
		if (FormLib::get_form_value('deptStart',False) !== False){
			$start = FormLib::get_form_value('deptStart');
			$end = FormLib::get_form_value('deptEnd');
			$pageID = FormLib::get_form_value('sID',0);
			$dbc = FannieDB::get($FANNIE_OP_DB);
			$q = $dbc->prepare_statement("select p.upc,p.description,p.normal_price,
				x.manufacturer,x.distributor,v.sku,v.size,
				CASE WHEN v.units IS NULL THEN 1 ELSE v.units END as units
				FROM products as p
				left join prodExtra as x on p.upc=x.upc
				left join vendorItems as v ON p.upc=v.upc
				left join vendors as n on v.vendorID=n.vendorID
				where p.department BETWEEN ? AND ? AND (
					x.distributor=n.vendorName
					or (x.distributor='' and n.vendorName='UNFI')
					or (x.distributor is null and n.vendorName='UNFI')
					or (n.vendorName is NULL)
				)");
			$r = $dbc->exec_statement($q,array($start,$end));
			$ins = $dbc->prepare_statement("INSERT INTO shelftags (id,upc,description,normal_price,
				brand,sku,size,units,vendor,pricePerUnit) VALUES (?,?,?,?,
				?,?,?,?,?,?)");
			while($w = $dbc->fetch_row($r)){
				$args = array($pageID,$w['upc'],
					$w['description'],$w['normal_price'],
					$w['manufacturer'],$w['distributor'],
					$w['sku'],$w['units'],$w['size'],
					PriceLib::pricePerUnit($w['normal_price'],$w['size'])
				);
				$dbc->exec_statement($ins,$args);
			}
			$this->msgs = sprintf('<em>Created tags for departments #%d through #%d</em>
					<br /><a href="ShelfTagIndex.php">Home</a>',
				$start, $end);
		}
		return True;
	}

	function body_content(){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);
		$deptsQ = $dbc->prepare_statement("select dept_no,dept_name from departments order by dept_no");
		$deptsR = $dbc->exec_statement($deptsQ);
		$deptsList = "";

		$deptSubQ = $dbc->prepare_statement("SELECT superID,super_name FROM MasterSuperDepts
				GROUP BY superID,super_name
				ORDER BY superID");
		$deptSubR = $dbc->exec_statement($deptSubQ);

		$deptSubList = "";
		while($deptSubW = $dbc->fetch_array($deptSubR)){
		  $deptSubList .=" <option value=$deptSubW[0]>$deptSubW[1]</option>";
		}
		while ($deptsW = $dbc->fetch_array($deptsR))
		  $deptsList .= "<option value=$deptsW[0]>$deptsW[0] $deptsW[1]</option>";

		$ret = '';
		if (!empty($this->msgs)){
			$ret .= '<blockquote style="border:solid 1px black; padding:5px;
					margin:5px;">';
			$ret .= $this->msgs;
			$ret .= '</blockquote>';
		}

		ob_start();
		?>
		<form action="CreateTagsByDept.php" method="get">
		<table>
		<tr> 
			<td align="right"> <p><b>Department Start</b></p>
			<p><b>End</b></p></td>
			<td> <p>
			<select id=deptStartSel onchange="$('#deptStart').val($(this).val());">
			<?php echo $deptsList ?>
			</select>
			<input type=text name=deptStart id=deptStart size=5 value=1 />
			</p>
			<p>
			<select id=deptEndSel onchange="$('#deptEnd').val($(this).val());">
			<?php echo $deptsList ?>
			</select>
			<input type=text name=deptEnd id=deptEnd size=5 value=1 />
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

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)){
	$obj = new CreateTagsByDept();
	$obj->draw_page();
}
?>
