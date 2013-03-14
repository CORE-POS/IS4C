<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

class AlwaysFsTotalFooter extends FooterBox {

	var $header_css = "color: #ffffff;";
	var $display_css = "font-weight:bold;font-size:150%;";

	function header_content(){
		global $CORE_LOCAL;
		if ( $CORE_LOCAL->get("ttlflag") == 1 and $CORE_LOCAL->get("End") != 1 ) {
			$this->header_css .= "background:#800000;";
			return _("Amount Due");
		}
		elseif ($CORE_LOCAL->get("ttlflag") == 1  and $CORE_LOCAL->get("End") == 1 ) {
			$this->header_css .= "background:#004080;";
			return _("Change");
		}	
		else {
			$this->header_css .= "background:#000000;";
			return _("Total");
		}
	}

	function display_content(){
		global $CORE_LOCAL;
		if ( $CORE_LOCAL->get("ttlflag") == 1 and $CORE_LOCAL->get("End") != 1 ) {
			$this->display_css .= "color:#800000;";
			return number_format($CORE_LOCAL->get("runningTotal"),2);
		}
		elseif ($CORE_LOCAL->get("ttlflag") == 1  and $CORE_LOCAL->get("End") == 1 ) {
			$this->display_css .= "color:#004080;";
			return number_format($CORE_LOCAL->get("runningTotal"),2);
		}	
		else {
			$this->display_css .= "color:#000000;";
			return number_format($CORE_LOCAL->get("runningTotal"),2);
		}
	}
}

?>
