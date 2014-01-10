<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/


/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	 7Sep2012 Eric Lee In getVendorInfo() display VendorID on successful lookup.

*/

include('../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);

if (isset($_REQUEST['action'])){
	switch($_REQUEST['action']){
	case 'saveScript':
		$q1 = $dbc->prepare_statement("DELETE FROM vendorLoadScripts WHERE vendorID=?");
		$dbc->exec_statement($q1,array($_REQUEST['vid']));
		$q2 = $dbc->prepare_statement("INSERT INTO vendorLoadScripts (vendorID,loadScript) VALUES (?,?)");
		$dbc->exec_statement($q2,array($_REQUEST['vid'],$_REQUEST['script']));
		break;
	}
}
