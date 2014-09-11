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
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
   header("Content-Disposition: inline; filename=mailingList.csv");
   header("Content-Description: PHP3 Generated Data");
   header("Content-type: application/vnd.ms-excel; name='excel'");

include('../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);
include($FANNIE_ROOT.'src/ReportConvert/HtmlToArray.php');
include($FANNIE_ROOT.'src/ReportConvert/ArrayToXls.php');
include($FANNIE_ROOT.'src/ReportConvert/ArrayToCsv.php');

$query = $dbc->prepare_statement("SELECT CardNo, 
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
      ON c.CardNo=m.card_no
      LEFT JOIN memDates AS d
      ON c.CardNo=d.card_no
          WHERE 
          memType IN (1,3)
      AND c.Type='PC'
          AND (end_date > ".$dbc->now()." 
        or end_date = '' 
        or end_date is null
        or end_date='1900-01-01 00:00:00'
        or end_date='0000-00-00 00:00:00')
          AND ads_OK = 1
          AND personNum = 1
      AND LastName <> 'NEW MEMBER'
          order by m.card_no");

$result = $dbc->exec_statement($query);

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

//$xls = ArrayToXls($ret);
$xls = ArrayToCsv($ret);
echo $xls;
?>
