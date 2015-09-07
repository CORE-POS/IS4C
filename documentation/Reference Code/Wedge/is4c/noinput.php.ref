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
    <body>
        <table border='0' cellpadding='0' cellspacing='0'>
            <tr>
                <td width='200'>
                </td>
                <td align='right' valign='top' width='440'>
                    <?php
                        $time = strftime("%m/%d/%y  %I:%M %p", time());

                        if ($_SESSION["training"] == 1) {
                            echo "<font size='-1' face='arial' color='#004080'>training</font>"
                                . "<img src='graphics/BLUEDOT.GIF' alt='Blue dot' />&nbsp;&nbsp:&nbsp;";
                        }
                        elseif ($_SESSION["standalone"] == 0) {
                            echo "<img src='graphics/GREENDOT.GIF' alt='Green dot' />&nbsp;&nbsp;&nbsp;";
                        }
                        else {
                            echo "<font size='-1' face='arial' color='#800000'>stand alone</font>"
                                . "<img src='graphics/REDDOT.GIF' alt='Red dot' />&nbsp;&nbsp;&nbsp;";
                        }
                    ?>
                    <font face='arial' size='+1'><b><?=$time;?></b></font>
                </td>
            </tr>
        </table>
    </body></html>

