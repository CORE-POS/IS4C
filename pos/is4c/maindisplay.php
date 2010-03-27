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

function gohome() {
    $_SESSION["scan"] = "scan";
    echo "<script type=\"text/javascript\">";
    echo "window.top.location = '/pos.php'";
    echo "</script>\n";
}

function maindisplay($location) {
    $_SESSION["display"] = $location;
    echo "<script  type=\"text/javascript\">";
    echo "window.top.location = '/display.php'";
    echo "</script>";
}

function msgscreen($msg) {
    $_SESSION["boxMsg"] = $msg;
    echo "<script  type=\"text/javascript\">";
    echo "window.top.location = '/msgscreen.php'";
    echo "</script>";
}

function inputBox() {
    echo "<script  type=\"text/javascript\">";
    echo "window.top.frames[0].location = '/input.php'";
    echo "</script>";
}

function noinputBox() {
    echo "<script type=\"text/javascript\">";
    echo "window.top.frames[0].location = '/noinput.php'";
    echo "</script>";
}

function returnHome() {
    $_SESSION["scan"] = "scan";
    echo "<script type=\"text/javascript\">";
    echo "window.top.frames[1].location = '/pos2.php';\n";
    echo "window.top.frames[0].location = '/input.php';\n";
    echo "</script>\n";
}

