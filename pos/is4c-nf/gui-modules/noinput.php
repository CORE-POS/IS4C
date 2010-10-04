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

if (!class_exists("BasicPage")) include_once($_SERVER["DOCUMENT_ROOT"]."/gui-class-lib/BasicPage.php");
if (!isset($IS4C_LOCAL)) include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/conf.php");

class noinput extends BasicPage {

	function body_content(){
		global $IS4C_LOCAL;
		?>
		<div id="inputArea">
		<div class="inputform">&nbsp;</div>
		<div class="notices">
		<?php
		$time = strftime("%m/%d/%y  %I:%M %p", time());

		if ($IS4C_LOCAL->get("training") == 1) {
			echo "<span class=\"text\">training </span>"
			     ."<img src='/graphics/BLUEDOT.GIF'>&nbsp;&nbsp:&nbsp;";
		}
		elseif ($IS4C_LOCAL->get("standalone") == 0) {
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
