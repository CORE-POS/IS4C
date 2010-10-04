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

if (!class_exists("MainFramePage")) include_once($_SERVER["DOCUMENT_ROOT"]."/gui-class-lib/MainFramePage.php");
if (!function_exists("mDataConnect")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/connect.php");
if (!function_exists("changeBothPages")) include_once($_SERVER["DOCUMENT_ROOT"]."/gui-base.php");
if (!isset($IS4C_LOCAL)) include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/conf.php");

class productDump extends MainFramePage {
	var $result;
	var $db;

	function body_content(){
		global $IS4C_LOCAL;
		?>
		<form name='form1' method='post' action='../prodInfo.php'>
		<input Type='hidden' name='input' size='20' tabindex='0'>
		</form>

		<div class="baseHeight">
		<div class="colored centeredDisplay">
		<span class="larger">Scan the product</span>
		<p />
		[clear] to cancel
		</div>
		</div>

		<?php
		$IS4C_LOCAL->set("beep","goodBeep");
		$IS4C_LOCAL->set("scan","scan");
	}
}

new productDump();
