<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op, Duluth, MN

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

include_once(dirname(__FILE__).'/../../config.php');
include_once(dirname(__FILE__).'/../../classlib2.0/FannieAPI.php');
include_once(dirname(__FILE__).'/../../src/JsonLib.php');

class BaseItemModule extends ItemModule {

	function ShowEditForm($upc){
		global $FANNIE_URL;
		$upc = BarcodeLib::padUPC($upc);

		$ret = '<fieldset id="BaseItemFieldset">';
		$ret .=  "<legend>Item</legend>";

		$dbc = $this->db();
		$p = $dbc->prepare_statement('SELECT p.*,x.*,u.description as ldesc FROM products AS p LEFT JOIN prodExtra
				AS x ON p.upc=x.upc LEFT JOIN productUser AS u ON p.upc=u.upc WHERE p.upc=?');
		$r = $dbc->exec_statement($p,array($upc));
		$rowItem = array();
		$prevUPC = False;
		$nextUPC = False;
		$likeCode = False;
		if($dbc->num_rows($r) > 0){
			//existing item
			$rowItem = $dbc->fetch_row($r);

			/* find previous and next items in department */
			$pnP = $dbc->prepare_statement('SELECT upc FROM products WHERE department=? ORDER BY upc');
			$pnR = $dbc->exec_statement($pnP,array($upc));
			$passed_it = False;
			while($pnW = $dbc->fetch_row($pnR)){
				if (!$passed_it && $upc != $pnW[0])
					$prevUPC = $pnW[0];
				else if (!$passed_it && $upc == $pnW[0])
					$passed_it = True;
				else if ($passed_it){
					$nextUPC = $pnW[0];
					break;		
				}
			}

			$lcP = $dbc->prepare_statement('SELECT likeCode FROM upcLike WHERE upc=?');
			$lcR = $dbc->exec_statement($lcP,array($upc));
			if ($dbc->num_rows($lcR) > 0)
				$likeCode = array_pop($dbc->fetch_row($lcR));
		}
		else {
			// new item
			$ret .= "<span style=\"color:red;\">Item not found.  You are creating a new one.  </span>";

			/**
			  Check for entries in the vendorItems table to prepopulate
			  fields for the new item
			*/
			$vendorP = "SELECT description,brand as manufacturer,cost,
				vendorName as distributor,margin,i.vendorID
				FROM vendorItems AS i LEFT JOIN vendors AS v ON i.vendorID=v.vendorID
				LEFT JOIN vendorDepartments AS d ON i.vendorDept=d.deptID
				WHERE upc=?";
			$args = array($upc);
			$vID = FormLib::get_form_value('vid','');
			if ($vID !== ''){
				$vendorP .= ' AND i.vendorID=?';
				$args[] = $vID;
			}
			$vendorP = $dbc->prepare_statement($vendorP);
			$vendorR = $dbc->exec_statement($vendorP,$args);
			
			if ($dbc->num_rows($vendorR) > 0){
				$v = $dbc->fetch_row($vendorR);
				$ret .= "<br /><i>This product is in the ".$v['distributor']." catalog. Values have
					been filled in where possible</i><br />";
				$rowItem['description'] = $v['description'];
				$rowItem['manufacturer'] = $v['manufacturer'];
				$rowItem['cost'] = $v['cost'];
				$rowItem['distributor'] = $v['distributor'];

				while($v = $dbc->fetch_row($vendorR)){
					printf('This product is also in <a href="?searchupc=%s&vid=%d">%s</a><br />',
						$upc,$v['vendorID'],$v['distributor']);
				}
			}

			/**
			  Look for items with a similar UPC to guess what
			  department this item goes in. If found, use 
			  department settings to fill in some defaults
			*/
			$rowItem['department'] = 0;
			$search = substr($upc,0,12);
			$searchP = $dbc->prepare_statement('SELECT department FROM products WHERE upc LIKE ?');
			while(strlen($search) >= 8){
				$searchR = $dbc->exec_statement($searchP,array($search.'%'));
				if ($dbc->num_rows($searchR) > 0){
					$rowItem['department'] = array_pop($dbc->fetch_row($searchR));
					$settingP = $dbc->prepare_statement('SELECT dept_tax,dept_fs,dept_discount
								FROM departments WHERE dept_no=?');
					$settingR = $dbc->exec_statement($settingP,array($rowItem['department']));
					if ($dbc->num_rows($settingR) > 0){
						$d = $dbc->fetch_row($settingR);
						$rowItem['tax'] = $d['dept_tax'];
						$rowItem['foodstamp'] = $d['dept_fs'];
						$rowItem['discount'] = $d['dept_discount'];
					}
					break;
				}
				$search = substr($search,0,strlen($search)-1);
			}
		}

        	$ret .= "<table border=1 cellpadding=5 cellspacing=0>";

		$ret .= '<tr><td align=right><b>UPC</b></td><td style="color:red;">'.$upc;
		$ret .= '<input type=hidden value="'.$upc.'" id=upc name=upc />';
		if ($prevUPC) $ret .= " <a style=\"font-size:85%;\" href=itemMaint.php?upc=$prevUPC>Previous</a>";
		if ($nextUPC) $ret .= " <a style=\"font-size:85%;\" href=itemMaint.php?upc=$nextUPC>Next</a>";
		$ret .= '</td>';

		// system for store-level records not refined yet; might go here
		$ret .= '<td colspan=2>';
		$ret .= '<input type="hidden" name="store_id" value="0" />';
		$ret .= '&nbsp;</td>';

		$ret .= '</tr><tr>';

		$limit = 35 - strlen(isset($rowItem['description'])?$rowItem['description']:'');
		$ret .= '<td><b>Description</b></td><td><input type=text size=30 value="'
			.(isset($rowItem['description'])?$rowItem['description']:'')
			.'" onkeyup="$(\'#dcounter\').html(35-(this.value.length));" '
			.' name=descript maxlength=35 id=descript>
			<span id=dcounter>'.$limit.'</span></td>'; 

		/**
		  Drop down box changes price field from single price to
		  X for $Y style pricing
		*/
		if (!isset($rowItem['pricemethod'])) $rowItem['pricemethod'] = 0;
		$ret .= '<td><b>Price</b></td>';
		$ret .= sprintf('<td>$<input id="price" name="price" type="text" size="6" value="%.2f" />
				</td>', (isset($rowItem['normal_price']) ? $rowItem['normal_price'] : 0)
		);

		$ret .= '</tr><tr>';

		$ret .= '<tr><td><b>Package Size</b></td><td><input type="text" name="size" size="4"
				value="'.(isset($rowItem['size'])?$rowItem['size']:'').'" />';
		$ret .= '<b>Unit of measure</b> <input type="text" name="unitm" size="4"
				value="'.(isset($rowItem['unitofmeasure'])?$rowItem['unitofmeasure']:'').'" /></td>';
		$ret .= '<td style="color:darkmagenta;">Last modified</td>
			<td style="color:darkmagenta;">'.$rowItem['modified'].'</td>';
		$ret .= '</tr>';

		$ret .= '<tr><td><b>Long Desc.</b><td colspan="2"><input type="text" size="60" name="puser_description"
				value="'.$rowItem['ldesc'].'" /></td><td>&nbsp;</td></tr>';

		$ret .="<td align=right><b>Brand</b></td><td><input type=text name=manufacturer size=30 value=\""
			.(isset($rowItem['manufacturer'])?$rowItem['manufacturer']:"")
			."\" /></td>";
		$ret .= "<td align=right><b>Vendor</b></td><td><input type=text name=distributor size=8 value=\""
			.(isset($rowItem['distributor'])?$rowItem['distributor']:"")
			."\" /></td>";
		$ret .= '</tr>';

		if (isset($rowItem['special_price']) && $rowItem['special_price'] <> 0){
			/* show sale info */
			$batchP = $dbc->prepare_statement("SELECT b.batchName FROM batches AS b 
				LEFT JOIN batchList as l
				on b.batchID=l.batchID WHERE '".date('Y-m-d')."' BETWEEN b.startDate
				AND b.endDate AND (l.upc=? OR l.upc=?)");
			$batchR = $dbc->exec_statement($batchP,array($upc,'LC'.$likeCode));
			$batch = "Unknown";
			if ($dbc->num_rows($batchR) > 0)
				$batch = array_pop($dbc->fetch_row($batchR));

			$ret .= '<tr>';
			$ret .= "<td style=\"color:green;\"><b>Sale Price:</b></td><td style=\"color:green;\">$rowItem[6] (<em>Batch: $batch</em>)</td>";
			list($date,$time) = explode(' ',$rowItem[11]);
           		$ret .= "<td style=\"color:green;\">End Date:</td>
				<td style=\"color:green;\">$date 
				(<a href=\"EndItemSale.php?id=$upc\">Unsale Now</a>)</td>";
			$ret .= '</tr>';
		}
		$ret .= "</table>";

		$ret .= "<table style=\"margin-top:5px;margin-bottom:5px;\" border=1 cellpadding=5 cellspacing=0 width='100%'>";
		$ret .= "<tr><th>Dept</th><th>Tax</th><th>FS</th>
			<th>Scale".FannieHelp::ToolTip('Item sold by weight')."</th>
			<th>QtyFrc".FannieHelp::ToolTip('Cashier must enter quantity')."</th>
			<th>NoDisc".FannieHelp::ToolTip('Item not subject to % discount')."</th></tr>";

		$depts = array();
		$subs = array();
		if (!isset($rowItem['subdept'])) $rowItem['subdept'] = 0;
		$p = $dbc->prepare_statement('SELECT dept_no,dept_name,subdept_no,subdept_name,dept_ID 
				FROM departments AS d
				LEFT JOIN subdepts AS s ON d.dept_no=s.dept_ID
				ORDER BY d.dept_no');
		$r = $dbc->exec_statement($p);
		while($w = $dbc->fetch_row($r)){
			if (!isset($depts[$w['dept_no']])) $depts[$w['dept_no']] = $w['dept_name'];
			if ($w['subdept_no'] == '') continue;
			if (!isset($subs[$w['dept_ID']]))
				$subs[$w['dept_ID']] = '';
			$subs[$w['deptID']] = sprintf('<option %s value="%d">%s</option>',
					($w['subdept_no'] == $rowItem['subdept'] ? 'selected':''),
					$w['subdept_no'],$w['subdept_name']);
		}

		$json = count($subs) == 0 ? '{}' : JsonLib::array_to_json($subs);
		ob_start();
		?>
		<script type="text/javascript">;
		function chainSelects(val){
			var lookupTable = <?php echo $json; ?>;
			if (val in lookupTable)
				$('#subdept').html(lookupTable[val]);
			else
				$('#subdept').html('<option value=0>None</option>');
			$.ajax({
				url: '<?php echo $FANNIE_URL; ?>item/modules/BaseItemModule.php',
				data: 'dept_defaults='+val,
				dataType: 'json',
				cache: false,
				success: function(data){
					if (data.tax)
						$('#tax').val(data.tax);
					if (data.fs)
						$('#FS').attr('checked','checked');
					else{
						$('#FS').removeAttr('checked');
					}
					if (data.nodisc)
						$('#NoDisc').attr('checked','checked');
					else
						$('#NoDisc').removeAttr('checked');
				}

			});
		}
		</script>
		<?php
		$ret .= ob_get_clean();

		$ret .= "<tr align=top>";
		$ret .= "<td align=left>";	
		$ret .= '<select name="department" id="department" onchange="chainSelects(this.value);">';
		foreach($depts as $id => $name){
			$ret .= sprintf('<option %s value="%d">%d %s</option>',
					($id == $rowItem['department'] ? 'selected':''),
					$id,$id,$name);
		}
		$ret .= '</select>';
		$ret .= '<select name="subdept" id="subdept">';
		$ret .= isset($subs[$rowItem['department']]) ? $subs[$rowItem['department']] : '<option value="0">None</option>';
		$ret .= '</select>';
		$ret .= '</td>';

		$taxQ = $dbc->prepare_statement('SELECT id,description FROM taxrates ORDER BY id');
		$taxR = $dbc->exec_statement($taxQ);
		$rates = array();
		while ($taxW = $dbc->fetch_row($taxR))
			array_push($rates,array($taxW[0],$taxW[1]));
		array_push($rates,array("0","NoTax"));
		$ret .= '<td align="left"><select name="tax" id="tax">';
		foreach($rates as $r){
			$ret .= sprintf('<option %s value="%d">%s</option>',
				(isset($rowItem['tax'])&&$rowItem['tax']==$r[0]?'selected':''),
				$r[0],$r[1]);
		}
		$ret .= '</select></td>';

		$ret .= sprintf('<td align="center"><input type="checkbox" value="1" name="FS" id="FS" %s /></td>',
				(isset($rowItem['foodstamp']) && $rowItem['foodstamp']==1 ? 'checked' : ''));

		$ret .= sprintf('<td align="center"><input type="checkbox" value="1" name="Scale" %s /></td>',
				(isset($rowItem['scale']) && $rowItem['scale']==1 ? 'checked' : ''));

		$ret .= sprintf('<td align="center"><input type="checkbox" value="1" name="QtyFrc" %s /></td>',
				(isset($rowItem['qttyEnforced']) && $rowItem['qttyEnforced']==1 ? 'checked' : ''));

		$ret .= sprintf('<td align="center"><input type="checkbox" value="0" id="NoDisc" name="NoDisc" %s /></td>',
				(isset($rowItem['discount']) && $rowItem['discount']==0 ? 'checked' : ''));

		$ret .= '</tr>';
		$ret .= '</table></fieldset>';
		return $ret;
	}

	function SaveFormData($upc){
		$upc = BarcodeLib::padUPC($upc);
		$dbc = $this->db();

		$up_array = array();
		$up_array['tax'] = FormLib::get_form_value('tax',0);
		$up_array['foodstamp'] = FormLib::get_form_value('FS',0);
		$up_array['scale'] = FormLib::get_form_value('Scale',0);
		$up_array['qttyEnforced'] = FormLib::get_form_value('QtyFrc',0);
		$up_array['discount'] = FormLib::get_form_value('NoDisc',1);
		$up_array['normal_price'] = FormLib::get_form_value('price',0.00);
		$up_array['description'] = FormLib::get_form_value('descript','');
		$up_array['pricemethod'] = 0;
		$up_array['groupprice'] = 0.00;
		$up_array['quantity'] = 0;
		$up_array['department'] = FormLib::get_form_value('department',0);
		$up_array['size'] = FormLib::get_form_value('size','');
		$up_array['scaleprice'] = 0.00;
		$up_array['modified'] = $dbc->now();
		$up_array['advertised'] = 1;
		$up_array['tareweight'] = 0;
		$up_array['unitofmeasure'] = FormLib::get_form_value('unitm','');
		$up_array['wicable'] = 0;
		$up_array['idEnforced'] = 0;
		$up_array['subdept'] = FormLib::get_form_value('subdepartment',0);
		$up_array['store_id'] = 0;

		/* turn on volume pricing if specified, but don't
		   alter pricemethod if it's already non-zero */
		$doVol = FormLib::get_form_value('doVolume',False);
		$vprice = FormLib::get_form_value('vol_price','');
		$vqty = FormLib::get_form_value('vol_qtty','');
		if ($doVol !== False && is_numeric($vprice) && is_numeric($vqty)){
			$up_array['pricemethod'] = FormLib::get_form_value('pricemethod',0);
			if ($up_array['pricemethod']==0) $up_array['pricemethod']=2;
			$up_array['groupprice'] = $vprice;
			$up_array['quantity'] = $vqty;
		}

		ProductsModel::update($upc, $up_array);

		if ($dbc->table_exists('prodExtra')){
			$arr = array();
			$arr['manufacturer'] = $dbc->escape(FormLib::get_form_value('manufacturer'));
			$arr['distributor'] = $dbc->escape(FormLib::get_form_value('distributor'));
			$arr['location'] = 0;

			$checkP = $dbc->prepare_statement('SELECT upc FROM prodExtra WHERE upc=?');
			$checkR = $dbc->exec_statement($checkP,array($upc));
			if ($dbc->num_rows($checkR) == 0){
				// if prodExtra record doesn't exist, needs more values
				$arr['upc'] = $dbc->escape($upc);
				$arr['variable_pricing'] = 0;
				$arr['margin'] = 0;
				$arr['case_quantity'] = "''";
				$arr['case_cost'] = 0.00;
				$arr['case_info'] = "''";
				$dbc->smart_insert('prodExtra',$arr);
			}
			else {
				$dbc->smart_update('prodExtra',$arr,"upc='$upc'");
			}
		}

		if ($dbc->table_exists('productUser')){
			$ldesc = FormLib::get_form_value('puser_description');
			$model = new ProductUserModel($dbc);
			$model->upc($upc);
			$model->description($ldesc);
			$model->save();
		}
	}

	function AjaxCallback(){
		$json = array('tax'=>0,'fs'=>False,'nodisc'=>False);
		$dept = FormLib::get_form_value('dept_defaults','');
		$db = $this->db();
		$p = $db->prepare_statement('SELECT dept_tax,dept_fs,dept_discount
				FROM departments WHERE dept_no=?');
		$r = $db->exec_statement($p,array($dept));
		if ($db->num_rows($r)){
			$w = $db->fetch_row($r);
			$json['tax'] = $w['dept_tax'];
			if ($w['dept_fs'] == 1) $json['fs'] = True;
			if ($w['dept_discount'] == 0) $json['nodisc'] = True;
		}
		echo JsonLib::array_to_json($json);
	}
}

/**
  This form does some fancy tricks via AJAX calls. This block
  ensures the AJAX functionality only runs when the script
  is accessed via the browser and not when it's included in
  another PHP script.
*/
if (basename($_SERVER['SCRIPT_NAME']) == basename(__FILE__)){
	$obj = new BaseItemModule();
	$obj->AjaxCallback();	
}

?>
