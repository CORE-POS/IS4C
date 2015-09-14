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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title></title>
    </head>
    <body onLoad='document.forms[0].elements[0].focus();'>
<?

$_SESSION["promptreturn"] = "";
promptbox("enter 4-digit number");
$_SESSION["promptreturn"] = "Hello";
printfooter();

function promptbox($msg) {
    printheaderb();
?>
    <table>
        <tr>
            <td height='295' width='640' align='center' valign='center'>
                <table border='0' cellpadding='0' cellspacing='0'>
                    <tr>
                        <td colspan='5' bgcolor='#004080' height='30' width='260' valign='center'>
                            &nbsp;&nbsp;&nbsp;&nbsp;
                            <font size='-1' face='arial' color='white'><b>wedge co-op - request</b></font>
                        </td>
                    </tr>
                    <tr>
                        <td width='1' height='118' bgcolor='black'></td>
                        <td bgcolor='white' height='118' width='68' valign='top' align='left'>
                            <img src='graphics/prompt.gif' alt='Prompt' />
                        </td>
                        <td bgcolor='white' height='118' width='190' valign='center' align='left'>
                            <br />
                            <form name='form' method='post' action='promptreturn.php'>
                                <input name='reginput' Type='text' size='20' />
                            </form>
                            <font face='arial' color='black'>
                                <?=$msg; ?>
                            </font>
                        </td>
                        <td width='10' bgcolor='white' height='118'></td>
                        <td width='1' height='118' bgcolor='black'></td>
                    </tr>
                    <tr>
                        <td colspan='5' bgcolor='black' height='1' width='260'></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
<?php
$_SESSION["strRemembered"] = $_SESSION["strEntered"];
$_SESSION["beep"] = "errorBeep"
$_SESSION["msgrepeat"] = 1;
$_SESSION["away"] = 1;

