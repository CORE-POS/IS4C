<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include('../config.php');
include($FANNIE_ROOT.'classlib2.0/FanniePage.php');
include($FANNIE_ROOT.'classlib2.0/lib/FormLib.php');
include($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');
include($FANNIE_ROOT.'classlib2.0/data/controllers/ProductsController.php');
include($FANNIE_ROOT.'src/JsonLib.php');
require('laneUpdates.php');
if (!function_exists('login'))
	include($FANNIE_ROOT.'auth/login.php');
include($FANNIE_ROOT.'src/ReportConvert/HtmlToArray.php');
include($FANNIE_ROOT.'src/ReportConvert/ArrayToCsv.php');

class ProductListPage extends FanniePage {

	protected $title = 'Fannie - Product List';
	protected $header = 'Product List';

	private $mode = 'form';

	private $canDeleteItems = False;
	private $canEditItems = False;

	private $excel = False;

	function preprocess(){
		global $FANNIE_URL;

		$this->canDeleteItems = validateUserQuiet('delete_items');
		$this->canEditItems = validateUserQuiet('pricechange');

		$this->excel = FormLib::get_form_value('excel',False);

		if (FormLib::get_form_value('ajax') !== ''){
			$this->ajax_response();
			return False;
		}

		if (FormLib::get_form_value('supertype') !== ''){
			$this->mode = 'list';
			$this->window_dressing = False;
			if (!$this->excel)
				$this->add_script($FANNIE_URL.'src/jquery/jquery.js');	
		}

		return True;
	}

	function javascript_content(){
		global $FANNIE_URL, $FANNIE_OP_DB;

		if ($this->excel) return '';

		$dbc = FannieDB::get($FANNIE_OP_DB);
		$depts = array();
		$p = $dbc->prepare_statement('SELECT dept_no,dept_name FROM departments ORDER BY dept_no');
		$result = $dbc->exec_statement($p);
		while($w = $dbc->fetch_row($result))
			$depts[$w[0]] = $w[1];
		$taxes = array('-'=>array(0,'NoTax'));
		$p = $dbc->prepare_statement('SELECT id, description FROM taxrates ORDER BY id');
		$result = $dbc->exec_statement($p);
		while($w = $dbc->fetch_row($result)){
			if ($w['id'] == 1)
				$taxes['X'] = array(1,'Regular');
			else
				$taxes[strtoupper(substr($w[1],0,1))] = array($w[0], $w[1]);
		}
		ob_start();
		?>
		var deptObj = <?php echo JsonLib::array_to_json($depts); ?>;
		var taxObj = <?php echo JsonLib::array_to_json($taxes); ?>;
		function edit(upc){
			var desc = $('tr#'+upc+' .td_desc').html();
			var content = "<input type=text class=in_desc value=\""+desc+"\" />";	
			$('tr#'+upc+' .td_desc').html(content);

			var dept = $('tr#'+upc+' .td_dept').html();
			var content = '<select class=in_dept>';
			for(dept_no in deptObj){
				content += "<option value=\""+dept_no+"\" "+((dept==deptObj[dept_no])?'selected':'')+">";
				content += deptObj[dept_no]+"</option>";
			}
			content += '</select>';
			$('tr#'+upc+' .td_dept').html(content);

			var supplier = $('tr#'+upc+' .td_supplier').html();
			var content = "<input type=text class=in_supplier value=\""+supplier+"\" />";	
			$('tr#'+upc+' .td_supplier').html(content);

			var price = $('tr#'+upc+' .td_price').html();
			var content = "<input type=text class=in_price size=4 value=\""+price+"\" />";	
			$('tr#'+upc+' .td_price').html(content);

			var tax = $('tr#'+upc+' .td_tax').html();
			var content = '<select class=in_tax>';
			for (ch in taxObj){
				var sel = (tax == ch) ? 'selected' : '';
				content += "<option value=\""+ch+":"+taxObj[ch][0]+"\" "+sel+">";
				content += taxObj[ch][1]+"</option>";
			}
			$('tr#'+upc+' .td_tax').html(content);

			var fs = $('tr#'+upc+' .td_fs').html();
			var content = "<input type=checkbox class=in_fs "+((fs=='X')?'checked':'')+" />";
			$('tr#'+upc+' .td_fs').html(content);

			var disc = $('tr#'+upc+' .td_disc').html();
			var content = "<input type=checkbox class=in_disc "+((disc=='X')?'checked':'')+" />";
			$('tr#'+upc+' .td_disc').html(content);

			var wgt = $('tr#'+upc+' .td_wgt').html();
			var content = "<input type=checkbox class=in_wgt "+((wgt=='X')?'checked':'')+" />";
			$('tr#'+upc+' .td_wgt').html(content);

			var local = $('tr#'+upc+' .td_local').html();
			var content = "<input type=checkbox class=in_local "+((local=='X')?'checked':'')+" />";
			$('tr#'+upc+' .td_local').html(content);

			var lnk = "<img src=\"<?php echo $FANNIE_URL;?>src/img/buttons/b_save.png\" alt=\"Save\" border=0 />";
			$('tr#'+upc+' .td_cmd').html("<a href=\"\" onclick=\"save('"+upc+"');return false;\">"+lnk+"</a>");
		}
		function save(upc){
			var desc = $('tr#'+upc+' .in_desc').val();
			$('tr#'+upc+' .td_desc').html(desc);
		
			var dept = $('tr#'+upc+' .in_dept').val();
			$('tr#'+upc+' .td_dept').html(deptObj[dept]);

			var supplier = $('tr#'+upc+' .in_supplier').val();
			$('tr#'+upc+' .td_supplier').html(supplier);

			var price = $('tr#'+upc+' .in_price').val();
			$('tr#'+upc+' .td_price').html(price);

			var tax = $('tr#'+upc+' .in_tax').val().split(':');
			$('tr#'+upc+' .td_tax').html(tax[0]);
			
			var fs = $('tr#'+upc+' .in_fs').is(':checked') ? 1 : 0;
			$('tr#'+upc+' .td_fs').html((fs==1)?'X':'-');

			var disc = $('tr#'+upc+' .in_disc').is(':checked') ? 1 : 0;
			$('tr#'+upc+' .td_disc').html((disc==1)?'X':'-');

			var wgt = $('tr#'+upc+' .in_wgt').is(':checked') ? 1 : 0;
			$('tr#'+upc+' .td_wgt').html((wgt==1)?'X':'-');

			var local = $('tr#'+upc+' .in_local').is(':checked') ? 1 : 0;
			$('tr#'+upc+' .td_local').html((local==1)?'X':'-');

			var lnk = "<img src=\"<?php echo $FANNIE_URL;?>src/img/buttons/b_edit.png\" alt=\"Edit\" border=0 />";
			var cmd = "<a href=\"\" onclick=\"edit('"+upc+"'); return false;\">"+lnk+"</a>";
			$('tr#'+upc+' .td_cmd').html(cmd);

			var dstr = 'ajax=save&upc='+upc+'&desc='+desc+'&dept='+dept+'&price='+price;
			dstr += '&tax='+tax[1]+'&fs='+fs+'&disc='+disc+'&wgt='+wgt+'&supplier='+supplier+'&local='+local;
			$.ajax({
			url: 'ProductListPage.php',
			data: dstr,
			cache: false,
			type: 'post',
			success: function(data){}
			});
		}
		function deleteCheck(upc,desc){
			$.ajax({
			url: 'ProductListPage.php',
			data: 'ajax=deleteCheck&upc='+upc+'&desc='+desc,
			dataType: 'json',
			cache: false,
			type: 'post',
			success: function(data){
				if (data.alertBox && data.upc && data.enc_desc){
					if (confirm(data.alertBox)){
						$.ajax({
						url: 'ProductListPage.php',
						data: 'ajax=doDelete&upc='+upc+'&desc='+enc_desc,
						cache: false,
						type: 'post',
						success: function(data){}
						});
					}
				}
				else
					alert('Data error: cannot delete');
			}
			});
		}
		<?php
		return ob_get_clean();
	}

	function ajax_response(){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);
		switch(FormLib::get_form_value('ajax')){
		case 'save':
			$upc = FormLib::get_form_value('upc');
			$upc = str_pad($upc,13,'0',STR_PAD_LEFT);
			$values = array();
			$desc = FormLib::get_form_value('desc');
			if ($desc !== '') $values['description'] = $desc;
			$dept = FormLib::get_form_value('dept');
			if ($dept !== '') $values['department'] = $dept;
			$price = rtrim(FormLib::get_form_value('price'),' ');
			if ($price !== '') $values['normal_price'] = $price;
			$tax = FormLib::get_form_value('tax');
			if ($tax !== '') $values['tax'] = $tax;
			$fs = FormLib::get_form_value('fs');
			if ($fs !== '') $values['foodstamp'] = ($fs==1) ? 1 : 0;
			$disc = FormLib::get_form_value('disc');
			if ($disc !== '') $values['discount'] = ($disc==1) ? 1 : 0;
			$wgt = FormLib::get_form_value('wgt');
			if ($wgt !== '') $values['scale'] = ($wgt==1) ? 1 : 0;
			$loc = FormLib::get_form_value('loc');
			if ($loc !== '') $values['local'] = ($loc==1) ? 1 : 0;

			ProductsController::update($upc, $values);

			$supplier = FormLib::get_form_value('supplier');
			$extraP = $dbc->prepare_statement('UPDATE prodExtra SET distributor=? WHERE upc=?');
			$dbc->exec_statement($extraP, array($supplier,$upc));
			
			updateProductAllLanes($upc);
			break;	
		case 'deleteCheck':
			$upc = FormLib::get_form_value('upc');
			$upc = str_pad($upc,13,'0',STR_PAD_LEFT);
			$encoded_desc = FormLib::get_form_value('desc');
			$desc = base64_decode($encoded_desc);
			$fetchP = $dbc->prepare_statement("select normal_price,
				special_price,t.description,
				case when foodstamp = 1 then 'Yes' else 'No' end as fs,
				case when scale = 1 then 'Yes' else 'No' end as s
				from products as p left join taxrates as t
				on p.tax = t.id
				where upc=? and p.description=?");
			$fetchR = $dbc->exec_statement($fetchP,array($upc, $desc));
			$fetchW = $dbc->fetch_array($fetchR);

			$ret = "Delete item $upc - $desc?\n";
			$ret .= "Normal price: ".rtrim($fetchW[0])."\n";
			$ret .= "Sale price: ".rtrim($fetchW[1])."\n";
			$ret .= "Tax: ".rtrim($fetchW[2])."\n";
			$ret .= "Foodstamp: ".rtrim($fetchW[3])."\n";
			$ret .= "Scale: ".rtrim($fetchW[4])."\n";

			$json = array(
				'alertBox'=>$ret,
				'upc'=>ltrim('0',$upc),
				'enc_desc'=>$encoded_desc
			);
			echo JsonLib::array_to_json($json);
			break;
		case 'doDelete':
			$upc = FormLib::get_form_value('upc');
			$upc = str_pad($upc,13,'0',STR_PAD_LEFT);
			$desc = base64_decode(FormLib::get_form_value('desc'));

			ProductsController::delete($upc);

			$delP = $dbc->prepare_statement("delete from prodExtra where upc=?");
			$delXR = $dbc->exec_statement($delP,array($upc));

			deleteProductAllLanes($upc);
			break;
		default:
			echo 'Unknown Action';
			break;
		}
	}

	function list_content(){
		global $FANNIE_OP_DB, $FANNIE_URL;
		$dbc = FannieDB::get($FANNIE_OP_DB);

		$supertype = FormLib::get_form_value('supertype','dept');
		$manufacturer = FormLib::get_form_value('manufacturer','');
		$mtype = FormLib::get_form_value('mtype','prefix');
		$deptStart = FormLib::get_form_value('deptStart',0);
		$deptEnd = FormLib::get_form_value('deptEnd',0);
		$super = FormLib::get_form_value('deptSub',0);

		$sort = FormLib::get_form_value('sort','Department');	
		$order = 'dept_name';
		if ($sort === 'UPC') $order = 'i.upc';	
		elseif ($sort === 'Description') $order = 'i.description';
		elseif ($sort === 'Supplier') $order = 'x.distributor';
		elseif ($sort === 'Price') $order = 'i.normal_price';

		$ret = 'Report sorted by '.$sort.'<br />';
		if ($supertype == 'dept' && $super == 0){
			$ret .= 'Department '.$deptStart.' to '.$deptEnd.'<br />';
		}
		else if ($supertype == 'dept'){
			$ret .= 'Sub department '.$super.'<br />';
		}
		else {
			$ret .= 'Manufacturer '.$manufacturer.'<br />';
		}
		$ret .= date("F j, Y, g:i a").'<br />'; 
		
		$page_url = sprintf('ProductListPage.php?supertype=%s&deptStart=%s&deptEnd=%s&deptSub=%s&manufacturer=%s&mtype=%s',
				$supertype, $deptStart, $deptEnd, $super, $manufacturer, $mtype);
		if (!$this->excel){
			$ret .= sprintf('<a href="%s&sort=%s&excel=yes">Save to Excel</a><br />',
				$page_url, $sort);
		}

		$query = "SELECT i.upc,i.description,d.dept_name as department,
			i.normal_price,                      
			(CASE WHEN i.tax = 1 THEN 'X' WHEN i.tax=0 THEN '-' ELSE LEFT(t.description,1) END) as Tax,              
	 	        (CASE WHEN i.foodstamp = 1 THEN 'X' ELSE '-' END) as FS,
                        (CASE WHEN i.discount = 0 THEN '-' ELSE 'X'END) as DISC,
                        (CASE WHEN i.scale = 1 THEN 'X' ELSE '-' END) as WGHd,
                        (CASE WHEN i.local = 1 THEN 'X' ELSE '-' END) as local,
			x.distributor
                        FROM products as i LEFT JOIN departments as d ON i.department = d.dept_no
			LEFT JOIN taxrates AS t ON t.id = i.tax
			LEFT JOIN prodExtra as x on i.upc = x.upc
                        WHERE i.department BETWEEN ? AND ? 
			ORDER BY ".$order;
		$args = array($deptStart, $deptEnd);
		if ($supertype == 'dept' && $super != 0){
			$query = "SELECT i.upc,i.description,d.dept_name as department,
				i.normal_price,                      
				(CASE WHEN i.tax = 1 THEN 'X' WHEN i.tax=0 THEN '-' ELSE LEFT(t.description,1) END) as Tax,              
				(CASE WHEN i.foodstamp = 1 THEN 'X' ELSE '-' END) as FS,
				(CASE WHEN i.discount = 0 THEN '-' ELSE 'X'END) as DISC,
				(CASE WHEN i.scale = 1 THEN 'X' ELSE '-' END) as WGHd,
				(CASE WHEN i.local = 1 THEN 'X' ELSE '-' END) as local,
				x.distributor
				FROM products as i LEFT JOIN superdepts as s ON i.department = s.dept_ID
				LEFT JOIN taxrates AS t ON t.id = i.tax
				LEFT JOIN departments as d on i.department = d.dept_no
				LEFT JOIN prodExtra as x on i.upc = x.upc
				WHERE s.superID = ?
				ORDER BY ".$order;
			$args = array($super);
		}
		else if ($supertype == 'manu'){
			$query = "SELECT i.upc,i.description,d.dept_name as department,
				i.normal_price,                      
				(CASE WHEN i.tax = 1 THEN 'X' WHEN i.tax=0 THEN '-' ELSE LEFT(t.description,1) END) as Tax,              
				(CASE WHEN i.foodstamp = 1 THEN 'X' ELSE '-' END) as FS,
				(CASE WHEN i.discount = 0 THEN '-' ELSE 'X'END) as DISC,
				(CASE WHEN i.scale = 1 THEN 'X' ELSE '-' END) as WGHd,
				(CASE WHEN i.local = 1 THEN 'X' ELSE '-' END) as local,
				x.distributor
				FROM products as i LEFT JOIN departments as d ON i.department = d.dept_no
				LEFT JOIN prodExtra as x on i.upc = x.upc
				LEFT JOIN taxrates AS t ON t.id = i.tax";
			if ($mtype == 'prefix'){
				$query .= ' WHERE i.upc LIKE ? ';
			}
			else {
				$query .= ' WHERE x.manfacturer LIKE ? ';
			}
			$args = array('%'.$manufacturer.'%');
			$query .= "ORDER BY ".$order; 
		}
		if ($order != "i.upc")
			$query .= ",i.upc";

		$prep = $dbc->prepare_statement($query);
		$result = $dbc->exec_statement($prep, $args);

		if ($result === False || $dbc->num_rows($result) == 0){
			return 'No data found!';
		}

		$ret .= "<table border=1 cellspacing=0 cellpadding =3><tr>\n"; 
		if (!$this->excel){
			$ret .= sprintf('<tr><th><a href="%s&sort=UPC">UPC</a></th>
					<th><a href="%s&sort=Description">Description</a></th>
					<th><a href="%s&sort=Department">Department</a></th>
					<th><a href="%s&sort=Supplier">Supplier</a></th>
					<th><a href="%s&sort=Price">Price</a></th>',
					$page_url,$page_url,$page_url,$page_url,$page_url);
		}
		else
			$ret .= "<th>UPC</th><th>Description</th><th>Dept</th><th>Supplier</th><th>Price</th>";
		$ret .= "<th>Tax</th><th>FS</th><th>Disc</th><th>Wg'd</th><th>Local</th>";
		if (!$this->excel && $this->canEditItems !== False)
			$ret .= '<th>&nbsp;</th>';
		$ret .= "</tr>";

		while($row = $dbc->fetch_row($result)) {
			$ret .= '<tr id="'.$row[0].'">';
			$enc = base64_encode($row[1]);
			if (!$this->excel){
				$ret .= "<td align=center class=\"td_upc\"><a href=ItemEditorPage.php?searchupc=$row[0]>$row[0]</a>"; 
				if ($this->canDeleteItems !== False){
					$ret .= "<a href=\"\" onclick=\"deleteCheck('$row[0]','$enc'); return false;\">";
					$ret .= "<img src=\"{$FANNIE_URL}src/img/buttons/trash.png\" border=0 /></a>";
				}
				$ret .= '</td>';
			}
			else
				$ret .= "<td align=center>$row[0]</td>";
			$ret .= "<td align=center class=td_desc>$row[1]</td>";
			$ret .= "<td align=center class=td_dept>$row[2]</td>";
			$ret .= "<td align=center class=td_supplier>$row[9]</td>";
			$ret .= "<td align=center class=td_price>$row[3]</td>";
			$ret .= "<td align=center class=td_tax>$row[4]</td>";
			$ret .= "<td align=center class=td_fs>$row[5]</td>";
			$ret .= "<td align=center class=td_disc>$row[6]</td>";
			$ret .= "<td align=center class=td_wgt>$row[7]</td>";
			$ret .= "<td align=center class=td_local>$row[8]</td>";
			if (!$this->excel && $this->canEditItems !== False){
				$ret .= "<td align=center class=td_cmd><a href=\"\" 
					onclick=\"edit('$row[0]'); return false;\">
					<img src=\"{$FANNIE_URL}src/img/buttons/b_edit.png\" alt=\"Edit\" 
					border=0 /></a></td>";
			}
			$ret .= "</tr>\n";
		}
		$ret .= '</table>';

		if ($this->excel){
			header('Content-Type: application/ms-excel');
			header('Content-Disposition: attachment; filename="itemList.csv"');
			$array = HtmlToArray($ret);
			$ret = ArrayToCsv($array);
		}

		return $ret;
	}

	function form_content(){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);
		$deptQ = $dbc->prepare_statement("select dept_no,dept_name from departments order by dept_no");
		$deptR = $dbc->exec_statement($deptQ);
		$depts = array();
		while ($deptW = $dbc->fetch_array($deptR)){
			$depts[$deptW['dept_no']] = $deptW['dept_name'];
		}
		$superQ = $dbc->prepare_statement("SELECT superID,super_name FROM superDeptNames WHERE 
			superID > 0 ORDER BY superID");
		$superR = $dbc->exec_statement($superQ);
		$supers = array();
		while ($superW = $dbc->fetch_row($superR)){
			$supers[$superW['superID']] = $superW['super_name'];
		}
		ob_start();
		?>
		<div id=textwlogo> 
		<form method = "get" action="ProductListPage.php">
		<b>Report by</b>:
		<input type=radio name=supertype value=dept checked  id="supertypeD"
			onclick="$('#dept1').show();$('#dept2').show();$('#manu').hide();" /> 
			<label for="supertypeD">Department</label>
		<input type=radio name=supertype value=manu id="supertypeM"
			onclick="$('#dept1').hide();$('#dept2').hide();$('#manu').show();" /> 
			<label for="supertypeM">Manufacturer</label>
		<table border="0" cellspacing="0" cellpadding="5">
		<tr class=dept id=dept1>
			<td valign=top><p><b>Buyer</b></p></td>
			<td><p><select name=deptSub>
			<option value=0></option>
			<?php
			foreach($supers as $id => $name)
				printf('<option value="%d">%s</option>',$id,$name);	
			?>
			</select></p>
			<i>Selecting a Buyer/Dept overrides Department Start/Department End.
			To run reports for a specific department(s) leave Buyer/Dept or set it to 'blank'</i></td>

		</tr>
		<tr class=dept id=dept2> 
			<td> <p><b>Department Start</b></p>
			<p><b>End</b></p></td>
			<td> <p>
			<select id=deptStartSelect onchange="$('#deptStart').val(this.value);">
			<?php
			foreach($depts as $id => $name)
				printf('<option value="%d">%d %s</option>',$id,$id,$name);	
			?>
			</select>
			<input type=text size= 5 id=deptStart name=deptStart value=1>
			</p>
			<p>
			<select id=deptEndSelect onchange="$('#deptEnd').val(this.value);">
			<?php
			foreach($depts as $id => $name)
				printf('<option value="%d">%d %s</option>',$id,$id,$name);	
			?>
			</select>
			<input type=text size= 5 id=deptEnd name=deptEnd value=1>
			</p></td>
		</tr>
		<tr class=manu id=manu style="display:none;">
			<td><p><b>Manufacturer</b></p>
			<p></p></td>
			<td><p>
			<input type=text name=manufacturer />
			</p>
			<p>
			<input type=radio name=mtype value=prefix checked />UPC prefix
			<input type=radio name=mtype value=name />Manufacturer name
			</p></td>
		</tr>
		<tr> 
			<td><b>Sort report by?</b></td>
			<td> <select name="sort" size="1">
				<option>Department</option>
				<option>UPC</option>
				<option>Description</option>
			</select> 
			<input type=checkbox name=excel /> <b>Excel</b></td>
			<td>&nbsp;</td>
			<td>&nbsp; </td>
			</tr>
			<td>&nbsp;</td>
			<td>&nbsp; </td>
		</tr>
		<tr> 
			<td> <input type=submit name=submit value="Submit"> </td>
			<td> <input type=reset name=reset value="Start Over"> </td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		</table>
		</form>
		</div>
		<?php
		return ob_get_clean();
	}

	function body_content(){
		if ($this->mode == 'form')
			return $this->form_content();
		else if ($this->mode == 'list')
			return $this->list_content();
		else
			return 'Unknown error occurred';
	}
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)){
	$obj = new ProductListPage();
	$obj->draw_page();
}

