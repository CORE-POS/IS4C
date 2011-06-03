<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

include('../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');

$UNFI_ID = 1;

set_time_limit(0);

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
		$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$dh = opendir("csvs");
while( ($file = readdir($dh)) !== False){
	if ($file[0] == ".") continue;
	if (file_exists("archive/".$file)){
		unlink('csvs/'.$file);
		continue; // this file's been done before	
	}
	if (substr(strtolower($file),-4) == ".zip"){
		$zip = zip_open("csvs/".$file);
		while ($zip_entry = zip_read($zip)) {
			$fp = fopen("csvs/".zip_entry_name($zip_entry), "w");
			if (zip_entry_open($zip, $zip_entry, "r")) {
				$buf = zip_entry_read($zip_entry, 
					zip_entry_filesize($zip_entry));
				fwrite($fp,"$buf");
				zip_entry_close($zip_entry);
			}
			fclose($fp);
		}
		zip_close($zip);

		rename("csvs/".$file,"archive/".$file);
	}
}
closedir($dh);

include($FANNIE_ROOT.'src/csv_parser.php');
$dh = opendir("csvs");
while( ($file = readdir($dh)) !== False){
	if ($file[0] == ".") continue;
	if (substr(strtolower($file),-4) == ".csv"){
		$fp = fopen('csvs/'.$file,'r');
		while(!feof($fp)){
			$line = fgets($fp);
			$data = csv_parser($line);
			if ($data[0] != 'Detail') continue;
			$upc = str_replace("-","",$data[3]);
			$upc = substr($upc,0,strlen($upc)-1); // check digit
			$upc = str_pad($upc,13,'0',STR_PAD_LEFT);
			$qty = (int)$data[5];
			$price = $data[13];
			$date = $data[28];
			$servings = (int)$data[29];

			$insQ = sprintf("INSERT INTO InvDelivery (inv_date,upc,vendor_id,
					quantity,price) VALUES (%s,%s,%d,%d,%.2f)",
					$sql->escape($date),$sql->escape($upc),
					$UNFI_ID,$qty*$servings,$price);
			$sql->query($insQ);
		}
		fclose($fp);
		unlink("csvs/".$file);
	}	
}
closedir($dh);

$sql->query("TRUNCATE TABLE InvCache");
$sql->query("INSERT INTO InvCache SELECT * FROM Inventory");


?>
