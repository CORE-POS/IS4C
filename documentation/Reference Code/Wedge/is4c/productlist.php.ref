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
 // session_start(); 
if (!function_exists("pDataConnect")) include("connect.php");
if (!function_exists("printheaderb")) include("drawscreen.php");
if (!function_exists("gohome")) include("maindisplay.php");

if (isset($_POST["search"])) {
    $entered = strtoupper(trim($_POST["search"]));
}
else {
    $entered = "";
}

if ($_SESSION["pvsearch"] && strlen($_SESSION["pvsearch"]) > 0) {
    $entered = $_SESSION["pvsearch"];
    $_SESSION["pvsearch"] = "";
}

$_SESSION["away"] = 1;

if (!$entered || strlen($entered) < 1) {
    gohome();
}
else {
    $db = pDataConnect();

    if (is_numeric($entered)) {
        if (substr($entered, 0, 1) == 0 && strlen($entered) == 7) {
            $p6 = substr($entered, -1);

            if ($p6 == 0) {
                $entered = substr($entered, 0, 3) . "00000" . substr($entered, 3, 3);
            }
            elseif ($p6 == 1) {
                $entered = substr($entered, 0, 3) . "10000" . substr($entered, 4, 3);
            }
            elseif ($p6 == 2) {
                $entered = substr($entered, 0, 3) . "20000" . substr($entered, 4, 3);
            }
            elseif ($p6 == 3) {
                $entered = substr($entered, 0, 4) . "00000" . substr($entered, 4, 2);
            }
            elseif ($p6 == 4) {
                $entered = substr($entered, 0, 5) . "00000" . substr($entered, 6, 1);
            }
            else {
                $entered = substr($entered, 0, 6) . "0000" . $p6;
            }
        }

        if (strlen($entered) == 13 && substr($entered, 0, 1) != 0) {
            $entered = "0" . substr($entered, 0, 12);
        }
        else {
            $entered = substr("0000000000000" . $entered, -13);
        }

        if (substr($entered, 0, 3) == "002") {
            $entered = substr($entered, 0, 8) . "00000";
        }
    }

    if (!is_numeric($entered)) {
        $query = "select upc, description, normal_price, special_price, advertised, scale from products where "
            . "substring(upc, 1, 7) = '0000000' AND inUse = 1 AND description like '%" . $entered . "%' "
            . "order by description";
        $boxSize = 15;
    }
    else {
        $query = "select upc, description, normal_price, special_price, advertised, scale from products where "
            . "upc = '" . $entered . "' AND inUse  = 1";
        $boxSize = 3;
    }

    $result = sql_query($query, $db);
    $num_rows = sql_num_rows($result);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
    if ($num_rows == 0) {
        echo "    <head>";
        echo "        <title></title>";
        echo "    </head>";
        echo "<body onLoad='document.forms[0].elements[0].focus();'>";
        printheaderb();
        productsearchbox("no match found<br />next search or enter upc");
    }
    else {
        echo "<head>\n"
        . "<script type=\"text/javascript\">\n"
        . "document.onkeydown = keyDown;\n"
        . "function keyDown(e) {\n"
        . "if ( !e ) { e = event; };\n"
        . "var ieKey=e.keyCode;\n"
        . "if (ieKey==13) { document.selectform.submit();}\n"
        . "else if (ieKey != 0 && ieKey != 38 && ieKey != 40) { window.top.location = 'pos.php';};\n"
        . "}\n"
        . "</script>\n"
        . "</head>\n"
        . "<body onLoad='document.selectform.selectlist.focus();'>\n";
        printheaderb();
        echo "<table>\n"
            . "<tr><td height='295' align='center' valign='center' width='560'>\n"
            . "<form name='selectform' method='post' action='productselected.php'>\n"
            . "<select name='selectlist' size=" . $boxSize . " onBlur='document.selectform.selectlist.focus();'>\n";

        $selected = "selected";
        for ($i = 0; $i < $num_rows; $i++) {
            $row = sql_fetch_array($result);
            if ($row["advertised"] != 0) {
                $price = $row["special_price"];
            }
            else {
                $price = $row["normal_price"];
            }
            if ($row["scale"] != 0) {
                $Scale = "S";
            }
            else {
                $Scale = " ";
            }

            if (!$price) {
                $price = "unKnown";
            }
            else {
                $price = truncate2($price);
            }

            echo "<option value='" . $row["upc"] . "' " . $selected . ">" . substr($row["upc"],7) . " -- " . $row["description"]
                . " ---- [" . $price . "] " . $Scale . "\n";

            $selected = "";
        }

        echo "</select>\n"
            . "</form>\n"
            . "</td>\n"
            . "<td height='295' width='80' valign='center'>\n"
            . "<font face='arial' color='#004080'><b>[c] to Cancel</b></FONT>"
            . "</td>\n"
            . "</tr></table>\n";
    }

    sql_close($db);
}
$_SESSION["scan"] = "noScan";
$_SESSION["beep"] = "noBeep";
printfooter();
?>

    </body>
</html>
