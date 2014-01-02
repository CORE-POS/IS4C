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

include_once(dirname(__FILE__).'/../../classlib2.0/item/ItemModule.php');
include_once(dirname(__FILE__).'/../../classlib2.0/lib/FormLib.php');

class ScaleItemModule extends ItemModule {

	function ShowEditForm($upc){
		$upc = BarcodeLib::padUPC($upc);

		$ret = '<fieldset id="ScaleItemFieldset">';
		$ret .=  "<legend>Scale</legend>";
		
		$dbc = $this->db();
		$p = $dbc->prepare_statement('SELECT * FROM scaleItems WHERE plu=?');
		$r = $dbc->exec_statement($p,array($upc));
		$scale = array('itemdesc'=>'','weight'=>0,'bycount'=>0,'tare'=>0,
			'shelflife'=>0,'label'=>133,'graphics'=>0,'text'=>'');
		if ($dbc->num_rows($r) > 0)
			$scale = $dbc->fetch_row($r);

		$p = $dbc->prepare_statement('SELECT description FROM products WHERE upc=?');
		$r = $dbc->exec_statement($p,array($upc));
		$reg_description = '';
		if ($dbc->num_rows($r) > 0)
			$reg_description = array_pop($dbc->fetch_row($r));

		$ret .= sprintf('<input type="hidden" name="s_plu" value="%s" />',$upc);
		$ret .= "<table style=\"background:#ffffcc;\" cellpadding=5 cellspacing=0 border=1>";
		$ret .= sprintf("<tr><th colspan=2>Longer description</th><td colspan=4><input size=35 
				type=text name=s_longdesc maxlength=100 value=\"%s\" /></td></tr>",
				($reg_description == $scale['itemdesc'] ? '': $scale['itemdesc']));
		$ret .= "<tr><td colspan=6 style=\"font-size:1px;\">&nbsp;</td></tr>";

		$ret .= "<tr><th>Weight</th><th>By Count</th><th>Tare</th><th>Shelf Life</th>";
		$ret .= "<th>Label</th><th>Safehandling</th></tr>";			

		$ret .= '<tr><td>';
		if ($scale['weight']==0){
			$ret .= "<input type=radio name=s_type value=\"Random Weight\" checked /> Random<br />";
			$ret .= "<input type=radio name=s_type value=\"Fixed Weight\" /> Fixed<br />";
		}
		else {
			$ret .= "<input type=radio name=s_type value=\"Random Weight\" /> Random<br />";
			$ret .= "<input type=radio name=s_type value=\"Fixed Weight\" checked /> Fixed<br />";
		}
		$ret .= '</td>';

		$ret .= sprintf("<td align=center><input type=checkbox value=1 name=s_bycount %s /></td>",
				($scale['bycount']==1?'checked':''));

		$ret .= sprintf("<td align=center><input type=text size=5 name=s_tare value=\"%s\" /></td>",
				$scale['tare']);

		$ret .= sprintf("<td align=center><input type=text size=5 name=s_shelflife value=\"%s\" /></td>",
				$scale['shelflife']);

		$ret .= "<td><select name=s_label size=2>";
		if ($scale['label']==133 || $scale['label']==63){
			$ret .= "<option value=horizontal selected>Horizontal</option>";
			$ret .= "<option value=vertical>Vertical</option>";
		}
		else {
			$ret .= "<option value=horizontal>Horizontal</option>";
			$ret .= "<option value=vertical selected>Vertical</option>";
		}
		$ret .= '</select></td>';

		$ret .= sprintf("<td align=center><input type=checkbox value=1 name=s_graphics %s /></td>",
				($scale['graphics']==1?'checked':''));
		$ret .= '</tr>';	

		$ret .= "<tr><td colspan=6 style=\"font-size:1px;\">&nbsp;</td></tr>";

		$ret .= "<tr><td colspan=6>";
		$ret .= "<b>Expanded text:<br /><textarea name=s_text rows=4 cols=50>";
		$ret .= $scale['text'];
		$ret .= "</textarea></td></tr>";

		$ret .= '</table></fieldset>';
		return $ret;
	}

	function SaveFormData($upc){
		/* check if data was submitted */
		if (FormLib::get_form_value('s_plu') === '') return False;

		$desc = FormLib::get_form_value('description','');
		$longdesc = FormLib::get_form_value('s_longdesc','');
		if ($longdesc !== '') $desc = $longdesc;
		$price = FormLib::get_form_value('price',0);
		$tare = FormLib::get_form_value('s_tare',0);
		$shelf = FormLib::get_form_value('s_shelflife',0);
		$bycount = FormLib::get_form_value('s_bycount',0);
		$graphics = FormLib::get_form_value('s_graphics',0);
		$type = FormLib::get_form_value('s_type','Random Weight');
		$weight = ($type == 'Random Weight') ? 0 : 1;
		$text = FormLib::get_form_value('s_text','');
		$label = FormLib::get_form_value('s_label','horizontal');

		if ($label == "horizontal" && $type == "Random Weight")
			$label = 133;
		elseif ($label == "horizontal" && $type == "Fixed Weight")
			$label = 63;
		elseif ($label == "vertical" && $type == "Random Weight")
			$label = 103;
		elseif ($label == "vertical" && $type == "Fixed Weight")
			$label = 23;

		$dbc = $this->db();
		$chkP = $dbc->prepare_statement('SELECT * FROM scaleItems WHERE plu=?');
		$chkR = $dbc->exec_statement($chkP,array($upc));
		$action = 'ChangeOneItem';
		if ($dbc->num_rows($chkR) == 0){
			$insP = $dbc->prepare_statement("INSERT INTO scaleItems (plu, price, itemdesc,
					exceptionprice, weight, bycount, tare, shelflife, text, class,
					label, graphics) VALUES (?, ?, ?, 0.00, ?, ?, ?, ?, '', ?, ?)");
			$insR = $dbc->exec_statement($insP,array($upc, $price, $desc, $weight, $bycount,
								$tare, $shelf, $text, $label, $graphics));
			$action = "WriteOneItem";
		}
		else {
			$upP = $dbc->prepare_statement('UPDATE scaleItems SET price=?, itemdesc=?, weight=?,
						bycount=?, tare=?, shelflife=?, text=?, label=?, graphics=?
						WHERE plu=?');
			$upR = $dbc->exec_statement($upP,array($price, $desc, $weight, $bycount, $tare,
								$shelf, $text, $label, $graphics, $upc));
			$action = "ChangeOneItem";
		}

		include(dirname(__FILE__).'/../hobartcsv/parse.php');
		parseitem($action,$upc,$desc,$tare,$shelf,$price,$bycount,$type,
			0.00,$text,$label,($graphics==1?121:0));
	}
}

?>
