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
// session_start(); 

if (!function_exists("setMember")) include("prehkeys.php");
if (!function_exists("gohome")) include("maindisplay.php");


$_SESSION["repeat"] = 0;
if (isset($_POST["selectlist"])) {
	$member_number = trim($_POST["selectlist"]);
} else {
	$member_number = "";
}
$mem_info = explode("::", $member_number);



if ($mem_info[0] && strlen($mem_info[0]) >= 1) {

//	setMember($mem_info[0], $mem_info[1]);
	setMember($mem_info[2]);
}

gohome();


?>
