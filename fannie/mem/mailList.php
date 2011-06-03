<?php 
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
   header("Content-Disposition: inline; filename=mailingList.xls");
   header("Content-Description: PHP3 Generated Data");
   header("Content-type: application/vnd.ms-excel; name='excel'");

include('../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/ReportConvert/HtmlToArray.php');
include($FANNIE_ROOT.'src/ReportConvert/ArrayToXls.php');

$query = "SELECT CardNo, 
          LastName, 
          FirstName, 
          street,
          city,
          state,
          zip,
          phone,
          memType,
          end_date
          FROM custdata AS c
	  LEFT JOIN meminfo AS m
	  ON c.cardno=m.card_no
	  LEFT JOIN memDates AS d
	  ON c.cardno=d.card_no
          WHERE 
          memType <>0
          AND (end_date > getdate() or end_date = '')
          AND ads_OK = 1
          AND PersonNum = 1
          order by m.card_no";

//select_to_table($query,0,';#ffffff');

$result = $dbc->query($query);

$ret = array();
while($row = $dbc->fetch_row($result)){
	$new = array(11);
	$new[0] = $row[0];
	$new[1] = $row[1];
	$new[2] = $row[2];

   if (strstr($row[3],"\n") === False){
	$new[3] = $row[3];
	$new[4] = "";
   }
   else {
	$pts = explode("\n",$row[3]);
	$new[3] = $pts[0];
	$new[4] = $pts[1];
   }
   for($i=5;$i<=10;$i++) $new[$i] = $row[$i-1];
   $ret[] = $new;
}

//$array = HtmlToArray($output);
$xls = ArrayToXls($ret);
echo $xls;
?>
