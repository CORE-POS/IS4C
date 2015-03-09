<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Community Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
$dbc = FannieDB::get($FANNIE_OP_DB);

if (isset($_REQUEST['submit2'])){
    $upc = BarcodeLib::padUPC(FormLib::get('upc'));

    $delQ = $dbc->prepare_statement("DELETE FROM upcLike WHERE upc=?");
    $dbc->exec_statement($delQ,array($upc));

    if (isset($_REQUEST['currentLC']) && $_REQUEST['currentLC'] != 0){
        $insQ = $dbc->prepare_statement("INSERT INTO upcLike (likeCode,upc) VALUES (?,?)");
        $dbc->exec_statement($insQ,array($_REQUEST['currentLC'],$upc));
    }
    
    header("Location: handheld.php?submit=Submit&upc=".$upc);
    return;
}

?>
<html>
<head><title>Change Likecode</title>
<style>
a {
    color:blue;
}
</style>
</head>
<body>
<?php
if (!isset($_REQUEST['upc'])){
    echo "<i>Error: No item</i>";
    return;
}

$upc = BarcodeLib::padUPC(FormLib::get('upc'));

$descQ = $dbc->prepare_statement("SELECT description FROM products WHERE upc=?");
$descR = $dbc->exec_statement($descQ,array($upc));
$desc = array_pop($dbc->fetch_row($descR));

$lcQ = $dbc->prepare_statement("SELECT likeCode FROM upcLike WHERE upc=?");
$lcR = $dbc->exec_statement($lcQ,array($upc));
$lc = $dbc->num_rows($lcR)>0?array_pop($dbc->fetch_row($lcR)):0;

$lclistQ = "SELECT likeCode,likeCodeDesc FROM likeCodes ORDER BY likeCode";
$lclistR = $dbc->exec_statement($lclistQ);
$lcs = array();
$lcs[0] = 'None';
while($lclistW = $dbc->fetch_row($lclistR)){
    $lcs[$lclistW[0]] = $lclistW[1];
}

printf("<b>Likecode settings for</b>: <span style=\"color:red\">%s
        </span> %s",$upc,$desc);
echo "<hr />";
echo "<form action=hhLike.php method=get>";
printf("<input type=hidden name=upc value=\"%s\" />",$upc);
echo "<select name=currentLC>";
foreach($lcs as $k=>$v){
    printf("<option %s value=%d>%d %s</option>",
        ($lc == $k ? 'selected' : ''),
        $k,$k,$v);
}
echo "</select><br /><br />";

echo "<input type=submit value=\"Update Likecode\"
    name=submit2 style=\"width:350px;height:40px;font-size:110%;\" />";

echo "<br />";

echo "<input type=submit value=\"Back\"
    name=back style=\"width:350px;height:40px;font-size:110%;\" 
    onclick=\"top.location='handheld.php?submit=Submit&upc=$upc';return false;\"
    />";

echo "<hr />";
echo "<b>Items in the likecode $lc</b><br />";
$query = $dbc->prepare_statement("SELECT u.upc,p.description FROM upcLike AS u
        INNER JOIN products AS p ON p.upc=u.upc
        WHERE u.likeCode=?
        ORDER BY u.upc");
$result = $dbc->exec_statement($query,array($lc));
while($row = $dbc->fetch_row($result)){
    printf("<a href=\"handheld.php?submit=Submit&upc=%s\">%s</a> %s<br />",
        $row[0],$row[0],$row[1]);
}


?>

</body>
</html>
