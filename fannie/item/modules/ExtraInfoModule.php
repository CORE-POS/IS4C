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
include_once(dirname(__FILE__).'/../../classlib2.0/data/controllers/ProductsController.php');

class ExtraInfoModule extends ItemModule {

	function ShowEditForm($upc){
		$upc = str_pad($upc,13,0,STR_PAD_LEFT);

		$ret = '<fieldset id="ExtraInfoFieldset">';
		$ret .=  "<legend>Extra Info</legend>";

		$info = array('cost'=>0.00,'deposit'=>0,'local'=>0,'inUse'=>1,'modified'=>'Unknown');
		$dbc = $this->db();
		$p = $dbc->prepare_statement('SELECT cost,deposit,local,inUse,modified FROM products WHERE upc=?');
		$r = $dbc->exec_statement($p,array($upc));
		if ($dbc->num_rows($r) > 0)
			$info = $dbc->fetch_row($r);
		
		$ret .= "<table style=\"margin-top:5px;margin-bottom:5px;\" border=1 cellpadding=5 cellspacing=0 width='100%'><tr>";
		$ret .= '<tr><th>Deposit</th><th>Cost</th><th>Local</th><th>In Use</th></tr>';
		$ret .= sprintf('<tr>
				<td align="center"><input type="text" size="5" value="%d" name="deposit" /></td>
				<td align="center"><input type="text" size="5" value="%.2f" id="cost" name="cost" /></td>
				<td align="center"><input type="checkbox" name="local" value="1" %s /></td>
				<td align="center"><input type="checkbox" name="inUse" value="1" %s /></td></tr>',
				$info['deposit'],$info['cost'],
				($info['local']==1 ? 'checked': ''),
				($info['inUse']==1 ? 'checked': '')
		);
		$ret .= '<tr><td colspan="4" style="color:darkmagenta;">Last modified: '.$info['modified'].'</td></tr>';		
		$ret .= '</table></fieldset>';
		return $ret;
	}

	function SaveFormData($upc){
		$upc = str_pad($upc,13,0,STR_PAD_LEFT);
		$deposit = FormLib::get_form_value('deposit',0);
		$cost = FormLib::get_form_value('cost',0.00);
		$inUse = FormLib::get_form_value('inUse',0);
		$local = FormLib::get_form_value('local',0);

		$r1 = ProductsController::update($upc,array('deposit'=>$deposit,'local'=>$local,
				'inUse'=>$inUse,'cost'=>$cost));
		$dbc = $this->db();
		$p = $dbc->prepare_statement('UPDATE prodExtra SET cost=? WHERE upc=?');
		$r2 = $dbc->exec_statement($p,array($cost,$upc));
	
		if ($r1 === False || $r2 === False)
			return False;
		else
			return True;	
	}
}

?>
