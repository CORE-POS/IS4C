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

if (!function_exists("addactivity")) include("additem.php");
if (!function_exists("printheaderb")) include("drawscreen.php");
?>
<HEAD></HEAD>
<BODY>

<FORM name='form1' method='post' autocomplete='off' action='authenticate3.php'>
<INPUT Type='hidden' name='input' value = '99999' size='20' tabindex='0'>
</FORM>

<?

addactivity(3);

printheaderb();

?>

<TR>
<TD height='300' width='640' align='center' valign='center'>
	<TABLE border='0' cellspacing='0' cellpadding='0'>
		<TR>
		<TD bgcolor='#004080' height='150' width='260' valign='center' align='center'>
			<CENTER>


			<IMG src='graphics/bluekey4.gif'>


			<P><FONT face='arial' color='white'>

			please enter password</FONT>

			<B></FONT>

			</CENTER>
		</TD>
		</TR>
	</TABLE>
<TD><TR>

<?
$_SESSION["scan"] = "noScan";
printfooter();

 load();
 getsubtotals();

function load() {
	$query_member = "select * from custdata where CardNo = '205203'";
	$query_product = "select * from products where upc = '0000000000090'";
	$query_localtemptrans = "select * from localtemptrans";

	$bdat = pDataConnect();
	$result = sql_query($query_product, $bdat);
	$result_2 = sql_query($query_member, $bdat);
	sql_close($bdat);

	$trans = tDataConnect();
	$result_3 = sql_query($query_localtemptrans, $trans);
	sql_close($trans);
}

?>
