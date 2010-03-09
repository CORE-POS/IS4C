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


if (!function_exists("printheaderb()")) include("drawscreen.php");
?>

<HTML>
<BODY onload='document.selectform.selectlist.focus();' >
<HEAD>
</HEAD>

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
printheaderb();
?>
<TR>
<TD height='300' width='640' align='center' colspan='2' valign='center'>
	<TABLE border='0' cellpadding='0' cellspacing='0'>
		<TR>
		<TD bgcolor='#004080' height='150' width='260' valign='center' align='center'>
		<FONT face='arial' color='white'><B>administrative tasks</B></FONT>
		<FORM name='selectform' method='post' action='admintasks.php'>
		<SELECT name='selectlist' onblur='document.selectform.selectlist.focus();' >
		<OPTION value=''>
		<OPTION value='SUSPEND'>1. Suspend Transaction
		<OPTION value='RESUME'>2. Resume Transaction
		<OPTION value='TR'>3. Tender Reports
		</SELECT>
		</FORM>
		<FONT face='arial' color='white' size='-1'>[c] to cancel</FONT>
		</TD>
		</TR>
	</TABLE>
</TD></TR></TABLE>
<?

$_SESSION["scan"] = "noScan";
printfooter();

?>
</BODY>
</HTML>
