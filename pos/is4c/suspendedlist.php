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
?>
<HTML>
<HEAD></HEAD>
<BODY onLoad='document.forms[0].elements[0].focus();'>

<SCRIPT type="text/javascript">
	document.onkeydown = keyDown;
	function keyDown(e) {
		if ( !e ) { e = event; };
		var ieKey=e.keyCode;
		if (ieKey==13) { document.selectform.submit();}
		else if (ieKey != 0 && ieKey != 38 && ieKey != 40) { window.top.location = 'pos.php';};
	}
</SCRIPT>

<?
if (!function_exists("tDataConnect")) include("connect.php");
if (!function_exists("printheaderb")) include("drawscreen.php");


$query_local = "select register_no, emp_no, trans_no, sum(total) as total from suspendedtoday "
	."group by register_no, emp_no, trans_no";

$query_remote = "select register_no, emp_no, trans_no, sum(total) as total from "
		    .trim($_SESSION["mServer"]).".".trim($_SESSION["mDatabase"]).".dbo.suspendedtoday "
		    ."group by register_no, emp_no, trans_no";

$query = "select * from suspendedlist";
 
$db_a = tDataConnect();
$m_conn = mDataConnect();

if ($_SESSION["standalone"] == 1) {
	if ($_SESSION["remoteDBMS"] == "mssql") $result = mssql_query($query_local, $db_a);
	else $result = mysql_query($query, $db_a);
}
else {
	if ($_SESSION["remoteDBMS"] == "mssql") $result = mssql_query($query_remote, $db_a);
	else $result = mysql_query($query, $m_conn);
}

$num_rows = sql_num_rows($result);

if ($num_rows > 0) {
	printheaderb();
	echo "<TABLE>"
		."<TR><TD height=295 width=400 align=center valign=center>\n"
		."<FORM name='selectform' method='post' action='resume.php'>\n"
		."<SELECT name='selectlist' size='10' onBlur='document.forms[0].elements[0].focus();'>\n";

	$selected = "selected";
	for ($i = 0; $i < $num_rows; $i++) {
		$row = sql_fetch_array($result);
		echo "<OPTION value='".$row["register_no"]."::".$row["emp_no"]."::".$row["trans_no"]."' ".$selected
			."> lane ".substr(100 + $row["register_no"], -2)." Cashier ".substr(100 + $row["emp_no"], -2)
			." #".$row["trans_no"]." -- $".$row["total"]."\n";
		$selected = "";
	}

	echo "</SELECT>\n</FORM>\n</TD>\n"
		."<TD width='240'>\n"
		."<FONT face='arial' size='+1' color='#004080'>use arrow keys to navigate<BR>[c] to cancel</FONT>\n"
		."</TD></TR>\n</TABLE>\n";
}
else {
	msgscreen("no suspended transaction");
}
sql_close($db_a);
$_SESSION["scan"] = "noScan";
printfooter();
?>

</BODY>
</HTML>
