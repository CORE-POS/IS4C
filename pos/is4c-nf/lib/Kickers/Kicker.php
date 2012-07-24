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

		if ($CORE_LOCAL->get("chargeTotal") == $CORE_LOCAL->get("tenderTotal") && $CORE_LOCAL->get("chargeTotal") != 0 && $CORE_LOCAL->get("tenderTotal") != 0 ) {	
			if (in_array($CORE_LOCAL->get("TenderType"),$CORE_LOCAL->get("DrawerKickMedia"))) {
				return True;
			} else {
				return False;
			}
		}

		return False;
	}
}

?>
