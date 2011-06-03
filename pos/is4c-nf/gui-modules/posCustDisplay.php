<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

    This file is part of IT CORE.

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
 
session_cache_limiter('nocache');

if (!class_exists("BasicPage")) include_once($_SESSION["INCLUDE_PATH"]."/gui-class-lib/BasicPage.php");

if (!function_exists("lastpage")) include($_SESSION["INCLUDE_PATH"]."/lib/listitems.php");
if (!function_exists("printheaderb")) include($_SESSION["INCLUDE_PATH"]."/lib/drawscreen.php");
if (!isset($CORE_LOCAL)) include($_SESSION["INCLUDE_PATH"]."/lib/LocalStorage/conf.php");

class posCustDisplay extends BasicPage {

	function body_content(){
		global $CORE_LOCAL;
		?>
		<div class="baseHeight">
		<?php

		if ($CORE_LOCAL->get("plainmsg") && strlen($CORE_LOCAL->get("plainmsg")) > 0) {
			printheaderb();
			echo "<div class=\"centerOffset\">";
			plainmsg($CORE_LOCAL->get("plainmsg"));
			echo "</div>";
			echo "</div>"; // end of baseHeight
		}
		else {	
			// No input and no messages, so
			// list the items
			if ($CORE_LOCAL->get("End") == 1)
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
