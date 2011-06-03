<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of IT CORE.

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

function buildLTTViews($db,$type){
	if ($type == 'mysql') buildLTTViewsMySQL($db);
	elseif($type == 'mssql') buildLTTViewsMSSQL($db);
}

function buildLTTViewsMySQL($db){

//--------------------------------------------------------------
// CREATE lttSummary VIEW
//--------------------------------------------------------------

$createStr = "CREATE view lttsummary as
	select 
	(case when min(datetime) is null then now() else min(datetime) end) as tdate,
	max(card_no) as card_no, 
	convert(sum(total),decimal(10,2)) as runningTotal,
	convert(sum(case when discounttype = 1 then discount else 0 end),decimal(10,2)) as discountTTL,
	convert(sum(case when discountable <> 0 and tax <> 0 then total else 0 end),decimal(10,2)) as discTaxable,
	convert(sum(case when discounttype in (2,3) then memDiscount else 0 end),decimal(10,2)) as memSpecial,
	case when (min(datetime) is null) then 0 else
		sum(CASE WHEN discounttype = 4 THEN memDiscount ELSE 0 END)
	end as staffSpecial,
	convert(sum(case when discountable = 0 then 0 else total end),decimal(10,2)) as discountableTTL,
	convert(sum(case when discountable = 7 then total else 0 end),decimal(10,2)) as scDiscountableTTL,
	";	

$taxRatesQ = "select id,description from taxrates order by id";
$taxRatesR = $db->query($taxRatesQ);
while ($taxRatesW = $db->fetch_row($taxRatesR)){
	$createStr .= "convert(sum(case when (trans_type = 'I' or trans_type = 'D') and tax = ".$taxRatesW[0]." and discountable = 0 then total else 0 end),decimal(10,2)) as noDiscTaxable".$taxRatesW[1].",\n";
	$createStr .= "convert(sum(case when (trans_type = 'I' or trans_type = 'D') and tax = ".$taxRatesW[0]." and discountable <> 0 then total else 0 end),decimal(10,2)) as discTaxable".$taxRatesW[1].",\n";
	$createStr .= "convert(sum(case when (trans_type = 'I' or trans_type = 'D') and tax = ".$taxRatesW[0]." and discountable = 0 and foodstamp=1 then total else 0 end),decimal(10,2)) as fsTaxable".$taxRatesW[1].",\n";
	$createStr .= "convert(sum(case when (trans_type = 'I' or trans_type = 'D') and tax = ".$taxRatesW[0]." and discountable <> 0 and foodstamp=1 then total else 0 end),decimal(10,2)) as fsDiscTaxable".$taxRatesW[1].",\n";
}

$createStr .= "convert(sum(
	case	 
	when (trans_type = 'I' or trans_type = 'D') and tax = 1 and discountable <> 7 then total 
	when (trans_type = 'I' or trans_type = 'D') and tax = 1 and discountable = 7 then (total * 0.9)
	else 0 end),decimal(10,2)) as scTaxable,

convert(sum(case when trans_subtype = 'MI' or trans_subtype = 'CX'  then total else 0 end),decimal(10,2)) as chargeTotal,
convert(sum(case when trans_subtype = 'CC'  then total else 0 end),decimal(10,2)) as ccTotal,
convert(sum(case when department = 990  then total else 0 end),decimal(10,2)) as paymentTotal,
convert(sum(case when trans_subtype = 'MI'  or department = 990 then total else 0 end),decimal(10,2)) as memChargeTotal,
convert(sum(case when trans_type = 'T' then total else 0 end),decimal(10,2)) as tenderTotal,\n";
$createStr .= "convert(sum(case when trans_subtype = 'FS' or trans_subtype = 'EF' then total else 0 end),decimal(10,2)) as fsTendered,
convert(sum(case when (foodstamp = 1 or trans_subtype='FS' or trans_subtype='EF') then total else 0 end),decimal(10,2)) as fsTotal,
convert(sum(case when foodstamp = 1 and discountable = 0 then total else 0 end),decimal(10,2)) as fsItems,
convert(sum(case when foodstamp = 1 and discountable <> 0 then total else 0 end),decimal(10,2)) as fsDiscItems,
convert(sum(case when trans_status = 'R' then total else 0 end),decimal(10,2)) as refundTotal,
convert(sum(case when upc = '0000000008005' then total else 0 end),decimal(10,2)) as couponTotal,
convert(sum(case when upc = 'MEMCOUPON' then unitPrice else 0 end),decimal(10,2)) as memCoupon,
(case when (max(percentDiscount) is null or max(percentDiscount) < 0) then 0.00 else max(convert(percentDiscount,decimal)) end) as percentDiscount,
convert(sum(case when numflag=1 THEN total ELSE 0 END),decimal(10,2)) as localTotal,
max(trans_id) as LastID
from localtemptrans\n";

$db->query("DROP VIEW lttsummary");
$db->query($createStr);
$rpQ = str_replace("select","select emp_no,register_no,trans_no,",$createStr);
$rpQ = str_replace("localtemptrans","localtranstoday",$rpQ);
$rpQ = str_replace("lttsummary","rp_lttsummary",$rpQ);
$rpQ .= " GROUP BY emp_no,register_no,trans_no";
$db->query("DROP VIEW rp_lttsummary");
$db->query($rpQ);
//echo str_replace("\n","<br />",$createStr)."<br />";
//echo "<hr />";


//--------------------------------------------------------------
// CREATE lttSubTotals VIEW
//--------------------------------------------------------------

$createStr = "CREATE VIEW lttsubtotals AS
	select tdate,\n";
$ratesQ = "select description,rate from taxrates";
$ratesR = $db->query($ratesQ);
$desc = array();
$rates = array();
while ($ratesW = $db->fetch_row($ratesR)){
	array_push($desc,$ratesW[0]);
	array_push($rates,$ratesW[1]);
}
if (count($rates) > 0){
	$createStr .= "convert(";
	for ($i = 0; $i < count($rates); $i++){
		$createStr .= "(noDiscTaxable".$desc[$i]." * ".$rates[$i].") + ";
		$createStr .= "(discTaxable".$desc[$i]." * ((100-percentDiscount)/100) * ".$rates[$i].") + ";
	}
	$createStr = substr($createStr,0,strlen($createStr)-2);
	$createStr .= ",decimal(10,2)) as taxTotal,\n";
}
else $createStr .= "0 as taxTotal,\n";

$createStr .= "convert(discTaxable * (100 - percentDiscount/100),decimal(10,2)) as ADtaxable,
	convert(scTaxable *  ((100 - percentDiscount)/100) * 0.09,decimal(10,2)) as scTaxTotal,
fsTendered,
(fsDiscItems  - convert((fsDiscItems  * (percentDiscount)/100),decimal(10,2)) +fsItems + fsTendered) as fsEligible,
convert(fsTendered * 0.075,decimal(10,2)) as fsTendered07,
convert(fsTendered * 0.09,decimal(10,2)) as fsTendered085,\n";
if(count($rates) > 0){
	for ($i = 0; $i < count($rates); $i++){
		$createStr .= "convert((fsDiscTaxable".$desc[$i]."*((100-percentDiscount)/100)) + fsTaxable".$desc[$i].",decimal(10,2)) as fsTaxable".$desc[$i].",";
		$createStr .= "convert((fsDiscTaxable".$desc[$i]."*((100-percentDiscount)/100)*".$rates[$i].")+(fsTaxable".$desc[$i]."*".$rates[$i]."),decimal(10,2)) as fsTax".$desc[$i].",\n";
	}
}
else $createStr .= "0 as fsTax,\n";

$createStr .= "convert(scDiscountableTTL * 0.1,decimal(10,2)) as scDiscount,
convert(discountableTTL * percentDiscount / 100,decimal(10,2)) as transDiscount,
convert((discountableTTL - (scDiscountableTTL * 0.1)) * percentDiscount / 100,decimal(10,2)) as scTransDiscount

from lttsummary\n";
$db->query("DROP VIEW lttsubtotals");
$db->query($createStr);
$rpQ = str_replace("select","select emp_no,register_no,trans_no,",$createStr);
$rpQ = str_replace("lttsummary","rp_lttsummary",$rpQ);
$rpQ = str_replace("lttsubtotals","rp_lttsubtotals",$rpQ);
$db->query("DROP VIEW rp_lttsubtotals");
$db->query($rpQ);
//echo str_replace("\n","<br />",$createStr)."<br />";
//echo "<hr />";

//--------------------------------------------------------------
// CREATE SubTotals VIEW
//--------------------------------------------------------------

$createStr = "CREATE view subtotals as
select
(case when l.LastID is null then 0 else l.LastID end) as LastID,
l.card_no as card_no,
l.runningTotal as runningTotal,
convert((l.runningTotal - s.transDiscount),decimal(10,2)) as subTotal,
l.discountableTTL as discountableTotal,
l.tenderTotal,
l.chargeTotal,
l.ccTotal,
l.paymentTotal,
l.memChargeTotal,
l.discountTTL,
l.memSpecial,
l.staffSpecial,
l.refundTotal as refundTotal,
l.couponTotal as couponTotal,
l.memCoupon as memCoupon,
case when convert(l.runningTotal - s.transDiscount,decimal(10,2)) * .05 > 2.5 then 2.5
else convert (l.runningTotal - s.transDiscount,decimal(10,2)) * .05 end as madCoupon,
s.fsEligible as fsEligible,\n";

$ratesQ = "select description,rate from taxRates order by rate desc";
$ratesR = $db->query($ratesQ);
$desc =  array();
$rates = array();
while ($ratesW = $db->fetch_row($ratesR)){
	array_push($desc,$ratesW[0]);
	array_push($rates,$ratesW[1]);
}
$fsTaxStr = "convert(CASE WHEN ";
for ($i = 0; $i < count($rates); $i++)
	$fsTaxStr .= "s.fsTaxable".$desc[$i]."+";
$fsTaxStr = substr($fsTaxStr,0,strlen($fsTaxStr)-1);
$fsTaxStr .= " = 0 THEN 0 ELSE CASE WHEN l.fsTendered <> 0 AND -1 * l.fsTendered >= ";
for ($i = 0; $i < count($rates); $i++)
	$fsTaxStr .= "s.fsTaxable".$desc[$i]."+";
$fsTaxStr = substr($fsTaxStr,0,strlen($fsTaxStr)-1);
$fsTaxStr .= " THEN -1 * (";
for ($i = 0; $i < count($rates); $i++)
	$fsTaxStr .= "s.fsTax".$desc[$i]."+";
$fsTaxStr = substr($fsTaxStr,0,strlen($fsTaxStr)-1);
$fsTaxStr .= ") ELSE CASE ";
for ($i = 0; $i < count($rates); $i++){
	$fsTaxStr .= "WHEN -1*l.fsTendered ";
	for ($j = $i-1; $j >= 0; $j--)
		$fsTaxStr .= "-s.fsTaxable".$desc[$j];
	$fsTaxStr .= "<= s.fsTaxable".$desc[$i];
	$fsTaxStr .= " THEN -(";
	for ($j = $i-1; $j >= 0; $j--)
		$fsTaxStr .= "s.fsTax".$desc[$j]."+";
	$fsTaxStr .= "((-1*l.fsTendered ";
	for ($j = $i-1; $j >= 0; $j--)
		$fsTaxStr .= "-s.fsTaxable".$desc[$j];
	$fsTaxStr .= ") * ".$rates[$i]."))";
}
$fsTaxStr .= " ELSE 0 ";
$fsTaxStr .= " END END END,decimal(10,2))\n";

if(count($rates) > 0){
	$createStr .= $fsTaxStr." as fsTaxExempt,\n";
	$createStr .= "convert(s.taxTotal+".$fsTaxStr.",decimal(10,2)) as taxTotal,\n";
}
else {
	$createStr .= "0 as fsTaxExempt,\n";
	$createStr .= "0 as taxTotal,\n";
}
$createStr .= "s.scTaxTotal,\n";
if(count($rates) > 0){
	$createStr .= "convert((l.runningTotal - s.transdiscount) + s.taxTotal + ".$fsTaxStr.",decimal(10,2)) as AmtDue,\n";
	$createStr .= "convert((l.runningTotal - s.sctransdiscount) + s.taxTotal + ".$fsTaxStr.",decimal(10,2)) as scAmtDue,\n";
}
else {
	$createStr .= "convert((l.runningTotal - s.transdiscount) + s.taxTotal ,decimal(10,2)) as AmtDue,\n";
	$createStr .= "convert((l.runningTotal - s.sctransdiscount) + s.taxTotal ,decimal(10,2)) as scAmtDue,\n";
}
$createStr .= "s.scDiscount,
s.transDiscount,
s.scTransDiscount,
l.percentDiscount,
l.localTotal
from lttsummary l, lttsubtotals s where l.tdate = s.tdate\n";

$db->query("DROP VIEW subtotals");
$db->query($createStr);
$rpQ = str_replace("select","select l.emp_no,l.register_no,l.trans_no,",$createStr);
$rpQ = str_replace("lttsummary","rp_lttsummary",$rpQ);
$rpQ = str_replace("lttsubtotals","rp_lttsubtotals",$rpQ);
$rpQ = str_replace("view subtotals","view rp_subtotals",$rpQ);
$rpQ .= " AND l.emp_no=s.emp_no AND 
	l.register_no=s.register_no AND
	l.trans_no=s.trans_no";
$db->query("DROP VIEW rp_subtotals");
$db->query($rpQ);
//echo str_replace("\n","<br />",$createStr)."<br />";

}

function buildLTTViewsMSSQL($db){

//--------------------------------------------------------------
// CREATE lttSummary VIEW
//--------------------------------------------------------------

$createStr = "CREATE view lttsummary as
	select 
	(case when min(datetime) is null then getdate() else min(datetime) end) as tdate,
	max(card_no) as card_no, 
	convert(numeric(10,2),sum(total)) as runningTotal,
	convert(numeric(10,2),sum(case when discounttype = 1 then discount else 0 end)) as discountTTL,
	convert(numeric(10,2),sum(case when discounttype in (2,3) then memDiscount else 0 end)) as memSpecial,
	case when (min(datetime) is null) then 0 else
		sum(CASE WHEN discounttype = 4 THEN memDiscount ELSE 0 END)
	end as staffSpecial,
	convert(numeric(10,2),sum(case when discountable = 0 then 0 else total end)) as discountableTTL,
	convert(numeric(10,2),sum(case when discountable = 7 then total else 0 end)) as scDiscountableTTL,
	";	

$taxRatesQ = "select id,description from taxRates order by id";
$taxRatesR = $db->query($taxRatesQ);
while ($taxRatesW = $db->fetch_row($taxRatesR)){
	$createStr .= "convert(numeric(10,2),sum(case when (trans_type = 'I' or trans_type = 'D') and tax = ".$taxRatesW[0]." and discountable = 0 then total else 0 end)) as noDiscTaxable".$taxRatesW[1].",";
	$createStr .= "convert(numeric(10,2),sum(case when (trans_type = 'I' or trans_type = 'D') and tax = ".$taxRatesW[0]." and discountable <> 0 then total else 0 end)) as discTaxable".$taxRatesW[1].",";
	$createStr .= "convert(numeric(10,2),sum(case when (trans_type = 'I' or trans_type = 'D') and tax = ".$taxRatesW[0]." and discountable = 0 and foodstamp=1 then total else 0 end)) as fsTaxable".$taxRatesW[1].",";
	$createStr .= "convert(numeric(10,2),sum(case when (trans_type = 'I' or trans_type = 'D') and tax = ".$taxRatesW[0]." and discountable <> 0 and foodstamp=1 then total else 0 end)) as fsDiscTaxable".$taxRatesW[1].",";
}

$createStr .= "convert(numeric(10,2),sum(
	case	 
	when (trans_type = 'I' or trans_type = 'D') and tax = 1 and discountable <> 7 then total 
	when (trans_type = 'I' or trans_type = 'D') and tax = 1 and discountable = 7 then (total * 0.9)
	else 0 end)) as scTaxable,

convert(numeric(10,2),sum(case when trans_subtype = 'MI' or trans_subtype = 'CX'  then total else 0 end)) as chargeTotal,
convert(numeric(10,2),sum(case when trans_subtype = 'CC'  then total else 0 end)) as ccTotal,
convert(numeric(10,2),sum(case when department = 990  then total else 0 end)) as paymentTotal,
convert(numeric(10,2),sum(case when trans_subtype = 'MI'  or department = 990 then total else 0 end)) as memChargeTotal,
convert(numeric(10,2),sum(case when trans_type = 'T' then total else 0 end)) as tenderTotal,";
$createStr .= "convert(numeric(10,2),sum(case when trans_subtype = 'FS' or trans_subtype = 'EF' then total else 0 end)) as fsTendered,
convert(numeric(10,2),sum(case when (foodstamp = 1 or trans_subtype='FS' or trans_subtype='EF') then total else 0 end)) as fsTotal,
convert(numeric(10,2),sum(case when foodstamp = 1 and discountable = 0 then total else 0 end)) as fsItems,
convert(numeric(10,2),sum(case when foodstamp = 1 and discountable <> 0 then total else 0 end)) as fsDiscItems,
convert(numeric(10,2),sum(case when trans_status = 'R' then total else 0 end)) as refundTotal,
convert(numeric(10,2),sum(case when upc = '0000000008005' then total else 0 end)) as couponTotal,
convert(numeric(10,2),sum(case when upc = 'MEMCOUPON' then unitPrice else 0 end)) as memCoupon,
(case when (max(percentDiscount) is null or max(percentDiscount) < 0) then 0.00 else max(convert(numeric(10,2),percentDiscount)) end) as percentDiscount,
convert(numeric(10,2),sum(case when numflag=1 then total else 0 end)) as localTotal,
max(trans_id) as LastID
from localtemptrans";

$db->query("DROP VIEW lttsummary");
$db->query($createStr);
$rpQ = str_replace("select","select emp_no,register_no,trans_no,",$createStr);
$rpQ = str_replace("localtemptrans","localtranstoday",$rpQ);
$rpQ = str_replace("lttsummary","rp_lttsummary",$rpQ);
$rpQ .= " GROUP BY emp_no,register_no,trans_no";
$db->query("DROP VIEW rp_lttsummary");
$db->query($rpQ);
//echo $createStr."<br />";
//echo "lttSummary view created<br />";


//--------------------------------------------------------------
// CREATE lttSubTotals VIEW
//--------------------------------------------------------------

$createStr = "CREATE VIEW lttsubtotals AS
	select tdate,";
$ratesQ = "select description,rate from taxRates";
$ratesR = $db->query($ratesQ);
$desc = array();
$rates = array();
while ($ratesW = $db->fetch_row($ratesR)){
	array_push($desc,$ratesW[0]);
	array_push($rates,$ratesW[1]);
}
if(count($rates) > 0){
	$createStr .= "convert(numeric(10,2),";
	for ($i = 0; $i < count($rates); $i++){
		$createStr .= "(noDiscTaxable".$desc[$i]." * ".$rates[$i].") + ";
		$createStr .= "(discTaxable".$desc[$i]." * ((100-percentDiscount)/100) * ".$rates[$i].") + ";
	}
	$createStr = substr($createStr,0,strlen($createStr)-2);
	$createStr .= ") as taxTotal,";
}
else $createStr .= "0 as taxTotal,";

$createStr .= "convert(numeric(10,2),scTaxable *  ((100 - percentDiscount)/100) * 0.09) as scTaxTotal,
fsTendered,
(fsDiscItems  - convert(numeric(10,2),(fsDiscItems  * (percentDiscount)/100)) +fsItems + fsTendered) as fsEligible,
convert(numeric(10,2),fsTendered * 0.075) as fsTendered07,
convert(numeric(10,2),fsTendered * 0.09) as fsTendered085,";
if(count($rates) > 0){
	for ($i = 0; $i < count($rates); $i++){
		$createStr .= "convert(numeric(10,2),(fsDiscTaxable".$desc[$i]."*((100-percentDiscount)/100)) + fsTaxable".$desc[$i].") as fsTaxable".$desc[$i].",";
		$createStr .= "convert(numeric(10,2),(fsDiscTaxable".$desc[$i]."*((100-percentDiscount)/100)*".$rates[$i].")+(fsTaxable".$desc[$i]."*".$rates[$i].")) as fsTax".$desc[$i].",";
	}
}
else $createStr .= "0 as fsTax,";

$createStr .= "convert(numeric(10,2),scDiscountableTTL * 0.1) as scDiscount,
convert(numeric(10,2),discountableTTL * percentDiscount / 100) as transDiscount,
convert(numeric(10,2),(discountableTTL - (scDiscountableTTL * 0.1)) * percentDiscount / 100) as scTransDiscount

from lttsummary";
$db->query("DROP VIEW lttsubtotals");
$db->query($createStr);
$rpQ = str_replace("select","select emp_no,register_no,trans_no,",$createStr);
$rpQ = str_replace("lttsummary","rp_lttsummary",$rpQ);
$rpQ = str_replace("lttsubtotals","rp_lttsubtotals",$rpQ);
$db->query("DROP VIEW rp_lttsubtotals");
$db->query($rpQ);
//echo $createStr."<br />";
//echo "lttSubTotals view created<br />";

//--------------------------------------------------------------
// CREATE SubTotals VIEW
//--------------------------------------------------------------

$createStr = "CREATE view subtotals as
select
(case when l.LastID is null then 0 else l.LastID end) as LastID,
l.card_no as card_no,
l.runningTotal as runningTotal,
convert(numeric(10,2),(l.runningTotal - s.transDiscount)) as subTotal,
l.discountableTTL as discountableTotal,
l.tenderTotal,
l.chargeTotal,
l.ccTotal,
l.paymentTotal,
l.memChargeTotal,
l.discountTTL,
l.memSpecial,
l.staffSpecial,
l.refundTotal as refundTotal,
l.couponTotal as couponTotal,
l.memCoupon as memCoupon,
case when convert(numeric(10,2),l.runningTotal - s.transDiscount) * .05 > 2.5 then 2.5
else convert(numeric(10,2),l.runningTotal - s.transDiscount) * .05 end as madCoupon,
s.fsEligible as fsEligible,";

$ratesQ = "select description,rate from taxRates order by rate desc";
$ratesR = $db->query($ratesQ);
$desc =  array();
$rates = array();
while ($ratesW = $db->fetch_row($ratesR)){
	array_push($desc,$ratesW[0]);
	array_push($rates,$ratesW[1]);
}
$fsTaxStr = "convert(numeric(10,2),CASE WHEN ";
for ($i = 0; $i < count($rates); $i++)
	$fsTaxStr .= "s.fsTaxable".$desc[$i]."+";
$fsTaxStr = substr($fsTaxStr,0,strlen($fsTaxStr)-1);
$fsTaxStr .= " = 0 THEN 0 ELSE CASE WHEN -1 * l.fsTendered >= ";
for ($i = 0; $i < count($rates); $i++)
	$fsTaxStr .= "s.fsTaxable".$desc[$i]."+";
$fsTaxStr = substr($fsTaxStr,0,strlen($fsTaxStr)-1);
$fsTaxStr .= " THEN -1 * (";
for ($i = 0; $i < count($rates); $i++)
	$fsTaxStr .= "s.fsTax".$desc[$i]."+";
$fsTaxStr = substr($fsTaxStr,0,strlen($fsTaxStr)-1);
$fsTaxStr .= ") ELSE CASE ";
for ($i = 0; $i < count($rates); $i++){
	$fsTaxStr .= "WHEN -1*l.fsTendered ";
	for ($j = $i-1; $j >= 0; $j--)
		$fsTaxStr .= "-s.fsTaxable".$desc[$j];
	$fsTaxStr .= "<= s.fsTaxable".$desc[$i];
	$fsTaxStr .= " THEN -(";
	for ($j = $i-1; $j >= 0; $j--)
		$fsTaxStr .= "s.fsTax".$desc[$j]."+";
	$fsTaxStr .= "((-1*l.fsTendered ";
	for ($j = $i-1; $j >= 0; $j--)
		$fsTaxStr .= "-s.fsTaxable".$desc[$j];
	$fsTaxStr .= ") * ".$rates[$i]."))";
}
$fsTaxStr .= " END END END)";

if(count($rates) > 0){
	$createStr .= $fsTaxStr." as fsTaxExempt,";
	$createStr .= "convert(numeric(10,2),s.taxTotal+".$fsTaxStr.") as taxTotal,";
}
else {
	$createStr .= "0 as fsTaxExempt,";
	$createStr .= "0 as taxTotal,";
}
$createStr .= "s.scTaxTotal,";
$createStr .= "convert(numeric(10,2),(l.runningTotal - s.transdiscount) + s.taxTotal + ".$fsTaxStr.") as AmtDue,";
$createStr .= "convert(numeric(10,2),(l.runningTotal - s.sctransdiscount) + s.taxTotal + ".$fsTaxStr.") as scAmtDue,";
$createStr .= "s.scDiscount,
s.transDiscount,
s.scTransDiscount,
l.percentDiscount,
l.localTotal
from lttsummary l, lttsubtotals s where l.tdate = s.tdate";

$db->query("DROP VIEW subtotals");
$db->query($createStr);
$rpQ = str_replace("select","select l.emp_no,l.register_no,l.trans_no,",$createStr);
$rpQ = str_replace("lttsummary","rp_lttsummary",$rpQ);
$rpQ = str_replace("lttsubtotals","rp_lttsubtotals",$rpQ);
$rpQ = str_replace("view subtotals","view rp_subtotals",$rpQ);
$rpQ .= " AND l.emp_no=s.emp_no AND 
	l.register_no=s.register_no AND
	l.trans_no=s.trans_no";
$db->query("DROP VIEW rp_subtotals");
$db->query($rpQ);
//echo $createStr."<br />";
//echo "SubTotals view created<br />";

}

?>
