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
    <body onload="document.form.reginput.focus();">
        <table border='0' cellpadding='0' cellspacing='0'>
            <tr>
                <td width='200'>
                    <form name='form' method='post' autocomplete='off' action='<?=$_SERVER["PHP_SELF"];?>'>
                    <?php
                        if ($_SESSION["inputMasked"] != 0) {
                            $inputType = "password";
                        }
                        else {
                            $inputType = "text";
                        }
                        echo "<input name='reginput' type=" . $inputType . " style='font-size:22px' value='' onBlur='document.form.reginput.focus();'>"
                    ?>
                    </form>
                </td>
                <td align='right' valign='top' width='440'>
                    <?php
                        if (isset($_POST["reginput"])) {
                            $input = strtoupper(trim($_POST["reginput"]));
                        }
                        else {
                            $input = "";
                        }

                        $time = strftime("%m/%d/%y %I:%M %p", time());

                        $_SESSION["repeatable"] = 0;

                        if ($_SESSION["training"] == 1) {?>
                            <font size='-1' face='arial' color='#004080'>training</font>
                            <img src='graphics/BLUEDOT.GIF' alt='Training Mode' />&nbsp;&nbsp;&nbsp;
                            <?php
                        }
                        elseif ($_SESSION["standalone"] == 0) {?>
                            <img src='graphics/GREENDOT.GIF' alt='Connected' />&nbsp;&nbsp;&nbsp;
                            <?php
                        }
                        else {?>
                            <font face='arial' color='#800000' size='-1'>stand alone</font>
                            <img src='graphics/REDDOT.GIF' alt='Not Connected' />&nbsp;&nbsp;&nbsp;
                            <?php
                        }
                    ?>
                <font face='arial' size='+1'><b><?=$time?></b></font>
                </td>
            </tr>
        </table>
        <script type="text/javascript">
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
                            <?=($_SESSION["OS"] == "win32")?"window.top.main_frame.document.form1.input.value = 'LOCK'; window.top.main_frame.document.form1.submit();":''?>
                        };
                        break;
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

            <?php
                if ( strlen($input) > 0 || $_SESSION["msgrepeat"] == 2) {
                    echo "window.top.main_frame.document.form1.input.value = '" . $input . "';\n";
                    echo "window.top.main_frame.document.form1.submit();\n";
                }
            ?>
        </script>
    </body>
</html>

