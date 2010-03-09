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
<BODY onload="document.form.reginput.focus();">
<TABLE border='0' cellpadding='0' cellspacing='0'>
	<TR>
		<TD width='200'><FORM name='form' method='post' autocomplete='off' action='<? echo $_SERVER["PHP_SELF"]; ?>'>
<?
		if ($_SESSION["inputMasked"] != 0) {
			$inputType = "password";
		} else {
			$inputType = "text";
		}
		echo "<INPUT name='reginput' Type=".$inputType." style='font-size:22px' value='' onBlur='document.form.reginput.focus();'>"
?>
			</FORM></TD>
		<TD align='right' valign='top' width='440'>
<?

if (isset($_POST["reginput"])) {
$input = strtoupper(trim($_POST["reginput"]));
} else {
$input = "";
}


$time = strftime("%m/%d/%y %I:%M %p", time());

$_SESSION["repeatable"] = 0;

if ($_SESSION["training"] == 1) {
	echo "<FONT size='-1' face='arial' color='#004080'>training </FONT>"
	     ."<IMG src='graphics/BLUEDOT.GIF'>&nbsp;&nbsp:&nbsp;";
}
elseif ($_SESSION["standalone"] == 0) {
	echo "<IMG src='graphics/GREENDOT.GIF'>&nbsp;&nbsp;&nbsp;";
}
else {
	echo "<FONT face='arial' color='#800000' size='-1'>stand alone  </FONT>"
	     ."<IMG src='graphics/REDDOT.GIF'>&nbsp;&nbsp;&nbsp;";
}

echo "<FONT face='arial' size='+1'><B>".$time."</B></FONT>\n";
echo "</TD></TR></TABLE>\n";

?>

<SCRIPT type="text/javascript">

function keyDown(e)
{
if ( !e ) {
	e = event;
};
var ieKey=e.keyCode;
var strKeyed
switch(ieKey) 
{
	case 37:
		{
			window.top.main_frame.document.form1.input.value = 'U';
			window.top.main_frame.document.form1.submit();
		};
		break;
	case 38:
		{
			window.top.main_frame.document.form1.input.value = 'U';
			window.top.main_frame.document.form1.submit();
		};
		break;
	case 39:
		{
			window.top.main_frame.document.form1.input.value = 'D';
			window.top.main_frame.document.form1.submit();
		};
		break;
	case 40:
		{
			window.top.main_frame.document.form1.input.value = 'D';
			window.top.main_frame.document.form1.submit();
		};
		break;
	case 115:
		{
<?php 
	if ($_SESSION["OS"] == "win32") {
		echo "window.top.main_frame.document.form1.input.value = 'LOCK';\n";
		echo "window.top.main_frame.document.form1.submit();\n";
	}
?>
		};
		break;
//	case 116:
//		{
//			window.top.main_frame.document.form1.input.value = 'MA';
//			window.top.main_frame.document.form1.submit();
//		};
//		break;
	case 117:
		{
			window.top.main_frame.document.form1.input.value = 'RI';
			window.top.main_frame.document.form1.submit();
		};
		break;

	case 121:
		{
			window.top.main_frame.document.form1.input.value = 'WAKEUP';
			window.top.main_frame.document.form1.submit();
		};
		break;
	default:
		break;
}
}

	document.onkeydown = keyDown;

<?


	if ( strlen($input) > 0 || $_SESSION["msgrepeat"] == 2) {
		echo "window.top.main_frame.document.form1.input.value = '".$input."';\n";
		echo "window.top.main_frame.document.form1.submit();\n";
	}

?>


</SCRIPT>
</BODY>
