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

$delayed = 0;

if (isset($_GET["reginput"])) {
   	$reginput = strtoupper(trim($_GET["reginput"]));
} else {
	$reginput = "";
}

if (substr($reginput, 0, 4) == "S144") {
	$reginput = "S11".substr($reginput, 4);
}

if (substr($reginput, 0, 3) == "S11") {
	if (!substr($reginput, 3) || !is_numeric(substr($reginput, 3))) {
		$weight = 0;
		$_SESSION["scale"] = 0;
		$_SESSION["weight"] = 0;
		$display_weight = "_ _ _ _";
	}
	else {
		$weight = number_format(substr($reginput, 3)/100, 2);
		$_SESSION["weight"] = $weight;
		$display_weight = $weight." lb";
		$_SESSION["scale"] = 1;
	}

	if ($_SESSION["SNR"] == 1 && $_SESSION["weight"] > 0 ) {
		$delayed = 1;
		$_SESSION["msgrepeat"] = 1;
		$_SESSION["strRemembered"] = $_SESSION["strEntered"];
	}
}
elseif (substr($reginput, 0, 4) == "S143") {
	$weight = 0;
	$display_weight = "0.00 lb";
	$_SESSION["weight"] = 0;
	$_SESSION["scale"] = 1;
}
elseif (substr($reginput, 0, 4) == "S141") {
	$weight = 0;
	$display_weight = "_ _ _ _";
	$_SESSION["scale"] = 0;
	$_SESSION["weight"] = 0;
}
elseif (substr($reginput, 0, 4) == "S145") {
	$display_weight = "err -0";
	$weight = 0;
	$_SESSION["scale"] = 0;
	$_SESSION["weight"] = 0;
}
elseif (substr($reginput, 0, 4) == "S142") {
	$display_weight = "error";
	$weight = 0;
	$_SESSION["scale"] = 0;
	$_SESSION["weight"] = 0;
}
elseif (!$reginput || strlen($reginput) == 0) {
	$display_weight = number_format($_SESSION["weight"], 2)." lb";
}
else {
	$display_weight = "? ? ? ?";
	$_SESSION["scale"] = 0;
	$_SESSION["weight"] = 0;
	$weight = 0;
}

echo $display_weight."::".$delayed;

?>
