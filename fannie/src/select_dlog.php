<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

if (!class_exists("SQLManager")) require_once("sql/SQLManager.php");

/** 
  @file 
*/

/** 
 @brief Get an appropriate transaction view
 @param $date a single date
 @param $end_date a second date
 @return A database table name

 If called with a single argument, returns the smallest
 available transaction view containing that day.

 If called with two arguments, returns the smallest
 available transaction view containing that date range.

 Return value is fully qualified as db_name.table_name

 This function differs from select_dtrans() by using
 a dlog style view. The returned table will omit testing
 and canceled transaction records.

 @deprecated
 Use DTransactionsModel::selectDlog instead
*/
function select_dlog($date, $end_date="",$unions=True){
  global $dbc,$FANNIE_TRANS_DB,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,$FANNIE_ARCHIVE_METHOD;

  $dbconn = ($FANNIE_SERVER_DBMS=='MSSQL')?'.dbo.':'.';

  $diffQ = $dbc->prepare_statement("select ".$dbc->datediff($dbc->now(),'?')." as daydiff");
  $diffR = $dbc->exec_statement($diffQ,array($date));
  $diffRow = $dbc->fetch_array($diffR);
  $daydiff = abs($diffRow['daydiff']);
  if ($FANNIE_ARCHIVE_METHOD == "partitions"){
    return ($daydiff == 0) ? $FANNIE_TRANS_DB.$dbconn."dlog" : $FANNIE_ARCHIVE_DB.$dbconn."dlogBig";
  }

  // parse out starting month and year
  $month=0;
  $year=0;
  $array = explode("-",$date);
  if (is_array($array) && count($array) == 3){
	  $month = $array[1];
	  $year = $array[0];
  }
  else {
	$array = explode("/",$date);
	if (is_array($array) && count($array) == 3){
		$month = $array[0];
		$year = $array[2];
	}
  }
  if ($month == 0 && $year == 0) return "dlog";
  if (strlen($year) == 2) $year = "20".$year;
  
  // no end date, so give the smallest chunk
  // with the given day
  if ($end_date == ''){ 
	if ($daydiff < 1){
		return $FANNIE_TRANS_DB.$dbconn."dlog";
	}
	if ($daydiff < 15){
		return $FANNIE_TRANS_DB.$dbconn."dlog_15";
	}
	return $FANNIE_ARCHIVE_DB.$dbconn."dlog".$year.(str_pad($month,2,'0',STR_PAD_LEFT));
  }
  
  // new - get end_date
  $endarray = explode('-',$end_date);
  $endyear = $endarray[0];
  $endmonth = 0;
  if ($endyear == $end_date){
    $endarray = explode("/",$end_date);
    $endmonth = $endarray[0];
    $endyear = $endarray[2];
  }
  else 
    $endmonth = $endarray[1];
  if ($endmonth == $end_date){
    return "dlog";
  }
  if (strlen($endyear) < 4){
    $endyear = "20".$endyear;
  }

  $diffR = $dbc->exec_statement($diffQ,array($end_date));
  $diffRow = $dbc->fetch_array($diffR);
  $enddiff = abs($diffRow['daydiff']);

  // if one of the available snapshots contains
  // both dates, return that
  if ($enddiff < 1 && $daydiff < 1)
	return $FANNIE_TRANS_DB.$dbconn."dlog";
  elseif ($enddiff < 15 && $daydiff < 15)
	return $FANNIE_TRANS_DB.$dbconn."dlog_15";
  elseif ($endmonth == $month && $endyear == $year)
	return $FANNIE_ARCHIVE_DB.$dbconn."dlog".$year.(str_pad($month,2,'0',STR_PAD_LEFT));
  elseif ($enddiff < 91 && $daydiff < 91)
	return $FANNIE_TRANS_DB.$dbconn."dlog_90_view";

  // otherwise:
  // create a union of dlog archives containing the
  // specified span
  $endstamp = mktime(0,0,0,$endmonth,1,$endyear);
  $startstamp = mktime(0,0,0,$month,1,$year);
  $data = "(select * from ";
  $tables = array();
  while ($startstamp <= $endstamp){
	$data .= $FANNIE_ARCHIVE_DB.$dbconn."dlog{$year}{$month} union all select * from ";
	$tables[] = $FANNIE_ARCHIVE_DB.$dbconn."dlog{$year}{$month}";
	$month += 1;
	if ($month > 12){
		$year += 1;
		$month = 1;
	}
	$month = str_pad($month,2,'0',STR_PAD_LEFT);
	$startstamp = mktime(0,0,0,$month,1,$year);
  }
  preg_match("/(.*) union all select \* from $/",$data,$matches);
  $data = $matches[1].")";
    
  if ($unions) return $data;
  else return $tables;
}

/** @cond */
function select_dlog_array($date,$end_date=""){
	return select_dlog($date,$end_date,False);
}
/** @endcond */

/** @cond */
function fixup_dquery($query,$table){
	if (!is_array($table)){
		return str_replace("__TRANS__",$table,$query);
	}
	$order = "";
	$tmp = preg_split("/ORDER\s+BY/i",$query,NULL,PREG_SPLIT_NO_EMPTY);
	if (count($tmp)==2){
		$query = $tmp[0];
		$order = " ORDER BY ".$tmp[1];
	}
	$ret = "";
	for($i=0;$i<count($table);$i++){
		$ret .= str_replace("__TRANS__",$table[$i],$query);
		if($i<count($table)-1)
			$ret .= " UNION ALL ";
	}
	return $ret.$order;
}
/** @endcond */

/** 
 @brief Get an appropriate transaction table
 @param $date a single date
 @param $end_date a second date
 @return A database table name

 If called with a single argument, returns the smallest
 available transaction table containing that day.

 If called with two arguments, returns the smallest
 available transaction table containing that date range.

 Return value is fully qualified as db_name.table_name

 @deprecated
 Use DTransactionsModel::selectDtrans instead
*/
function select_dtrans($date, $end_date=""){
  global $dbc,$FANNIE_TRANS_DB,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,$FANNIE_ARCHIVE_METHOD;

  $dbconn = ($FANNIE_SERVER_DBMS=='MSSQL')?'.dbo.':'.';

  $diffQ = $dbc->prepare_statement("select ".$dbc->datediff($dbc->now(),'?')." as daydiff");
  $diffR = $dbc->exec_statement($diffQ,array($date));
  $diffRow = $dbc->fetch_array($diffR);
  $daydiff = abs($diffRow['daydiff']);
  if ($FANNIE_ARCHIVE_METHOD == "partitions"){
    return ($daydiff == 0) ? $FANNIE_TRANS_DB.$dbconn."dtransactions" : $FANNIE_ARCHIVE_DB.$dbconn."bigArchive";
  }

  // parse out starting month and year
  $array = explode("-",$date);
  $month = $array[1];
  $year = $array[0];
  if ($year == $date){
	$array = explode("/",$date);
	$month = $array[0];
	$year = $array[2];
  }
  if ($month == $date) return "dtransactions";
  if (strlen($year) == 2) $year = "20".$year;
  
  // no end date, so give the smallest chunk
  // with the given day
  if ($end_date == ''){ 
	if ($daydiff < 1){
		return $FANNIE_TRANS_DB.$dbconn."dtransactions";
	}
	return $FANNIE_ARCHIVE_DB.$dbconn."transArchive".$year.(str_pad($month,2,'0',STR_PAD_LEFT));
  }
  
  // new - get end_date
  $endarray = explode('-',$end_date);
  $endmonth = $endarray[1];
  $endyear = $endarray[0];
  if ($endyear == $end_date){
    $endarray = explode("/",$end_date);
    $endmonth = $endarray[0];
    $endyear = $endarray[2];
  }
  if ($endmonth == $end_date){
    return "dtransactions";
  }
  if (strlen($endyear) < 4){
    $endyear = "20".$endyear;
  }
  $diffR = $dbc->exec_statement($diffQ,array($end_date));
  $diffRow = $dbc->fetch_array($diffR);
  $enddiff = abs($diffRow['daydiff']);

  // if one of the available snapshots contains
  // both dates, return that
  if ($enddiff < 1 && $daydiff < 1)
	return $FANNIE_TRANS_DB.$dbconn."dtransactions";
  elseif ($endmonth == $month && $endyear == $year)
	return $FANNIE_ARCHIVE_DB.$dbconn."transArchive".$year.(str_pad($month,2,'0',STR_PAD_LEFT));
  elseif ($enddiff < 91 && $daydiff < 91)
	return $FANNIE_TRANS_DB.$dbconn."transarchive";

  // otherwise:
  // create a union of dlog archives containing the
  // specified span
  $endstamp = mktime(0,0,0,$endmonth,1,$endyear);
  $startstamp = mktime(0,0,0,$month,1,$year);
  $data = "(select * from ";
  while ($startstamp <= $endstamp){
	$data .= $FANNIE_ARCHIVE_DB.$dbconn."transArchive{$year}{$month} union all select * from ";
	$month += 1;
	if ($month > 12){
		$year += 1;
		$month = 1;
	}
	$month = str_pad($month,2,'0',STR_PAD_LEFT);
	$startstamp = mktime(0,0,0,$month,1,$year);
  }
  preg_match("/(.*) union all select \* from $/",$data,$matches);
  $data = $matches[1].")";
    
  return $data;
}

/** @cond */
// standard comparison function for query rows
function standard_compare($v1,$v2){
	global $STANDARD_COMPARE_ORDER,$STANDARD_COMPARE_DIRECTION;
	if (strstr($STANDARD_COMPARE_ORDER,".")){
		$STANDARD_COMPARE_ORDER = substr($STANDARD_COMPARE_ORDER,strpos($STANDARD_COMPARE_ORDER,".")+1);
	}
	$a = $v1[$STANDARD_COMPARE_ORDER];
	$b = $v2[$STANDARD_COMPARE_ORDER];
	if (is_numeric($a) && is_numeric($b)){
		if ($STANDARD_COMPARE_DIRECTION == "ASC")
			return ($a < $b) ? -1 : 1;
		else
			return ($a > $b) ? -1 : 1;
	}
	else{
		if ($STANDARD_COMPARE_DIRECTION == "ASC")
			return (strcmp($a,$b)<=0) ? -1 : 1;
		else
			return (strcmp($a,$b)>=0) ? -1 : 1;
	}
}
/** @endcond */
?>
