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
  @class Kicker
  Base class for opening cash drawer

*/
class Kicker {

	/**
	  Determine whether to open the drawer
	  @return boolean
	*/
	function doKick(){
		global $CORE_LOCAL;
		$db = Database::tDataConnect();

		$query = "select trans_id from localtemptrans where 
			(trans_subtype = 'CA' and total <> 0)";

		$result = $db->query($query);
		$num_rows = $db->num_rows($result);

		return ($num_rows > 0) ? True : False;

	}

	/**
	  Determine whether to open the drawer when
	  a cashier signs in
	  @return boolean
	*/
	function kickOnSignIn(){
		return True;
	}

	/**
	  Determine whether to open the drawer when
	  a cashier signs out
	  @return boolean
	*/
	function kickOnSignOut(){
		return True;
	}
}

?>
