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

/* HELP

   nightly.dtrans.php

   [ variant for archiving to two servers ]

   This script archives transaction data. The main
   reason for rotating transaction data into
   multiple snapshot tables is speed. A single large
   transaction table eventually becomes slow. The 
   rotation applied here is as follows:

   dtransactions is copied into transarchive, then
   transarchive is trimmed so it contains the previous
   90 days of transactions.

   dlog_15, a lookup table of the past 15 days'
   transaction data, is reloaded using transarchive

   dtransactions is also copied to a monthly snapshot,
   transarchiveYYYYMM on the archive database. Support
   for archiving to a remote server is theoretical and
   should be thoroughly tested before being put into
   production. Archive tables are created automatically
   as are corresponding dlog and receipt views.

   After dtransactions has been copied to these two
   locations, it is truncated. This script is meant to
   be run nightly so that dtransactions always holds
   just the current day's data. 
*/

include('../config.php');
include('../src/SQLManager.php');
include($FANNIE_ROOT.'src/cron_msg.php');

set_time_limit(0);

/* push from MySQL to MSSQL */

unlink('/pos/csvs/mydtrans.csv');

$dbc = new SQLManager("129.103.2.2","MYSQL","is4c_trans","root",$FANNIE_SERVER_PW);
$dbc->query("SELECT * FROM dtransactions
	INTO OUTFILE '/pos/csvs/mydtrans.csv'
	FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"'
	LINES TERMINATED BY '\\r\\n'");
$dbc->close();

$dbc = new SQLManager('129.103.2.10','MSSQL','WedgePOS',
		'sa',$FANNIE_SERVER_PW);
$dbc->query("exec master..xp_cmdshell 'dtsrun /S IS4CSERV\IS4CSERV /U sa /P $FANNIE_SERVER_PW /N dt-import-my',no_output",'WedgePOS');
$dbc->close();

/* end pull */

exit;

/* push from MSSQL to MySQL follows */
/*
$dbc = new SQLManager('129.103.2.10','MSSQL','WedgePOS',
		'sa',$FANNIE_SERVER_PW);
$dbc->query("exec master..xp_cmdshell 'dtsrun /S IS4CSERV\IS4CSERV /U sa /P $FANNIE_SERVER_PW /N export_dt',no_output",'WedgePOS');
$dbc->close();

$sql = new SQLManager('129.103.2.2','MYSQL','is4c_trans',
		'root',$FANNIE_SERVER_PW);

$sql->query("LOAD DATA LOCAL INFILE '/pos/csvs/dtransactions.csv' 
	INTO TABLE dtransactions
	FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"'
	LINES TERMINATED BY '\\r\\n'");
*/

/*
$sql = new SQLManager('129.103.2.2','MYSQL','is4c_trans',
		'root',$FANNIE_SERVER_PW);
*/
/* Load dtransactions into the archive, trim to 90 days */
/*
$chk1 = $sql->query("INSERT INTO transarchive SELECT * FROM dtransactions");
$chk2 = $sql->query("DELETE FROM transarchive WHERE ".$sql->datediff($sql->now(),'datetime')." > 92");
if ($chk1 === false)
	echo cron_msg("Error loading data into transarchive");
elseif ($chk2 === false)
	echo cron_msg("Error trimming transarchive");
else
	echo cron_msg("Data rotated into transarchive");
*/

/* reload all the small snapshot */
/*
$chk1 = $sql->query("TRUNCATE TABLE dlog_15");
$chk2 = $sql->query("INSERT INTO dlog_15 SELECT * FROM dlog_90_view WHERE ".$sql->datediff($sql->now(),'tdate')." <= 15");
if ($chk1 === false || $chk2 === false)
	echo cron_msg("Error reloading dlog_15");
else
	echo cron_msg("Success reloading dlog_15");
*/

// use partitioned archive table; needs better solution by 3/31/2012
//$sql->query("INSERT INTO trans_archive.bigArchive SELECT * FROM dtransactions");
/* figure out which monthly archive dtransactions data belongs in */
/*
$res = $sql->query("SELECT month(datetime),year(datetime) FROM dtransactions");
$row = $sql->fetch_row($res);
$dstr = $row[1].(str_pad($row[0],2,'0',STR_PAD_LEFT));
$table = 'transArchive'.$dstr;

// archive dtransactions locally
if(!$FANNIE_ARCHIVE_REMOTE || True){
	$sql = new SQLManager('129.103.2.2','MYSQL',$FANNIE_ARCHIVE_DB,
			'root',$FANNIE_SERVER_PW);
	if (!$sql->table_exists($table)){
		$query = "CREATE TABLE $table LIKE is4c_trans.dtransactions";
		$chk1 = $sql->query($query,$FANNIE_ARCHIVE_DB);
		// mysql doesn't create & populate in one step
		$chk2 = $sql->query("INSERT INTO $table SELECT * FROM is4c_trans.dtransactions");
		if ($chk1 === false || $chk2 === false)
			echo cron_msg("Error creating new archive $table");
		else
			echo cron_msg("Created new table $table and archived dtransactions");
		createViews($dstr,$sql);
	}
	else {
		$query = "INSERT INTO $table SELECT * FROM is4c_trans.dtransactions";
		$chk = $sql->query($query,$FANNIE_ARCHIVE_DB);
		if ($chk === false)
			echo cron_msg("Error archiving dtransactions");
		else
			echo cron_msg("Success archiving dtransactions");
	}
}
*/

/* drop dtransactions data */
/*
$sql = new SQLManager('129.103.2.2','MYSQL','is4c_trans',
		'root',$FANNIE_SERVER_PW);
$chk = $sql->query("TRUNCATE TABLE dtransactions");
if ($chk === false)
	echo cron_msg("Error truncating dtransactions");
else
	echo cron_msg("Success truncating dtransactions");
*/

function createArchive($name,$db,$override_dbms=''){
	global $FANNIE_SERVER_DBMS, $FANNIE_ARCHIVE_REMOTE,
		$FANNIE_ARCHIVE_DBMS, $FANNIE_ARCHIVE_DB;

	$dbms = $FANNIE_ARCHIVE_REMOTE?$FANNIE_ARCHIVE_DBMS:$FANNIE_SERVER_DBMS;
	if ($override_dbms != '') $dbms = $override_dbms;
	
	$trans_columns = "(
	  `datetime` datetime default NULL,
	  `register_no` smallint(6) default NULL,
	  `emp_no` smallint(6) default NULL,
	  `trans_no` int(11) default NULL,
	  `upc` varchar(255) default NULL,
	  `description` varchar(255) default NULL,
	  `trans_type` varchar(255) default NULL,
	  `trans_subtype` varchar(255) default NULL,
	  `trans_status` varchar(255) default NULL,
	  `department` smallint(6) default NULL,
	  `quantity` double default NULL,
	  `scale` tinyint(4) default NULL,
	  `cost` double default 0.00 NULL,
	  `unitPrice` double default NULL,
	  `total` double default NULL,
	  `regPrice` double default NULL,
	  `tax` smallint(6) default NULL,
	  `foodstamp` tinyint(4) default NULL,
	  `discount` double default NULL,
	  `memDiscount` double default NULL,
	  `discountable` tinyint(4) default NULL,
	  `discounttype` tinyint(4) default NULL,
	  `voided` tinyint(4) default NULL,
	  `percentDiscount` tinyint(4) default NULL,
	  `ItemQtty` double default NULL,
	  `volDiscType` tinyint(4) default NULL,
	  `volume` tinyint(4) default NULL,
	  `VolSpecial` double default NULL,
	  `mixMatch` smallint(6) default NULL,
	  `matched` smallint(6) default NULL,
	  `memType` tinyint(2) default NULL,
	  `staff` tinyint(4) default NULL,
	  `numflag` smallint(6) default 0 NULL,
	  `charflag` varchar(2) default '' NULL,
	  `card_no` varchar(255) default NULL,
	  `trans_id` int(11) default NULL
	)";

	if ($dbms == 'MSSQL'){
		$trans_columns = "([datetime] [datetime] NOT NULL ,
			[register_no] [smallint] NOT NULL ,
			[emp_no] [smallint] NOT NULL ,
			[trans_no] [int] NOT NULL ,
			[upc] [nvarchar] (13) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[description] [nvarchar] (30) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[trans_type] [nvarchar] (1) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[trans_subtype] [nvarchar] (2) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[trans_status] [nvarchar] (1) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[department] [smallint] NULL ,
			[quantity] [float] NULL ,
			[scale] [tinyint] NULL ,
			[cost] [money] NULL ,
			[unitPrice] [money] NULL ,
			[total] [money] NOT NULL ,
			[regPrice] [money] NULL ,
			[tax] [smallint] NULL ,
			[foodstamp] [tinyint] NOT NULL ,
			[discount] [money] NOT NULL ,
			[memDiscount] [money] NULL ,
			[discountable] [tinyint] NULL ,
			[discounttype] [tinyint] NULL ,
			[voided] [tinyint] NULL ,
			[percentDiscount] [tinyint] NULL ,
			[ItemQtty] [float] NULL ,
			[volDiscType] [tinyint] NOT NULL ,
			[volume] [tinyint] NOT NULL ,
			[VolSpecial] [money] NOT NULL ,
			[mixMatch] [smallint] NULL ,
			[matched] [smallint] NOT NULL ,
			[memType] [smallint] NULL ,
			[isStaff] [tinyint] NULL ,
			[numflag] [smallint] NULL ,
			[charflag] [nvarchar] (2) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[card_no] [nvarchar] (6) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[trans_id] [int] NOT NULL )";
	}

	$db->query("CREATE TABLE $table $trans_columns",$FANNIE_ARCHIVE_DB);
}

function createViews($dstr,$db){
	global $FANNIE_SERVER_DBMS, $FANNIE_ARCHIVE_REMOTE,
		$FANNIE_ARCHIVE_DBMS, $FANNIE_ARCHIVE_DB,
		$FANNIE_SERVER,$FANNIE_SERVER_PW,$FANNIE_SERVER_USER,
		$FANNIE_ARCHIVE_SERVER,$FANNIE_ARCHIVE_USER,
		$FANNIE_ARCHIVE_PW;	

	$dbms = 'MYSQL';

	$dlogQ = "CREATE  view dlog$dstr as
		select 
		d.datetime as tdate, 
		d.register_no, 
		d.emp_no, 
		d.trans_no, 
		d.upc, 
		CASE WHEN (d.trans_subtype IN ('CP','IC') OR d.upc like('%000000052')) then 'T' 
			WHEN d.upc = 'DISCOUNT' then 'S' else d.trans_type end as trans_type, 
		CASE WHEN d.upc = 'MAD Coupon' THEN 'MA' ELSe 
		   case when d.upc like('%00000000052') then 'RR' else d.trans_subtype end end as trans_subtype, 
		d.trans_status, 
		d.department, 
		d.quantity, 
		d.unitPrice, 
		d.total, 
		d.tax, 
		d.foodstamp, 
		d.itemQtty, 
		d.memType,
		d.staff,
		d.numflag,
		d.charflag,
		d.card_no, 
		d.trans_id,
		concat(convert(d.emp_no,char), '-', convert(d.register_no,char), '-',
		convert(d.trans_no,char)) as trans_num

		from transArchive$dstr as d
		where d.trans_status not in ('D','X','Z') and d.emp_no not in (9999,56) and d.register_no  <> 99";

	if ($dbms == "MSSQL"){
		$dlogQ = "CREATE  view dlog$dstr as
			select 
			d.datetime as tdate, 
			d.register_no, 
			d.emp_no, 
			d.trans_no, 
			d.upc, 
			CASE WHEN (d.trans_subtype IN ('CP','IC') OR d.upc like('%000000052')) then 'T' 
				WHEN d.upc = 'DISCOUNT' then 'S' else d.trans_type end as trans_type, 
			CASE WHEN d.upc = 'MAD Coupon' THEN 'MA' ELSe 
			   case when d.upc like('%00000000052') then 'RR' else d.trans_subtype end end as trans_subtype, 
			d.trans_status, 
			d.department, 
			d.quantity, 
			d.unitPrice, 
			d.total, 
			d.tax, 
			d.foodstamp, 
			d.itemQtty, 
			d.memType,
			d.isStaff,
			d.numflag,
			d.charflag,
			d.card_no, 
			d.trans_id,
			(convert(varchar,d.emp_no) +  '-' + convert(varchar,d.register_no) + '-' + 
			convert(varchar,d.trans_no)) as trans_num

			from transArchive$dstr as d
			where d.trans_status not in ('D','X','Z') and d.emp_no not in (9999,56) and d.register_no  <> 99";
	}
	$chk = $db->query($dlogQ,$FANNIE_ARCHIVE_DB);
	if ($chk === false)
		echo cron_msg("Error creating dlog view for new archive table");

	$rp1Q = "CREATE  view rp_dt_receipt_$dstr as 
		select 
		datetime,
		register_no,
		emp_no,
		trans_no,
		description,
		case 
			when voided = 5 
				then 'Discount'
			when trans_status = 'M'
				then 'Mbr special'
			when scale <> 0 and quantity <> 0 
				then concat(convert(quantity,char), ' @ ', convert(unitPrice,char))
			when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
				then concat(convert(volume,char), ' /', convert(unitPrice,char))
			when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
				then concat(convert(Quantity,char), ' @ ', convert(Volume,char), ' /', convert(unitPrice,char))
			when abs(itemQtty) > 1 and discounttype = 3
				then concat(convert(ItemQtty,char), ' /', convert(UnitPrice,char))
			when abs(itemQtty) > 1
				then concat(convert(quantity,char), ' @ ', convert(unitPrice,char))	
			when matched > 0
				then '1 w/ vol adj'
			else ''
				
		end
		as comment,
			total,

		case 
			when trans_status = 'V' 
				then 'VD'
			when trans_status = 'R'
				then 'RF'
			when tax <> 0 and foodstamp <> 0
				then 'TF'
			when tax <> 0 and foodstamp = 0
				then 'T' 
			when tax = 0 and foodstamp <> 0
				then 'F'
			when tax = 0 and foodstamp = 0
				then '' 
		end
		as Status,
		trans_type,
		card_no as memberID,
		unitPrice,
		voided,
		trans_id,
		concat(convert(emp_no,char), '-', convert(register_no,char), '-', convert(trans_no,char)) as trans_num

		from transArchive$dstr
		where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'";
	if ($dbms == 'MSSQL'){
		$rp1Q = "CREATE  view rp_dt_receipt_$dstr as 
			select 
			datetime,
			register_no,
			emp_no,
			trans_no,
			description,
			case 
				when voided = 5 
					then 'Discount'
				when trans_status = 'M'
					then 'Mbr special'
				when scale <> 0 and quantity <> 0 
					then convert(varchar, quantity) + ' @ ' + convert(varchar, unitPrice)
				when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
					then convert(varchar, volume) + ' /' + convert(varchar, unitPrice)
				when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
					then convert(varchar, Quantity) + ' @ ' + convert(varchar, Volume) + ' /' + convert(varchar, unitPrice)
				when abs(itemQtty) > 1 and discounttype = 3
					then convert(varchar,ItemQtty) + ' /' + convert(varchar, UnitPrice)
				when abs(itemQtty) > 1
					then convert(varchar, quantity) + ' @ ' + convert(varchar, unitPrice)	
				when matched > 0
					then '1 w/ vol adj'
				else ''
					
			end
			as comment,
				total,

			case 
				when trans_status = 'V' 
					then 'VD'
				when trans_status = 'R'
					then 'RF'
				when tax <> 0 and foodstamp <> 0
					then 'TF'
				when tax <> 0 and foodstamp = 0
					then 'T' 
				when tax = 0 and foodstamp <> 0
					then 'F'
				when tax = 0 and foodstamp = 0
					then '' 
			end
			as Status,
			trans_type,
			card_no as memberID,
			unitPrice,
			voided,
			trans_id,
			(convert(varchar,emp_no) +  '-' + convert(varchar,register_no) + '-' + convert(varchar,trans_no)) as trans_num

			from transArchive$dstr
			where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'";
	}
	$chk = $db->query($rp1Q,$FANNIE_ARCHIVE_DB);
	if ($chk === false)
		echo cron_msg("Error creating receipt view for new archive table");

	$rp2Q = "create  view rp_receipt_header_$dstr as
		select
		datetime as dateTimeStamp,
		card_no as memberID,
		concat(convert(emp_no,char), '-', convert(register_no,char), '-', convert(trans_no,char)) as trans_num,
		register_no,
		emp_no,
		trans_no,
		convert(sum(case when discounttype = 1 then discount else 0 end),decimal(10,2)) as discountTTL,
		convert(sum(case when discounttype = 2 then memDiscount else 0 end),decimal(10,2)) as memSpecial,
		convert(sum(case when upc = '0000000008005' then total else 0 end),decimal(10,2)) as couponTotal,
		convert(sum(case when upc = 'MEMCOUPON' then unitPrice else 0 end),decimal(10,2)) as memCoupon,
		abs(sum(case when trans_subtype = 'MI' or trans_subtype = 'CX' then total else 0 end)) as chargeTotal,
		sum(case when upc = 'Discount' then total else 0 end) as transDiscount,
		sum(case when trans_type = 'T' then -1 * total else 0 end) as tenderTotal

		from transArchive$dstr
		group by register_no, emp_no, trans_no, card_no, datetime";
	if ($dbms == 'MSSQL'){
		$rp2Q = "create  view rp_receipt_header_$dstr as
			select
			datetime as dateTimeStamp,
			card_no as memberID,
			(convert(varchar,emp_no) +  '-' + convert(varchar,register_no) + '-' + convert(varchar,trans_no)) as trans_num,
			register_no,
			emp_no,
			trans_no,
			convert(numeric(10,2), sum(case when discounttype = 1 then discount else 0 end)) as discountTTL,
			convert(numeric(10,2), sum(case when discounttype = 2 then memDiscount else 0 end)) as memSpecial,
			convert(numeric(10,2), sum(case when upc = '0000000008005' then total else 0 end)) as couponTotal,
			convert(numeric(10,2), sum(case when upc = 'MEMCOUPON' then unitPrice else 0 end)) as memCoupon,
			abs(sum(case when trans_subtype = 'MI' or trans_subtype = 'CX' then total else 0 end)) as chargeTotal,
			sum(case when upc = 'Discount' then total else 0 end) as transDiscount,
			sum(case when trans_type = 'T' then -1 * total else 0 end) as tenderTotal

			from transArchive$dstr
			group by register_no, emp_no, trans_no, card_no, datetime";
	}
	$chk = $db->query($rp2Q,$FANNIE_ARCHIVE_DB);
	if ($chk === false)
		echo cron_msg("Error creating receipt header view for new archive table");
}

?>
