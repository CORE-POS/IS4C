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
<BODY onLoad='document.selectform.selectlist.focus();'>
<HEAD></HEAD>


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
if (!function_exists("printheaderb")) include("drawscreen.php");
if (!function_exists("tDataConnect")) include("connect.php");


printheaderb();

if ($_SESSION["DBMS"] == "mssql") {
$query = "select register_no, emp_no, trans_no, sum((case when trans_type = 'T' then -1 * total else 0 end)) as total "
	."from localtranstoday where register_no = ".$_SESSION["laneno"]." and emp_no = ".$_SESSION["CashierNo"]
	." group by register_no, emp_no, trans_no order by trans_no desc";
} else {

$query = "select * from rp_list where register_no = ".$_SESSION["laneno"].
" and emp_no = ".$_SESSION["CashierNo"]." order by trans_no desc";

}

$db = tDataConnect();
$result = sql_query($query, $db);
$num_rows = sql_num_rows($result);

?>

<TABLE>

<TR><TD height='295' width='400' align='center' valign='center'>
<FORM name='selectform' method='post' action='reprint.php'>
<SELECT name='selectlist' size='10' onBlur='document.selectform.selectlist.focus()' >

<?
$selected = "selected";

for ($i = 0; $i < $num_rows; $i++) {
	$row = sql_fetch_array($result);
	echo "<OPTION value='".$row["register_no"]."::".$row["emp_no"]."::".$row["trans_no"]."'";

	echo $selected;

	echo ">".$row["time"]." - ".$row["trans_no"]." -- $".number_format(($row["total"] / 2),2);
	$selected = "";
}

?>

</SELECT>
</FORM>
</TD>
<TD width='240'>
<FONT face='arial' size='+1' color='#004080'>use arrow keys to navigate<BR>[enter] to reprint receipt<BR>[c] to cancel</FONT>
</TD>
</TR>
</TABLE>

<?

sql_close($db);
$_SESSION["scan"] = "noScan";
printfooter();

?>

</BODY>
</HTML>
