<?php
/*******************************************************************************

    Copyright 2007 People's Food Co-op, Portland, Oregon.

    This file is part of Fannie.

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
$page_title = 'Fannie - Volunteer Hours Entry';
$header = 'Volunteer Hours Entry';
include('../src/header.html');

require_once('../src/mysql_connect.php');

if(isset($_POST['submit'])){
	foreach ($_POST AS $key => $value) {
		$$key = $value;
	}
}else{
      foreach ($_GET AS $key => $value) {
          $$key = $value;
      }
}

if(isset($_POST['submit'])){
//	print_r(array_combine($id,$hours));
	$comb_arr = array_combine($id,$hours);
	foreach ($comb_arr as $key => $value) {
		mysql_query("UPDATE custdata SET SSI = (SSI + ".$value.") WHERE id = ".$key);
	}
}

$query = "SELECT CardNo, LastName, FirstName, SSI, id FROM custdata WHERE staff IN(3,6) ORDER BY LastName";

$queryR = mysql_query($query);

echo "<form action=volunteers.php method=POST>";
echo "<table border=0 width=95% cellspacing=0 cellpadding=5 align=center>";
echo "<th>Card No<th>Last Name<th>First Name<th>Hours<th>ADD<th>&nbsp;";
$bg = '#eeeeee';
while($query = mysql_fetch_row($queryR)){
	$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
	echo "<tr bgcolor='$bg'>";
	echo "<td>".$query[0]."</td>";
	echo "<td>".$query[1]."</td>";
	echo "<td>".$query[2]."</td>";
	echo "<td align=right>".number_format($query[3],2)."</td>";
	echo "<td align=right><input size=4 name='hours[]' id='hours'></td>";
	echo "<td><input type=hidden name='id[]' value=".$query[4].">&nbsp;</td></tr>";
}
echo "<tr><td><input type=submit name=submit value=submit></td></tr>";
echo "</table></form>";


//
// PHP INPUT DEBUG SCRIPT  -- very helpful!
//
/*
function debug_p($var, $title) 
{
    print "<p>$title</p><pre>";
    print_r($var);
	mysql_error();
    print "</pre>";
}  

debug_p($_REQUEST, "all the data coming in");
*/
include('../src/footer.html');
?>