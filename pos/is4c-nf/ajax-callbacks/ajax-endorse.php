<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op.

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

include_once($IS4C_PATH."ini.php");
include_once($IS4C_PATH."lib/session.php");
include_once($IS4C_PATH."lib/printLib.php");
include_once($IS4C_PATH."lib/printReceipt.php");
include_once($IS4C_PATH."lib/connect.php");
include_once($IS4C_PATH."lib/prehkeys.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

$endorseType = $IS4C_LOCAL->get("endorseType");

if (strlen($endorseType) > 0) {
	$IS4C_LOCAL->set("endorseType","");

	switch ($endorseType) {

		case "check":
			frank();
			break;

		case "giftcert":
			frankgiftcert();
			break;

		case "stock":
			frankstock();
			break;

		case "classreg":
			frankclassreg();
			break;

		default:
			break;
	}
}
echo "Done";
?>
