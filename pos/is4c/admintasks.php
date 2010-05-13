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

if (!function_exists("getsubtotals")) include("connect.php");
if (!function_exists("msgscreen")) include("maindisplay.php");
if (!function_exists("checksuspended")) include("special.php");
if (!function_exists("tenderReport")) include("tenderReport.php");

if (isset($_POST["selectlist"])) {
    $admin_task = strtoupper(trim($_POST["selectlist"]));
}
else {
    $admin_task = "";
}

if ($admin_task == "SUSPEND") {
    getsubtotals();
    if ($_SESSION["LastID"] == 0) {
        msgscreen("no transaction in progress");
    }
    else {
        suspendorder();
    }
}
elseif ($admin_task == "RESUME") {
    getsubtotals();
    if ($_SESSION["LastID"] != 0) {
        msgscreen("transaction in progress");
    }
    elseif (checksuspended() == 0) {


        msgscreen("no suspended transaction");
    }
    else {
        echo "<SCRIPT type=\"text/javascript\">\n"
            ."window.location='/suspendedlist.php'"
            ."</SCRIPT>";
    }
}
elseif ($admin_task == "TR") {
    getsubtotals();
    if ($_SESSION["LastID"] != 0) {
            msgscreen("transaction in progress");
    }
    elseif ($_SESSION["standalone"] != 0) {?>
        <script type="text/javascript">
            alert('Unable to contact server.  Please make sure both the lane and server are connected to the network and the server is powered on.  You can check connectivity to the server by looking for the gren connection icon next to the date and time field.');
        </script>
    <?php
        gohome();
    }
    else {
        tenderReport();
    }
}

elseif ($admin_task == "" || !$admin_task || strlen($admin_task) < 1) {
    $_SESSION["msgrepeat"] = 0;
    gohome();
}

