<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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

/* HELP

   weekly.spins.php

   The idea here is to send weekly sales data
   to SPINS. This script isn't yet in active
   use and may contain bugs. 

   SPINS data is sent via FTP; credentials must
   be specified manually in the script.
*/

set_time_limit(0);

$SPINS_SERVER = "ftp.spins.com";

include('../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');

/**
  CONFIGURATION:
  SPINS.php needs to define your FTP username and
  password as $SPINS_USER and $SPINS_PW respectively.
*/
include($FANNIE_ROOT.'src/Credentials/SPINS.php');

$tstamp = time();
$week = date("W",$tstamp);
$week--;
if ($week == 0) $week = 52;


if (isset($argv[1]) && is_numeric($argv[1]))
    $week = $argv[1];

while(date("W",$tstamp) != $week or date("w",$tstamp) != 6){
    $tstamp = mktime(0,0,0,date("n",$tstamp),
        date("j",$tstamp)-1,date("Y",$tstamp));
}

$lastDay = date("M d, Y",$tstamp)." 11:59PM";

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
    $FANNIE_TRANS_DB,$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$dataQ = "SELECT d.upc as upc, p.description as description,
    sum(CASE WHEN d.quantity <> d.ItemQtty AND d.ItemQtty <> 0 THEN d.quantity*d.ItemQtty ELSE d.quantity END) as quantity,
    sum(d.total) as dollars,
    '$lastDay' as lastDay
    FROM dlog_90_view as d inner join 
    {$FANNIE_OP_DB}.dbo.products as p
    on d.upc=p.upc
    WHERE p.scale = 0
    AND d.upc > '0000000999999'
    AND datepart(ww,tdate) = $week
    group by d.upc, p.description";
// mysql handles week # differently by default
if (strstr($FANNIE_SERVER_DBMS,"MYSQL")){
    $dataQ = "SELECT d.upc as upc, p.description as description,
        sum(CASE WHEN d.quantity <> d.ItemQtty AND d.ItemQtty <> 0 THEN d.quantity*d.ItemQtty ELSE d.quantity END) as quantity,
        sum(d.total) as dollars,
        '$lastDay' as lastDay
        FROM dlog_90_view as d inner join 
        {$FANNIE_OP_DB}.products as p
        on d.upc=p.upc
        WHERE p.scale = 0
        AND d.upc > '0000000999999'
        AND week(tdate) = ".($week-1)."
        group by d.upc, p.description";
}

/* SPINS numbering is non-standard in 2012
   so week is offset by one in the filename
   this may change back next year
*/
$filename = date('mdY.csv', $tstamp);
$outfile = sys_get_temp_dir()."/".$filename;
$fp = fopen($outfile,"w");

$dataR = $sql->query($dataQ);
while($row = $sql->fetch_row($dataR)){
    for($i=0;$i<4; $i++){
        fwrite($fp,"\"".$row[$i]."\",");
    }
    fwrite($fp,"\"".$row[4]."\"\n");
}
fclose($fp);

$conn_id = ftp_connect($SPINS_SERVER);
$login_id = ftp_login($conn_id, $SPINS_USER, $SPINS_PW);

if (!$conn_id or !$login_id){
    echo "FTP connect failed!";
}

ftp_chdir($conn_id,"data");
ftp_pasv($conn_id,True);

$upload = ftp_put($conn_id, $filename, $outfile, FTP_ASCII);

if (!$upload){
    echo "FTP upload failed";
}

echo date('r').': Uploaded file to SPINS';
unlink($outfile);

ftp_close($conn_id);

?>
