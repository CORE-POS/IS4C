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
include($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');
include($FANNIE_ROOT.'classlib2.0/lib/FormLib.php');

class ItemFlags extends FanniePage {
	
	private $msgs;

	function preprocess(){
		global $FANNIE_OP_DB;
		$this->title = 'Fannie - Item Maintanence';
		$this->header = 'Item Maintanence';
		$this->msgs = array();
		$db = FannieDB::get($FANNIE_OP_DB);

		if (FormLib::get_form_value('addBtn') !== ''){
			$desc = FormLib::get_form_value('new');			
			if (empty($desc)) $msgs[] = 'Error: no new description given';
			else {
				$bit=1;
				$chkP = $db->prepare_statement("SELECT bit_number FROM prodFlags WHERE bit_number=?");
				for($i=0; $i<32; $i++){
					$chkR = $db->exec_statement($chkP,array($bit));
					if ($db->num_rows($chkR) == 0) break;
					$bit *= 2;
				}
				if ($bit > (1<<32)) $msgs[] = 'Error: can\' add more flags';
				else {
					$insP = $db->prepare_statement("INSERT INTO prodFlags 
								(bit_number, description) VALUES (?,?)");
					$db->exec_statement($insP,array($bit,$desc));	
				}
			}
		}
		elseif (FormLib::get_form_value('updateBtn') !== ''){
			$ids = FormLib::get_form_value('mask',array());
			$descs = FormLib::get_form_value('desc',array());
			$upP = $db->prepare_statement("UPDATE prodFlags SET description=? WHERE bit_number=?");
			for($i=0;$i<count($ids);$i++){
				if (isset($descs[$i]) && !empty($descs[$i])){
					$db->exec_statement($upP,array($descs[$i],$ids[$i]));	
				}
			}
		}
		elseif (FormLib::get_form_value('delBtn') !== ''){
			$ids = FormLib::get_form_value('del',array());
			$delP = $db->prepare_statement("DELETE FROM prodFlags WHERE bit_number=?");
			foreach($ids as $id)
				$db->exec_statement($delP,array($id));
		}

		return True;
	}

	function body_content(){
		global $FANNIE_OP_DB;
		echo '<form action="'.$_SERVER['PHP_SELF'].'" method="post">';
		$db = FannieDB::get($FANNIE_OP_DB);
		$q = "SELECT bit_number,description FROM prodFlags ORDER BY description";
		$r = $db->query($q);
		echo '<b>Current Flags</b>:<br />';
		echo '<table cellpadding="4" cellspacing="0" border="1">';
		while($w = $db->fetch_row($r)){
			printf('<tr><td><input type="text" name="desc[]" value="%s" />
				<input type="hidden" name="mask[]" value="%d" /></td>
				<td><input type="checkbox" name="del[]" value="%d" /></td>
				</tr>',
				$w['description'],$w['bit_number'],$w['bit_number']
			);
		}
		echo '</table>';
		echo '<input type="submit" name="updateBtn" value="Update Descriptions" /> | ';
		echo '<input type="submit" name="delBtn" value="Delete Selected" /> ';
		echo '</form>';
		echo '<hr />';
		echo '<form action="'.$_SERVER['PHP_SELF'].'" method="post">';
		echo '<b>New</b>: <input type="text" name="new" /> ';
		echo '<input type="submit" name="addBtn" value="Add New Flag" />';
		echo '</form>';
	}
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)){
	$obj = new ItemFlags();
	$obj->draw_page();
}

?>
