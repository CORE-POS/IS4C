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

include('../src/mysql_connect.php');

if (isset($_REQUEST['submit2'])){
	$upc = str_pad($_REQUEST['upc'],13,'0',STR_PAD_LEFT);

	$delQ = "DELETE FROM upcLike WHERE upc='$upc'";
	$dbc->query($delQ);

	if (isset($_REQUEST['currentLC']) && $_REQUEST['currentLC'] != 0){
		$insQ = "INSERT INTO upcLike (likeCode,upc) VALUES
			({$_REQUEST['currentLC']},'$upc')";
		$dbc->query($insQ);
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

$upc = str_pad($_REQUEST['upc'],13,'0',STR_PAD_LEFT);

$descQ = "SELECT description FROM products WHERE upc='$upc'";
$descR = $dbc->query($descQ);
$desc = array_pop($dbc->fetch_row($descR));

$lcQ = "SELECT likeCode FROM upcLike WHERE upc='$upc'";
$lcR = $dbc->query($lcQ);
$lc = $dbc->num_rows($lcR)>0?array_pop($dbc->fetch_row($lcR)):0;

$lclistQ = "SELECT likeCode,likeCodeDesc FROM likeCodes ORDER BY likeCode";
$lclistR = $dbc->query($lclistQ);
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
$query = "SELECT u.upc,p.description FROM upcLike AS u
		INNER JOIN products AS p ON p.upc=u.upc
		WHERE u.likeCode=$lc
		ORDER BY u.upc";	
$result = $dbc->query($query);
while($row = $dbc->fetch_row($result)){
	printf("<a href=\"handheld.php?submit=Submit&upc=%s\">%s</a> %s<br />",
		$row[0],$row[0],$row[1]);
}


?>

</body>
</html>
