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
 
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class posCustDisplay extends BasicPage 
{

	public function body_content()
    {
		global $CORE_LOCAL;
        echo $this->noinput_header();
		?>
		<div class="baseHeight">
		<?php

		if ($CORE_LOCAL->get("plainmsg") && strlen($CORE_LOCAL->get("plainmsg")) > 0) {
			echo DisplayLib::printheaderb();
			echo "<div class=\"centerOffset\">";
			echo DisplayLib::plainmsg($CORE_LOCAL->get("plainmsg"));
			echo "</div>";
		} else {	
			// No input and no messages, so
			// list the items
			if ($CORE_LOCAL->get("End") == 1) {
				echo DisplayLib::printReceiptfooter(true);
			} else {
				echo DisplayLib::lastpage(true);
            }
		}
		echo "</div>"; // end base height

		echo "<div id=\"footer\">";
		echo DisplayLib::printfooter(true);
        echo '</div>';

	} // END body_content() FUNCTION
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
	new posCustDisplay();
}

