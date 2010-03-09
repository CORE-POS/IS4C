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
<BODY onLoad='document.forms[0].elements[0].focus();'>
<?

$_SESSION["promptreturn"] = "";
promptbox("enter 4-digit number");
$_SESSION["promptreturn"] = "Hello";
printfooter();

function promptbox($msg) {
	printheaderb();
?>
<TR><TD height='295' width='640' align='center' valign='center'>
<TABLE border='0' cellpadding='0' cellspacing='0'>
<TR><TD colspan='5' bgcolor='#004080' height='30' width='260' valign='center'>&nbsp;&nbsp;&nbsp;&nbsp;<FONT size='-1' face='arial' color='white'><B>wedge co-op - request</B></FONT></TD></TR>
<TR>
<TD width='1' height='118' bgcolor='black'></TD>
<TD bgcolor='white' height='118' width='68' valign='top' align='left'>
<IMG src='graphics/prompt.gif'>
</TD>
<TD bgcolor='white' height='118' width='190' valign='center' align='left'>
<BR>
<FORM name='form' method='post' action='promptreturn.php'>
<INPUT name='reginput' Type='text' size='20'></FORM>
<FONT face='arial' color='black'>
<? echo $msg; ?>
</FONT></CENTER></TD>
<TD width='10' bgcolor='white' height='118'></TD>
<TD width='1' height='118' bgcolor='black'></TD></TR>
<TR><TD colspan='5' bgcolor='black' height='1' width='260'></TD></TR>
</TABLE>
</TD></TR>
<?

$_SESSION["strRemembered"] = $_SESSION["strEntered"];
$_SESSION["beep"] = "errorBeep"
$_SESSION["msgrepeat"] = 1;
$_SESSION["away"] = 1;

?>
