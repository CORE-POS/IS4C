<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op.

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

$endorseType = $CORE_LOCAL->get("endorseType");

if (strlen($endorseType) > 0) {
	$CORE_LOCAL->set("endorseType","");

	switch ($endorseType) {

		case "check":
			ReceiptLib::frank();
			break;

		case "giftcert":
			ReceiptLib::frankgiftcert();
			break;

		case "stock":
			ReceiptLib::frankstock();
			break;

		case "classreg":
			ReceiptLib::frankclassreg();
			break;

		default:
			break;
	}
}
echo "Done";
?>
