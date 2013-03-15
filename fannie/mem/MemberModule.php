<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

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

class MemberModule {

	function db(){
		global $FANNIE_ROOT,$FANNIE_OP_DB;
		if (!class_exists('FannieDB')) include_once($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');
		return FannieDB::get($FANNIE_OP_DB);
	}

	function ShowEditForm($memNum,$country="US"){

	}

	function SaveFormData($memNum){

	}

	function HasSearch(){
		return False;
	}

	function ShowSearchForm($country="US"){

	}

	function GetSearchResults(){

	}
	
	function RunCron(){

	}
}

?>
