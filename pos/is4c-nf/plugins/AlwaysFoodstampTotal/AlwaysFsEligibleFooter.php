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

class AlwaysFsEligibleFooter extends FooterBox {

	var $header_css = "color: #ffffff;";
	var $display_css = '';

	function AlwaysFsEligibleFooter() {
		global $CORE_LOCAL;
		if ($CORE_LOCAL->get('fntlflag') == 0 && $CORE_LOCAL->get('End') != 1){
			$CORE_LOCAL->set("fntlflag",1);
			Database::setglobalvalue("FntlFlag", 1);
		}
	}

	function header_content(){
		global $CORE_LOCAL;
		$this->header_css .= "background:#800080;";
		return _("FS Eligible");
	}

	function display_content(){
		global $CORE_LOCAL;
		$this->display_css .= "color:#800080;";
		if ($CORE_LOCAL->get('End') != 1)
			return number_format($CORE_LOCAL->get("fsEligible"),2);
		else
			return '0.00';
	}
		
}
