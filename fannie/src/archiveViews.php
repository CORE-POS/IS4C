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

function createViews($dstr,$db){
	global $FANNIE_SERVER_DBMS, $FANNIE_ARCHIVE_REMOTE,
		$FANNIE_ARCHIVE_DBMS, $FANNIE_ARCHIVE_DB,
		$FANNIE_SERVER,$FANNIE_SERVER_PW,$FANNIE_SERVER_USER,
		$FANNIE_ARCHIVE_SERVER,$FANNIE_ARCHIVE_USER,
		$FANNIE_ARCHIVE_PW;	

	if ($FANNIE_ARCHIVE_REMOTE){
		$db->add_connection($FANNIE_ARCHIVE_SERVER,$FANNIE_ARCHIVE_DBMS,
			$FANNIE_ARCHIVE_DB,$FANNIE_ARCHIVE_USER,
			$FANNIE_ARCHIVE_PW);
	}
	else {
		$db->add_connection($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
			$FANNIE_ARCHIVE_DB,$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);
	}

	$dbms = $FANNIE_ARCHIVE_REMOTE?$FANNIE_ARCHIVE_DBMS:$FANNIE_SERVER_DBMS;

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
