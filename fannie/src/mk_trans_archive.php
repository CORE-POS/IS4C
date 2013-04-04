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

function mk_trans_archive_table($month,$year,$db,$redo=False){
	global $FANNIE_SERVER_DBMS, $FANNIE_ARCHIVE_REMOTE,
		$FANNIE_ARCHIVE_DBMS, $FANNIE_ARCHIVE_DB;;

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
	  `mixMatch` varchar(13) default NULL,
	  `matched` smallint(6) default NULL,
	  `memType` tinyint(2) default NULL,
	  `staff` tinyint(4) default NULL,
	  `numflag` smallint(6) default 0 NULL,
	  `charflag` varchar(2) default '' NULL,
	  `card_no` varchar(255) default NULL,
	  `trans_id` int(11) default NULL
	)";

	if ($FANNIE_SERVER_DBMS == 'MSSQL'){
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
			[mixMatch] [nvarchar] (13) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[matched] [smallint] NOT NULL ,
			[memType] [smallint] NULL ,
			[staff] [tinyint] NULL ,
			[numflag] [smallint] NULL ,
			[charflag] [nvarchar] (2) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[card_no] [nvarchar] (6) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[trans_id] [int] NOT NULL )";
	}

	$month = str_pad($month,2,'0',STR_PAD_LEFT);
	if (strlen($year) == 2) $year = "20".$year;
	elseif(strlen($year) != 4)
		$year = "20".substr($year,-2);
	$name = "transArchive".$year.$month;

	$q = "CREATE TABLE $name $trans_columns";
	
	$exists = $db->table_exists($name);
	$p = $dbc->prepare_statement($q,$FANNIE_ARCHIVE_DB);
	if (!$exists){
		$db->exec_statement($p,array(),$FANNIE_ARCHIVE_DB);
		echo "Created archive table for $month / $year<br />";
	}
	else if ($exists && $redo){
		$drop = $db->prepare_statement("DROP TABLE $name",$FANNIE_ARCHIVE_DB);
		$db->exec_statement($drop,array(),$FANNIE_ARCHIVE_DB);
		$db->exec_statement($p,array(),$FANNIE_ARCHIVE_DB);
		echo "Re-created archive table for $month / $year<br />";
	}
	else {
		echo "Skipping existing table for $month / $year<br />";
	}
}

function mk_trans_archive_views($month,$year,$db){
	global $FANNIE_SERVER_DBMS, $FANNIE_ARCHIVE_DB;

	$month = (int)$month;
	$year = (int)$year;

	$month = str_pad($month,2,'0',STR_PAD_LEFT);
	if (strlen($year) == 2) $year = "20".$year;
	elseif(strlen($year) != 4)
		$year = "20".substr($year,-2);

	/* dlog view only requires slight tweak for My/MS SQL
	   receipt views are more divergent thus listed completely
	   separate */
	$dlogQ = "CREATE  view dlog$year$month as
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
		d.card_no, 
		d.trans_id,";
	if (strstr($FANNIE_SERVER_DBMS,"MYSQL")){
		$dlogQ .= "concat(convert(d.emp_no,char), '-', convert(d.register_no,char), '-',
			convert(d.trans_no,char)) as trans_num";
	}
	else {
		$dlogQ .= "(convert(varchar,d.emp_no) +  '-' + convert(varchar,d.register_no) + '-' + 
			convert(varchar,d.trans_no)) as trans_num";
	}
	$dlogQ .= " from transArchive$year$month as d
		where d.trans_status not in ('D','X','Z') and d.emp_no not in (9999,56) and d.register_no  <> 99";
	
	$rp1Q = "CREATE  view rp_dt_receipt_$year$month as 
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

		from transArchive$year$month
		where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'";

	if ($FANNIE_SERVER_DBMS == 'MSSQL'){
		$rp1Q = "CREATE  view rp_dt_receipt_$year$month as 
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
			from transArchive$year$month
			where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'";
	}

	
	$rp2Q = "create  view rp_receipt_header_$year$month as
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
		from transArchive$year$month
		group by register_no, emp_no, trans_no, card_no, datetime";

	if ($FANNIE_SERVER_DBMS == 'MSSQL'){
		$rp2Q = "create  view rp_receipt_header_$year$month as
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
			from transArchive$year$month
			group by register_no, emp_no, trans_no, card_no, datetime";
	}

	if ($db->table_exists("dlog".$year.$month,$FANNIE_ARCHIVE_DB)){
		$drop = $db->prepare_statement("DROP VIEW dlog$year$month", $FANNIE_ARCHIVE_DB);
		$db->exec_statement($drop,array(),$FANNIE_ARCHIVE_DB);
	}
	$dlogP = $db->prepare_statement($dlogQ,$FANNIE_ARCHIVE_DB);
	$db->exec_statement($dlogP,array(),$FANNIE_ARCHIVE_DB);
	if ($db->table_exists("rp_dt_receipt_".$year.$month,$FANNIE_ARCHIVE_DB)){
		$drop = $db->prepare_statement("DROP VIEW rp_dt_receipt_$year$month",$FANNIE_ARCHIVE_DB);
		$db->exec_statement($drop,array(),$FANNIE_ARCHIVE_DB);
	}
	$rp1P = $db->prepare_statement($rp1Q,$FANNIE_ARCHIVE_DB);
	$db->exec_statement($rp1P,array(),$FANNIE_ARCHIVE_DB);
	if ($db->table_exists("rp_receipt_header_".$year.$month,$FANNIE_ARCHIVE_DB)){
		$drop = $db->prepare_statement("DROP VIEW rp_receipt_header_$year$month",$FANNIE_ARCHIVE_DB);
		$db->exec_statement($drop,array(),$FANNIE_ARCHIVE_DB);
	}
	$rp2P = $db->prepare_statement($rp2Q,$FANNIE_ARCHIVE_DB);
	$db->exec_statement($rp2P,array(),$FANNIE_ARCHIVE_DB);

	echo "Created views for $month / $year <br />";
}
	

?>
