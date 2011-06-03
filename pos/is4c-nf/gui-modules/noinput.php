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

if (!class_exists("BasicPage")) include_once($_SESSION["INCLUDE_PATH"]."/gui-class-lib/BasicPage.php");
if (!isset($CORE_LOCAL)) include($_SESSION["INCLUDE_PATH"]."/lib/LocalStorage/conf.php");

class noinput extends BasicPage {

	function body_content(){
		global $CORE_LOCAL;
		?>
		<div id="inputArea">
		<div class="inputform">&nbsp;</div>
		<div class="notices">
		<?php
		$time = strftime("%m/%d/%y  %I:%M %p", time());

		if ($CORE_LOCAL->get("training") == 1) {
			echo "<span class=\"text\">training </span>"
			     ."<img src='/graphics/BLUEDOT.GIF'>&nbsp;&nbsp:&nbsp;";
		}
		elseif ($CORE_LOCAL->get("standalone") == 0) {
			echo "<img src='/graphics/GREENDOT.GIF'>&nbsp;&nbsp;&nbsp;";
		}
		else {
			echo "<span class=\"text\">stand alone</span>"
			     ."<img src='/graphics/REDDOT.GIF'>&nbsp;&nbsp;&nbsp;";
		}
		?>
		</div>
		</div>
		<?php
	} // END body_content() FUNCTION
}

new noinput();
