<?php
/*******************************************************************************
opyright 2001, 2004 Wedge Community Co-op

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
<BODY onLoad='document.form.reginput.focus();'>
<TABLE border='0' cellpadding='0' cellspacing='0'>
<TR><TD height='40' width='100' valign='center' bgcolor='#81366c' align='center'>
<FONT face='arial' size='-1' color='white'><B>I S 4 C</B></FONT>
</TD>
<TD height='40' width='540' valign='bottom' align='right'>
<FONT face='arial' size='-2'>
&nbsp; I S 4 C &nbsp; D E V E L O P M E N T &nbsp; V E R S I O N &nbsp; 2.0</B></FONT>
</TD>
</TR>

<TR><TD height='0' width='640' colspan='2' bgcolor='black'></TD></TR>
<TR>
<TD height='20' width='100' align='center' bgcolor='#FFFFFF'>
<FONT face='arial' size='-1' color='black'><B>W E L C O M E</B></FONT>
</TD>
<TD></TD>
</TR>
<TR>
<TD height='265' width='640' align='center' valign='bottom' colspan='2' valign='center'>
	<TABLE border='0' cellpadding='0' cellspacing='0'>
		<TR></TR>
		<TD bgcolor='' height='80' width='260' valign='top' align='center'>
			<CENTER>
			<BR><FONT face='arial' color='red'>
			<FORM name='form' method='post' autocomplete='off' action='authenticate.php'>
			<INPUT Type='password' name='reginput' size='20' onBlur='document.form.reginput.focus();'>
			</FORM>
			<h3>PASSWORD INVALID</h3>
			</FONT></CENTER>

		</TD>
		</TR>
	</TABLE>
</TD></TR>
<TR><TD width='640' colspan='2' align='right'>
<FONT size='-2' face='arial'>E X I T</FONT>

<?

if ($_SESSION["laneno"] >= 9) {
	echo "<A href='javascript:window.top.close();' ";
}
else {
	echo "<A href='bye.html' ";
}
?>

	onMouseOver=document.exit.src='/graphics/switchred2.gif'
	onMouseOut=document.exit.src='/graphics/switchblue2.gif'>

	<IMG name='exit' border='0' src='/graphics/switchblue2.gif'></A>
</TD></TR>
</TABLE>
<FORM name='hidden'>
<INPUT Type='hidden' name='alert' value='noScan'>
</FORM>

</BODY>
</HTML>
