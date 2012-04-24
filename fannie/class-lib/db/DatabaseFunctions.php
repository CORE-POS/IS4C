<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

/**
  @class DatabaseFunctions
  Functions for common database tasks
*/
class DatabaseFunctions extends FannieModule {

	public $required = True;

	public $description = "
	Provides functions for common database tasks
	";

	function provides_functions(){
		return array(
		'op_connect',
		'trans_connect',
		'select_dtrans',
		'select_dlog'
		);
	}
}

/**
  Connect to transactional database
  @return
   - SQLManager object
   - False if an error occurs
*/
function trans_connect(){
	global $FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
		$FANNIE_SERVER_USER,$FANNIE_SERVER_PW;

	if (!load_class('SQLManager')) return False;

	return new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
		$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);
}

/**
  Connect to operational database
  @return
   - SQLManager object
   - False if an error occurs
*/
function op_connect(){
	global $FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
		$FANNIE_SERVER_USER,$FANNIE_SERVER_PW;

	if (!load_class('SQLManager')) return False;

	return new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
		$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);
}

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
*/
function select_dtrans($date, $end_date=""){
  global $FANNIE_TRANS_DB,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,$FANNIE_ARCHIVE_METHOD;
  $dbc = op_connect();

  $dbconn = ($FANNIE_SERVER_DBMS=='MSSQL')?'.dbo.':'.';

  $diffQ = "select ".$dbc->datediff($dbc->now(),"'$date'")." as daydiff";
  $diffR = $dbc->query($diffQ);
  $diffRow = $dbc->fetch_array($diffR);
  $daydiff = abs($diffRow['daydiff']);
  if ($FANNIE_ARCHIVE_METHOD == "partitions"){
    $dbc->close();
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
  if ($month == $date){ 
	$dbc->close();
	return "dtransactions";
  }
  if (strlen($year) == 2) $year = "20".$year;
  
  // no end date, so give the smallest chunk
  // with the given day
  if ($end_date == ''){ 
	$dbc->close();
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
    $dbc->close();
    return "dtransactions";
  }
  if (strlen($endyear) < 4){
    $endyear = "20".$endyear;
  }
  $diffQ = "select ".$dbc->datediff($dbc->now(),"'$end_date'")." as daydiff";
  $diffR = $dbc->query($diffQ);
  $diffRow = $dbc->fetch_array($diffR);
  $enddiff = abs($diffRow['daydiff']);

  // if one of the available snapshots contains
  // both dates, return that
  if ($enddiff < 1 && $daydiff < 1){
	$dbc->close();
	return $FANNIE_TRANS_DB.$dbconn."dtransactions";
  }
  elseif ($endmonth == $month && $endyear == $year){
	$dbc->close();
	return $FANNIE_ARCHIVE_DB.$dbconn."transArchive".$year.(str_pad($month,2,'0',STR_PAD_LEFT));
  }
  elseif ($enddiff < 91 && $daydiff < 91){
	$dbc->close();
	return $FANNIE_TRANS_DB.$dbconn."transarchive";
  }

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
    
  $dbc->close();
  return $data;
}

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
*/
function select_dlog($date, $end_date="",$unions=True){
  global $FANNIE_TRANS_DB,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,$FANNIE_ARCHIVE_METHOD;
  $dbc = op_connect();

  $dbconn = ($FANNIE_SERVER_DBMS=='MSSQL')?'.dbo.':'.';

  $diffQ = "select ".$dbc->datediff($dbc->now(),"'$date'")." as daydiff";
  $diffR = $dbc->query($diffQ);
  $diffRow = $dbc->fetch_array($diffR);
  $daydiff = abs($diffRow['daydiff']);
  if ($FANNIE_ARCHIVE_METHOD == "partitions"){
    $dbc->close();
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
  if ($month == 0 && $year == 0){ 
	$dbc->close();
	return "dlog";
  }
  if (strlen($year) == 2) $year = "20".$year;
  
  // no end date, so give the smallest chunk
  // with the given day
  if ($end_date == ''){ 
	$dbc->close();
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
    $dbc->close();
    return "dlog";
  }
  if (strlen($endyear) < 4){
    $endyear = "20".$endyear;
  }
  $diffQ = "select ".$dbc->datediff($dbc->now(),"'$end_date'")." as daydiff";
  $diffR = $dbc->query($diffQ);
  $diffRow = $dbc->fetch_array($diffR);
  $enddiff = abs($diffRow['daydiff']);

  // if one of the available snapshots contains
  // both dates, return that
  if ($enddiff < 1 && $daydiff < 1){
	$dbc->close();
	return $FANNIE_TRANS_DB.$dbconn."dlog";
  }
  elseif ($enddiff < 15 && $daydiff < 15){
	$dbc->close();
	return $FANNIE_TRANS_DB.$dbconn."dlog_15";
  }
  elseif ($endmonth == $month && $endyear == $year){
	$dbc->close();
	return $FANNIE_ARCHIVE_DB.$dbconn."dlog".$year.(str_pad($month,2,'0',STR_PAD_LEFT));
  }
  elseif ($enddiff < 91 && $daydiff < 91){
	$dbc->close();
	return $FANNIE_TRANS_DB.$dbconn."dlog_90_view";
  }

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
    
  $dbc->close();
  if ($unions) return $data;
  else return $tables;
}

?>
