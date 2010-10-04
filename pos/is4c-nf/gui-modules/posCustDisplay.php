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
 
session_cache_limiter('nocache');

if (!class_exists("BasicPage")) include_once($_SERVER["DOCUMENT_ROOT"]."/gui-class-lib/BasicPage.php");

if (!function_exists("lastpage")) include($_SERVER["DOCUMENT_ROOT"]."/lib/listitems.php");
if (!function_exists("printheaderb")) include($_SERVER["DOCUMENT_ROOT"]."/lib/drawscreen.php");
if (!isset($IS4C_LOCAL)) include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/conf.php");

class posCustDisplay extends BasicPage {

	function body_content(){
		global $IS4C_LOCAL;
		?>
		<div class="baseHeight">
		<?php

		if ($IS4C_LOCAL->get("plainmsg") && strlen($IS4C_LOCAL->get("plainmsg")) > 0) {
			printheaderb();
			echo "<div class=\"centerOffset\">";
			plainmsg($IS4C_LOCAL->get("plainmsg"));
			echo "</div>";
			echo "</div>"; // end of baseHeight
		}
		else {	
			// No input and no messages, so
			// list the items
			if ($IS4C_LOCAL->get("End") == 1)
				printReceiptfooter(True);
			else
				lastpage(True);
		}
		echo "</div>"; // end base height

		printfooter(True);

	} // END body_content() FUNCTION
}

new posCustDisplay();

?>
