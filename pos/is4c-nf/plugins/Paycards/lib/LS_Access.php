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

/**
 @class LS_Access
 Alternate for LocalStorage to
 simplify auditing cc-handling code
*/
class LS_Access {

	function get($str){
		if (!isset($_SESSION["$str"])) return "";
		return $_SESSION["$str"];
	}

	function set($k, $v){
		$_SESSION["$k"] = $v;
	}
}

if(ini_get('session.auto_start')==0 && !headers_sent())
	@session_start();

?>
