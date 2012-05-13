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

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

/**
  @file
  @brief Functions for opening the till drawer
  @deprecated See ReceiptLib
*/

/**
  Check whether drawer should open on this transaction
  @return
   - 1 open drawer
   - 0 do not open
*/
function setDrawerKick()

{
	global $CORE_LOCAL;

//	this, the simplest version, kicks the drawer for every tender *except* staff charge & business charge (MI, CX)
// 	apbw 05/03/05 KickFix added !=0 criteria

	if ($CORE_LOCAL->get("chargeTotal") == $CORE_LOCAL->get("tenderTotal") && $CORE_LOCAL->get("chargeTotal") != 0 && $CORE_LOCAL->get("tenderTotal") != 0 ) {	
		//$_SESSION["kick"] = 0; 						
		return 0;
	} else {						
		//$_SESSION["kick"] = 1;	
		return 1;
	}							
}

/**
  Variant check for when to open cash drawer
  @return
   - 1 open drawer
   - 0 do not open

  Opens on cash transactions, credit card
  transactions > $25, and stamp sales.

  @todo This functionality needs to be more modular
  and customizable.
*/
function setDrawerKickLater()

{

// 	this more complex version can be modified to kick the drawer under whatever circumstances the FE Mgr sees fit
//	it currently kicks the drawer *only* for cash in & out
//	and credit card - andy
 

	$db = tDataConnect();

	$query = "select * from localtemptrans where (trans_subtype = 'CA' and total <> 0) or (trans_subtype = 'CC' AND (total < -25 or total > 0)) or upc='0000000001065'";

	$result = $db->query($query);
	$num_rows = $db->num_rows($result);
	$row = $db->fetch_array($result);

	if ($num_rows != 0) {
	 //$_SESSION["kick"] = 1;
	 return 1;
	} else {
	//$_SESSION["kick"] = 0;
	 return 0;
	}

}

?>
