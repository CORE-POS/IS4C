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

/*****************************************************************************
 *
 * select_dlog 
 * returns the smallest dlog containing the given date
 *
*****************************************************************************/
if (!class_exists("SQLManager")) require_once("sql/SQLManager.php");
function select_dlog($date, $enddate=""){
  global $dbc,$FANNIE_TRANS_DB,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB;

  $dbconn = ($FANNIE_SERVER_DBMS=='MSSQL')?'.dbo.':'.';

  $diffQ = "select ".$dbc->datediff($dbc->now(),"'$date'")." as daydiff";
  $diffR = $dbc->query($diffQ);
  $diffRow = $dbc->fetch_array($diffR);
  $daydiff = abs($diffRow['daydiff']);

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
  if ($enddate == ''){ 
	if ($daydiff < 1){
		return $FANNIE_TRANS_DB.$dbconn."dlog";
	}
	if ($daydiff < 15){
		return $FANNIE_TRANS_DB.$dbconn."dlog_15";
	}
	return $FANNIE_ARCHIVE_DB.$dbconn."dlog".$year.(str_pad($month,2,'0',STR_PAD_LEFT));
  }
  
  // new - get enddate
  $endarray = explode('-',$enddate);
  $endyear = $endarray[0];
  $endmonth = 0;
  if ($endyear == $enddate){
    $endarray = explode("/",$enddate);
    $endmonth = $endarray[0];
    $endyear = $endarray[2];
  }
  else 
    $endmonth = $endarray[1];
  if ($endmonth == $enddate){
    return "dlog";
  }
  if (strlen($endyear) < 4){
    $endyear = "20".$endyear;
  }
  $diffQ = "select ".$dbc->datediff($dbc->now(),"'$enddate'")." as daydiff";
  $diffR = $dbc->query($diffQ);
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
  while ($startstamp <= $endstamp){
	$data .= $FANNIE_ARCHIVE_DB.$dbconn."dlog{$year}{$month} union all select * from ";
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

function select_dtrans($date, $enddate=""){
  global $dbc,$FANNIE_TRANS_DB,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB;

  $dbconn = ($FANNIE_SERVER_DBMS=='MSSQL')?'.dbo.':'.';

  $diffQ = "select ".$dbc->datediff($dbc->now(),"'$date'")." as daydiff";
  $diffR = $dbc->query($diffQ);
  $diffRow = $dbc->fetch_array($diffR);
  $daydiff = abs($diffRow['daydiff']);

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
  if ($enddate == ''){ 
	if ($daydiff < 1){
		return $FANNIE_TRANS_DB.$dbconn."dtransactions";
	}
	return $FANNIE_ARCHIVE_DB.$dbconn."transArchive".$year.(str_pad($month,2,'0',STR_PAD_LEFT));
  }
  
  // new - get enddate
  $endarray = explode('-',$enddate);
  $endmonth = $endarray[1];
  $endyear = $endarray[0];
  if ($endyear == $enddate){
    $endarray = explode("/",$enddate);
    $endmonth = $endarray[0];
    $endyear = $endarray[2];
  }
  if ($endmonth == $enddate){
    return "dtransactions";
  }
  if (strlen($endyear) < 4){
    $endyear = "20".$endyear;
  }
  $diffQ = "select ".$dbc->datediff($dbc->now(),"'$enddate'")." as daydiff";
  $diffR = $dbc->query($diffQ);
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
/*--------------------------end select_dlog-------------------------------*/
?>
