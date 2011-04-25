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

    if (!function_exists("get_config_auto")) {
        include_once("lib/conf.php");
    }

    if (!function_exists("pDataConnect") || !function_exists("tDataConnect")) {
    	include("connect.php");
    }
    if (!function_exists("initiate_session")) {
    	include("session.php");
    }
    if (!function_exists("setglobalflags")) {
    	include("loadconfig.php");
    }
    if (!function_exists("is_config_set")) {
        include("lib/conf.php");
    }

    if (!is_config_set()) {
    ?>
        <script type='text/javascript'>
            window.top.location = '/configure.php';
        </script>
    <?php
    }

    apply_configurations();
    initiate_session();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <link rel="stylesheet" type="text/css" href="css/is4c.css" />
        <script type='text/javascript'>
            function closeFrames() {
                window.top.close();
            }
        </script>
    </head>

    <body onLoad='document.form.reginput.focus();'>
        <table id='login'>
            <tr>
                <td id='is4c_header'>
                    I S 4 C
                </td>
                <td id='full_header'>
                    I N T E G R A T E D &nbsp; S Y S T E M S &nbsp; F O R &nbsp; C O O P S &nbsp; V 2 . 2
                </td>
            </tr>
            <tr>
                <td id='line' colspan='2'></td>
            </tr>
            <tr>
                <td id='welcome'>
                    W E L C O M E
                </td>
                <td></td>
            </tr>
            <tr>
                <td id='content_data' colspan='2'>
                    <table id='content_table'>
                        <tr>
                            <td>
                                <span style='font-weight: bold;'>log in</span>
                                <form name='form' method='post' autocomplete='off' action='lib/authenticate.php'>
                                    <input type='password' name='reginput' size='20' tabindex='0' onblur='document.form.reginput.focus();' />
                                </form>
                                <br />
                                <?php
                                    if (isset($_SESSION["auth_fail"])) {?>
                                        <h3>PASSWORD INVALID</h3>
                                        <?php
                                        unset($_SESSION["auth_fail"]);
                                    }
                                ?>
                                Please enter your password
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td width='640' colspan='2' align='right' valign='top'>
                    <font id='exit_text'>EXIT</font>
                    <div id='exit_switch' onclick="<?= $_SESSION["browserOnly"]?"window.top.close(); return false;":"location.href='bye.html'; return false;" ?>">
                    </div>
                </td>
            </tr>
        </table>
    </body>
</html>

