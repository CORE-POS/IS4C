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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/tr/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title></title>
    </head>
    <body onLoad='document.form.reginput.focus();'>
        <table border='0' cellpadding='0' cellspacing='0'>
            <tr>
                <td height='40' width='100' valign='center' bgcolor='#81366c' align='center'>
                    <font face='arial' size='-1' color='white'><b>I S 4 C</b></font>
                </td>
                <td height='40' width='540' valign='bottom' align='right'>
                    <font face='arial' size='-2'>
                        &nbsp; I S 4 C &nbsp; D E V E L O P M E N T &nbsp; V E R S I O N &nbsp; 2.0
                    </font>
                </td>
            </tr>
            <tr>
                <td height='0' width='640' colspan='2' bgcolor='black'></td>
            </tr>
            <tr>
                <td height='20' width='100' align='center' bgcolor='#FFFFFF'>
                    <font face='arial' size='-1' color='black'><b>W E L C O M E</b></font>
                </td>
                <td></td>
            </tr>
            <tr>
                <td height='265' width='640' align='center' valign='bottom' colspan='2' valign='center'>
                    <table border='0' cellpadding='0' cellspacing='0'>
                        <tr></tr>
                        <tr>
                            <td bgcolor='' height='80' width='260' valign='top' align='center'>
                                <center>
                                    <br />
                                    <font face='arial' color='red'>
                                        <form name='form' method='post' autocomplete='off' action='lib/authenticate.php'>
                                            <input Type='password' name='reginput' size='20' onBlur='document.form.reginput.focus();' />
                                        </form>
                                        <h3>PASSWORD INVALID</h3>
                                    </font>
                                </center>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td width='640' colspan='2' align='right'>
                    <font size='-2' face='arial'>E X I T</font>
                        <?
                            if ($_SESSION["laneno"] >= 9) {
                                echo "<a href='#' onclick='javascript:window.top.close(); return false;' ";
                            }
                            else {
                                echo "<a href='bye.html' ";
                            }
                        ?>
                        onMouseOver=document.exit.src='/graphics/switchred2.gif' onMouseOut=document.exit.src='/graphics/switchblue2.gif'>
                        <img name='exit' border='0' src='/graphics/switchblue2.gif' alt='Blue switch' /></a>
                    </font>
                </td>
            </tr>
        </table>
        <form name='hidden'>
            <input Type='hidden' name='alert' value='noScan' />
        </form>
    </body>
</html>
