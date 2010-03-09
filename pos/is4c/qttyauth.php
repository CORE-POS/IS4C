<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

$qtty = strtoupper(trim($_POST["input"]));

if (!$qtty || strlen($qtty) < 1) header("Location:/qttyinvalid.php");

elseif ($qtty == "CL") {
	$_SESSION["qttyvalid"] = 0;
	$_SESSION["quantity"] = 0;
	$_SESSION["msgrepeat"] = 0;
	header("Location:/pos2.php");
}
elseif (is_numeric($qtty)) {
	if ($qtty > 9999 || $qtty <= 0) header("Location:/qttyinvalid.php");
	else {
		$_SESSION["qttyvalid"] = 1;
		$_SESSION["msgrepeat"] = 1;
		$_SESSION["strRemembered"] = $qtty."*".$_SESSION["strEntered"];
		header("Location:/pos2.php");
	}
}
else header("Location:/qttyinvalid.php");

?>