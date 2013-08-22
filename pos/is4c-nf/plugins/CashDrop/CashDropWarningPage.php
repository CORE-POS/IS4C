<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include_once(dirname(__FILE__).'/../../lib/AutoLoader.php');

class CashDropWarningPage extends InputPage {

	function preprocess(){
		global $CORE_LOCAL;
		if (isset($_REQUEST['reginput'])){
			$in = strtoupper($_REQUEST['reginput']);
			if ($in != '' && $in != 'CL') return True;

			$CORE_LOCAL->set("msgrepeat",1);
			$CORE_LOCAL->set("strRemembered",$CORE_LOCAL->get('cashDropSaveInput'));
			$CORE_LOCAL->set('cashDropSaveInput','');

			$this->change_page($this->page_url."gui-modules/pos2.php");
			return False;
		}
		return True;
	}
	
	function body_content(){
		global $CORE_LOCAL;
		echo '<div class="baseHeight">';
		$ret = "<div id=\"boxMsg\" style=\"background:red;\" 
			class=\"centeredDisplay\">";
		$ret .= "<div class=\"boxMsgAlert coloredArea\">";
		$ret .= $CORE_LOCAL->get("alertBar");
		$ret .= "</div>";
		$ret .= "<div class=\"boxMsgBody\">";
		$ret .= "<div class=\"msgicon\"></div>";
		$ret .= "<div class=\"msgtext\">";
		$ret .= '1.83 jigawatts';
		$ret .= "</div><div class=\"clear\"></div></div>";
		$ret .= "</div>";
		echo $ret;
		echo "</div>";
	} // END body_content() FUNCTION
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__))
	new CashDropWarningPage();

?>
