<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists("InputPage")) include_once($IS4C_PATH."gui-class-lib/InputPage.php");
if (!class_exists("ScrollItems")) include_once($IS4C_PATH."parser-class-lib/parse/ScrollItems.php");
if (!function_exists("addItem")) include_once($IS4C_PATH."lib/additem.php");
if (!function_exists("setMember")) include_once($IS4C_PATH."lib/prehkeys.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");


/* wraps around an undone transaction to limit editing options
   CL cancels the attempt (wraps to input "CN")
   {Enter} finishes the transaction (wraps to input "0CA")
*/
class undo_confirm extends InputPage {
	var $box_color;
	var $msg;

	function body_content(){
		global $IS4C_LOCAL;
		?>
		<div class="baseHeight">
		<?php echo lastpage(); ?>
		</div>
		<?php
		$this->add_onload_command("\$('#reginput').keyup(function(ev){
					switch(ev.keyCode){
					case 33:
						\$('#reginput').val('U11');
						\$('#formlocal').submit();
						break;
					case 38:
						\$('#reginput').val('U');
						\$('#formlocal').submit();
						break;
					case 34:
						\$('#reginput').val('D11');
						\$('#formlocal').submit();
						break;
					case 40:
						\$('#reginput').val('D');
						\$('#formlocal').submit();
						break;
					}
				});\n");
		$this->add_onload_command("\$('#reginput').focus();");
		$IS4C_LOCAL->set("beep","noScan");
	}

	function preprocess(){
		global $IS4C_LOCAL,$IS4C_PATH;
		if (isset($_REQUEST['reginput'])){
			switch(strtoupper($_REQUEST['reginput'])){
			case 'CL':
				// zero removes password check I think
				$IS4C_LOCAL->set("runningTotal",0);
				$IS4C_LOCAL->set("msgrepeat",1);
				$IS4C_LOCAL->set("strRemembered","CN");
				header("Location: {$IS4C_PATH}gui-modules/pos2.php");
				return False;
				break;
			case '':
				$IS4C_LOCAL->set("msgrepeat",1);
				$IS4C_LOCAL->set("strRemembered","0CA");
				header("Location: {$IS4C_PATH}gui-modules/pos2.php");
				return False;
				break;
			case 'U':
			case 'U11':
			case 'D':
			case 'D11':
				// just use the parser module here
				// for simplicity; all its really
				// doing is updating a couple session vars
				$si = new ScrollItems();
				$si->parse($_REQUEST['reginput']);
				break;
			default:
				break;
			}
		}
		return True;
	}
}

new undo_confirm();
