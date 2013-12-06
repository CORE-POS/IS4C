<?php
/*******************************************************************************

    Copyright 2007 People's Food Co-op, Portland, Oregon.

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


/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	* 20Jan2013 Eric Lee In receipt_to_table() support per-coop header text
	*            hopefully pending use of CORE_LOCAL[receiptHeader*]
	*            via $FANNIE/install/lane_config/ini.php and table core_trans.lane_config

*/

require_once('mysql_connect.php');

// -----------------------------------------------------------------


// $db =$dbc->connect('localhost',$_SESSION["mUser"],$_SESSION["mPass"]);
// $dbc->select_db('is4c_op',$db);

function select_to_table($query,$args,$border,$bgcolor)
{
	global $dbc;
	$prep = $dbc->prepare_statement($query);
	$results = $dbc->exec_statement($prep,$args); 
	$number_cols = $dbc->num_fields($results);
	//display query
	//echo "<b>query: $query</b>";
	//layout table header
	echo "<font size = 2>";
	echo "<table border = $border bgcolor=$bgcolor cellspacing=0 cellpadding=3>\n";
	/*
	echo "<tr align left>\n";
	for($i=0; $i<$number_cols; $i++)
	{
		echo "<th><font size =2>" . $dbc->field_name($results,$i). "</font></th>\n";
	}
	echo "</tr>\n"; //end table header
	*/
	//layout table body
	while($row = $dbc->fetch_row($results))
	{
		echo "<tr align left>\n";
		for ($i=0;$i<$number_cols; $i++) {
			echo "<td width=";
			if(is_numeric($row[$i]) || !isset($row[$i])) { echo "89";} else { echo "170";} 
			echo " align=";
			if(is_numeric($row[$i]) || !isset($row[$i])) { echo "right";} else { echo "left";} 
			echo "><font size = 2>";
			if(!isset($row[$i])) {//test for null value
				echo "0.00";
			}elseif (is_numeric($row[$i]) && strstr($row[$i],".")){
				printf("%.2f",$row[$i]);
			}else{
				echo $row[$i];
			}
			echo "</font></td>\n";
		} echo "</tr>\n";
	} echo "</table>\n";
	echo "</font>";
}

/* -------------------------------end select_to_table-------------------*/ 

function select_to_table2($query,$args,$border,$bgcolor,$width="120",$spacing="0",$padding="0",$headers=array(),$nostart=False)
{
	global $dbc;
	$prep = $dbc->prepare_statement($query);
	$results = $dbc->exec_statement($prep,$args); 
	$number_cols = $dbc->num_fields($results);
	$num_rows = $dbc->num_rows($results);
	$backgrounds = array('#ffffff',$bgcolor);
	$b = 0;	
	//display query
	//echo "<b>query: $query</b>";
	//layout table header
	echo "<font size = 2>";
	if (!$nostart){
	    if($num_rows !=0){
	       echo "<table border = $border cellpadding=$padding cellspacing=$spacing>\n";
	    }else{
	       echo "<table border=0 bgcolor=$bgcolor>\n";
	    }
	}
	echo "<tr align left>\n";
    if($num_rows == 0){
       echo "There are no results to report";
    }else{

	if (count($headers) > 0){
		echo "<tr>\n";
		foreach ($headers as $h)
			echo "<th width=$width bgcolor=$backgrounds[$b]><font size=2>".$h."</font></th>\n";
		echo "</tr>";
		$b = 1;
	}
    while($row = $dbc->fetch_array($results))
	{
		echo "<tr align left>\n";
		for ($i=0;$i<$number_cols; $i++)
		{
		echo "<td width = $width bgcolor=$backgrounds[$b]><font size = 2>";
			if(!isset($row[$i])) //test for null value
			{
				echo "NULL";
			}else{
				echo $row[$i];
			}
			echo "</font></td>\n";
		} echo "</tr>\n";
		$b = ($b+1)%2;
	} } echo "</table>\n";
	echo "</font>";
}

function receipt_to_table($query,$args,$query2,$args2,$border,$bgcolor)
{
	global $dbc, $FANNIE_COOP_ID;
	$prep = $dbc->prepare_statement($query);
	$results = $dbc->exec_statement($prep,$args);
	$number_cols = $dbc->num_fields($results);
	$rows = array();
	while($row = $dbc->fetch_row($results))
		$rows[] = $row;
	$row2 = $rows[0];
	$emp_no = $row2['emp_no'];	
	$trans_num = $row2['emp_no']."-".$row2['register_no']."-".$row2['trans_no'];

	/* 20Jan13 EL The way I would like to do this.
	 * Or perhaps get from core_trans.lane_config
	if ( $CORE_LOCAL->get("receiptHeaderCount") > 0 ) {
		$receiptHeader = "";
		$c = $CORE_LOCAL->get("receiptHeaderCount");
		for ( $i=1; $i <= $c; $i++ ) {
			$h = "receiptHeader$i";
			$receiptHeader .= ("<tr><td align=center colspan=4>" . $CORE_LOCAL->get("$h") . "</td></tr>\n");
		}
	}
	*/

	$receiptHeader = "";

	if ( isset($FANNIE_COOP_ID) ) {
		switch ($FANNIE_COOP_ID) {

		case "WEFC_Toronto":
			$receiptHeader .= ("<tr><td align=center colspan=4>" . "W E S T &nbsp; E N D &nbsp; F O O D &nbsp; C O - O P" . "</td></tr>\n");
			$receiptHeader .= ("<tr><td align=center colspan=4>" . "416-533-6363" . "</td></tr>\n");
			$receiptHeader .= ("<tr><td align=center colspan=4>" . "Local food for local tastes" . "</td></tr>\n");
			break;

		case "WFC_Duluth":
			$receiptHeader .= ("<tr><td align=center colspan=4>" . "W H O L E &nbsp; F O O D S &nbsp; C O - O P" . "</td></tr>\n");
			$receiptHeader .= ("<tr><td align=center colspan=4>" . "218-728-0884" . "</td></tr>\n");
			$receiptHeader .= ("<tr><td align=center colspan=4>" . "MEMBER OWNED SINCE 1970" . "</td></tr>\n");
			break;

		default:
			$receiptHeader .= ("<tr><td align=center colspan=4>" . "FANNIE_COOP_ID >{$FANNIE_COOP_ID}<" . "</td></tr>\n");
		}
	}

	echo "<table border = $border bgcolor=$bgcolor>\n";
	echo "{$receiptHeader}\n";
	echo "<tr><td align=center colspan=4>{$row2['datetime']} &nbsp; &nbsp; $trans_num</td></tr>";
	echo "<tr><td align=center colspan=4>Cashier:&nbsp;$emp_no</td></tr>";
	echo "<tr><td colspan=4>&nbsp;</td></tr>";
	echo "<tr align left>\n";
	/*for($i=0; $i<5; $i++)
	{
		echo "<th>" . $dbc->field_name($results,$i). "</th>\n";
	}
	echo "</tr>\n"; *///end table header
	//layout table body
	foreach($rows as $row){
		echo "<tr><td align=left>";
		echo $row["description"]; 
		echo "</td>";
		echo "<td align=right>";
		echo $row["comment"];
		echo "</td><td align=right>";
		echo $row["total"];
		echo "</td><td align=right>";
		echo $row["Status"];
		echo "</td></tr>";	
	} 
	
	echo "<tr><td colspan=4>&nbsp;</td></tr>";
	echo "<tr><td colspan=4 align=center>--------------------------------------------------------</td></tr>";
	echo "<tr><td colspan=4 align=center>Reprinted Transaction</td></tr>";
	echo "<tr><td colspan=4 align=center>--------------------------------------------------------</td></tr>";
	echo "<tr><td colspan=4 align=center>Member #: {$row2['memberID']}</td</tr>";
	echo "</table>\n";

}

/* pads upc with zeroes to make $upc into IT CORE compliant upc*/

function str_pad_upc($upc)
{
    if (!class_exists('BarcodeLib')) {
        include(dirname(__FILE__).'/../classlib2.0/lib/BarcodeLib.php');
    }
    return BarcodeLib::padUPC($upc);
}

function test_upc($upc){
   if(is_numeric($upc)){
      $upc=str_pad_upc($upc);
   }else{
      echo "not a number";
   }
}

/* create an array from the results of a POSTed form */

function get_post_data($int){
    foreach ($_POST AS $key => $value) {
    $$key = $value;
    if($int == 1){
        echo $key .": " .  $$key . "<br>";
    }
    }
}

/* create an array from the results of GETed information */

function get_get_data($int){
    foreach ($_GET AS $key => $value) {
    $$key = $value;
    if($int == 1){
        echo $key .": " .  $$key . "<br>";
    }
    }
}

/* rounding function to create 'non-stupid' pricing */



?>
