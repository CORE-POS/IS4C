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

function buildLTTViews($db,$type,$errors=array()){
    return buildLTTViewsGeneric($db, $type, $errors);
}

/**
  9Nov15 Andy
  Keep temporarily for reference if anything's wrong
  with the generic version
function buildLTTViewsMySQL($db, $errors=array())
{

//--------------------------------------------------------------
// CREATE lttSummary VIEW
//--------------------------------------------------------------

$createStr = "CREATE view lttsummary as
    select 
    (case when min(datetime) is null then ".$db->now()." else min(datetime) end) as tdate,
    max(card_no) as card_no, 
    CAST(sum(total) AS decimal(10,2)) as runningTotal,
    CAST(sum(case when discounttype = 1 then discount else 0 end) AS decimal(10,2)) as discountTTL,
    CAST(sum(case when discountable <> 0 and tax <> 0 then total else 0 end) AS decimal(10,2)) as discTaxable,
    CAST(sum(case when discounttype in (2,3) then memDiscount else 0 end) AS decimal(10,2)) as memSpecial,
    CAST(sum(case when discounttype=4 THEN memDiscount ELSE 0 END) AS decimal(10,2)) as staffSpecial,
    CAST(sum(case when discountable = 0 then 0 else total end) AS decimal(10,2)) as discountableTTL,
    ";    

$taxRatesQ = "select id,description from taxrates order by id";
$taxRatesR = $db->query($taxRatesQ);
while ($taxRatesW = $db->fetch_row($taxRatesR)){
    $createStr .= "CAST(sum(case when (trans_type = 'I' or trans_type = 'D') and tax = ".$taxRatesW[0]." and discountable = 0 then total else 0 end) AS decimal(10,2)) as noDiscTaxable_".$taxRatesW[1].",\n";
    $createStr .= "CAST(sum(case when (trans_type = 'I' or trans_type = 'D') and tax = ".$taxRatesW[0]." and discountable <> 0 then total else 0 end) AS decimal(10,2)) as discTaxable_".$taxRatesW[1].",\n";
    $createStr .= "CAST(sum(case when (trans_type = 'I' or trans_type = 'D') and tax = ".$taxRatesW[0]." and discountable = 0 and foodstamp=1 then total else 0 end) AS decimal(10,2)) as fsTaxable_".$taxRatesW[1].",\n";
    $createStr .= "CAST(sum(case when (trans_type = 'I' or trans_type = 'D') and tax = ".$taxRatesW[0]." and discountable <> 0 and foodstamp=1 then total else 0 end) AS decimal(10,2)) as fsDiscTaxable_".$taxRatesW[1].",\n";
}

$createStr .= "
CAST(sum(case when trans_subtype = 'MI' or trans_subtype = 'CX'  then total else 0 end) AS decimal(10,2)) as chargeTotal,
CAST(sum(case when department = 990  then total else 0 end) AS decimal(10,2)) as paymentTotal,
CAST(sum(case when trans_type = 'T' and department = 0 then total else 0 end) AS decimal(10,2)) as tenderTotal,\n";
$createStr .= "CAST(sum(case when trans_subtype = 'FS' or trans_subtype = 'EF' then total else 0 end) AS decimal(10,2)) as fsTendered,
CAST(sum(case when foodstamp = 1 and discountable = 0 then total else 0 end) AS decimal(10,2)) as fsNoDiscTTL,
CAST(sum(case when foodstamp = 1 and discountable <> 0 then total else 0 end) AS decimal(10,2)) as fsDiscTTL,
(case when (max(percentDiscount) is null or max(percentDiscount) < 0) then 0.00 else max(CAST(percentDiscount AS decimal)) end) as percentDiscount,
CAST(sum(case when trans_status='V' THEN -total ELSE 0 END) AS decimal(10,2)) as voidTotal,
max(trans_id) as LastID
from localtemptrans WHERE trans_type <> 'L'\n";

$errors = \COREPOS\pos\install\db\Creator::dbStructureModify($db,'lttsummary','DROP VIEW lttsummary',$errors);
$errors = \COREPOS\pos\install\db\Creator::dbStructureModify($db,'lttsummary',$createStr,$errors);
$rpQ = str_replace("select","select emp_no,register_no,trans_no,",$createStr);
$rpQ = str_replace("localtemptrans","localtranstoday",$rpQ);
$rpQ = str_replace("lttsummary","rp_lttsummary",$rpQ);
$rpQ .= " AND datetime >= CURRENT_DATE GROUP BY emp_no,register_no,trans_no";
$errors = \COREPOS\pos\install\db\Creator::dbStructureModify($db,'rp_lttsummary','DROP VIEW rp_lttsummary',$errors);
$errors = \COREPOS\pos\install\db\Creator::dbStructureModify($db,'rp_lttsummary',$rpQ,$errors);
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
    $createStr .= "CAST(";
    for ($i = 0; $i < count($rates); $i++){
        $createStr .= "(noDiscTaxable_".$desc[$i]." * ".$rates[$i].") + ";
        $createStr .= "(discTaxable_".$desc[$i]." * ((100-percentDiscount)/100) * ".$rates[$i].") + ";
    }
    $createStr = substr($createStr,0,strlen($createStr)-2);
    $createStr .= " AS decimal(10,2)) as taxTotal,\n";
}
else $createStr .= "0 as taxTotal,\n";

$createStr .= "fsTendered,
CAST(fsTendered + fsNoDiscTTL + (fsDiscTTL * ((100-percentDiscount)/100)) AS  DECIMAL(10,2)) AS fsEligible,\n";
if(count($rates) > 0){
    for ($i = 0; $i < count($rates); $i++){
        $createStr .= "CAST((fsDiscTaxable_".$desc[$i]."*((100-percentDiscount)/100)) + fsTaxable_".$desc[$i]." AS decimal(10,2)) as fsTaxable_".$desc[$i].",";
        $createStr .= "CAST((fsDiscTaxable_".$desc[$i]."*((100-percentDiscount)/100)*".$rates[$i].")+(fsTaxable_".$desc[$i]."*".$rates[$i].") AS decimal(10,2)) as fsTax_".$desc[$i].",\n";
    }
}
else $createStr .= "0 as fsTax,\n";

$createStr .= "CAST(discountableTTL * percentDiscount / 100 AS decimal(10,2)) as transDiscount

from lttsummary\n";
$errors = \COREPOS\pos\install\db\Creator::dbStructureModify($db,'lttsubtotals','DROP VIEW lttsubtotals',$errors);
$errors = \COREPOS\pos\install\db\Creator::dbStructureModify($db,'lttsubtotals',$createStr,$errors);
$rpQ = str_replace("select","select emp_no,register_no,trans_no,",$createStr);
$rpQ = str_replace("lttsummary","rp_lttsummary",$rpQ);
$rpQ = str_replace("lttsubtotals","rp_lttsubtotals",$rpQ);
$errors = \COREPOS\pos\install\db\Creator::dbStructureModify($db,'rp_lttsubtotals','DROP VIEW rp_lttsubtotals',$errors);
$errors = \COREPOS\pos\install\db\Creator::dbStructureModify($db,'rp_lttsubtotals',$rpQ,$errors);
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
l.discountableTTL as discountableTotal,
l.tenderTotal as tenderTotal,
l.chargeTotal as chargeTotal,
l.paymentTotal as paymentTotal,
l.discountTTL as discountTTL,
l.memSpecial as memSpecial,
l.staffSpecial as staffSpecial,
s.fsEligible as fsEligible,\n";

list($desc, $rates) = getLttTaxRates($db);
$fsTaxStr = "CAST(" . fsTaxStr($desc, $rates) . " AS DECIMAL(10,2))";

if(count($rates) > 0){
    $createStr .= $fsTaxStr." as fsTaxExempt,\n";
    $createStr .= "CAST(s.taxTotal+".$fsTaxStr." AS decimal(10,2)) as taxTotal,\n";
}
else {
    $createStr .= "0 as fsTaxExempt,\n";
    $createStr .= "0 as taxTotal,\n";
}
$createStr .= "
s.transDiscount as transDiscount,
l.percentDiscount as percentDiscount,
l.voidTotal as voidTotal
from lttsummary l, lttsubtotals s where l.tdate = s.tdate\n";

$errors = \COREPOS\pos\install\db\Creator::dbStructureModify($db,'subtotals','DROP VIEW subtotals',$errors);
$errors = \COREPOS\pos\install\db\Creator::dbStructureModify($db,'subtotals',$createStr,$errors);
$rpQ = str_replace("select","select l.emp_no,l.register_no,l.trans_no,",$createStr);
$rpQ = str_replace("lttsummary","rp_lttsummary",$rpQ);
$rpQ = str_replace("lttsubtotals","rp_lttsubtotals",$rpQ);
$rpQ = str_replace("view subtotals","view rp_subtotals",$rpQ);
$rpQ .= " AND l.emp_no=s.emp_no AND 
    l.register_no=s.register_no AND
    l.trans_no=s.trans_no";
$errors = \COREPOS\pos\install\db\Creator::dbStructureModify($db,'rp_subtotals','DROP VIEW rp_subtotals',$errors);
$errors = \COREPOS\pos\install\db\Creator::dbStructureModify($db,'rp_subtotals',$rpQ,$errors);
//echo str_replace("\n","<br />",$createStr)."<br />";

return $errors;
}
*/

function buildLTTViewsGeneric($db, $type, $errors=array())
{
    //--------------------------------------------------------------
    // CREATE lttSummary VIEW
    //--------------------------------------------------------------

    $createStr = "CREATE view lttsummary as
        select 
        (case when min(datetime) is null then ".$db->now()." else min(datetime) end) as tdate,
        max(card_no) as card_no, 
        " . convertOrCast($type, '(sum(total))') . ' as runningTotal,
        ' . convertOrCast($type, '(sum(case when discounttype = 1 then discount else 0 end))') . ' as discountTTL,
        ' . convertOrCast($type, '(sum(case when discountable <> 0 and tax <> 0 then total else 0 end))') . ' as discTaxable,
        ' . convertOrCast($type, '(sum(case when discounttype in (2,3) then memDiscount else 0 end))') . ' as memSpecial,
        ' . convertOrCast($type, '(sum(case when discounttype=4 THEN memDiscount ELSE 0 END))') . ' as staffSpecial,
        ' . convertOrCast($type, '(sum(case when discountable = 0 then 0 else total end))') . ' as discountableTTL,
        ';    

    list($desc, $rates) = getLttTaxRates($db);
    for ($i=0; $i<count($rates); $i++) {
        $createStr .= convertOrCast($type, "(sum(case when (trans_type = 'I' or trans_type = 'D') and tax = ".$rates[$i]." and discountable = 0 then total else 0 end))") . " as noDiscTaxable_".$desc[$i].",\n";
        $createStr .= convertOrCast($type, "(sum(case when (trans_type = 'I' or trans_type = 'D') and tax = ".$rates[$i]." and discountable <> 0 then total else 0 end))") . " as discTaxable_".$desc[$i].",\n";
        $createStr .= convertOrCast($type, "(sum(case when (trans_type = 'I' or trans_type = 'D') and tax = ".$rates[$i]." and discountable = 0 and foodstamp=1 then total else 0 end))") . " as fsTaxable_".$desc[$i].",\n";
        $createStr .= convertOrCast($type, "(sum(case when (trans_type = 'I' or trans_type = 'D') and tax = ".$rates[$i]." and discountable <> 0 and foodstamp=1 then total else 0 end))") . " as fsDiscTaxable_".$desc[$i].",\n";
    }

    $ar_depts = COREPOS\pos\lib\MiscLib::getNumbers(CoreLocal::get('ArDepartments'));
    if (count($ar_depts) == 0) {
        $ar_depts = array(-999);
    }
    $ar_in = '';
    foreach ($ar_depts as $a) {
        $ar_in .= ((int)$a) . ',';
    }
    $ar_in = substr($ar_in, 0, strlen($ar_in)-1);

    $createStr .= "
    " . convertOrCast($type, "(sum(case when trans_subtype = 'MI' or trans_subtype = 'CX'  then total else 0 end))") . " as chargeTotal,
    " . convertOrCast($type, "(sum(case when department IN ({$ar_in}) then total else 0 end))") . " as paymentTotal,
    " . convertOrCast($type, "(sum(case when trans_type = 'T' and department = 0 then total else 0 end))") . " as tenderTotal,\n";
    $createStr .= 
    convertOrCast($type, "(sum(case when trans_subtype = 'FS' or trans_subtype = 'EF' then total else 0 end))") . " as fsTendered,
    " . convertOrCast($type, "(sum(case when foodstamp = 1 and discountable = 0 then total else 0 end))") . " as fsNoDiscTTL,
    " . convertOrCast($type, "(sum(case when foodstamp = 1 and discountable <> 0 then total else 0 end))") . " as fsDiscTTL,
    (case when (max(percentDiscount) is null or max(percentDiscount) < 0) then 0.00 else max(" . convertOrCast($type, 'percentDiscount') . ") end) as percentDiscount,
    " . convertOrCast($type, "(sum(case when trans_status='V' THEN -total ELSE 0 END))") . " as voidTotal,
    max(trans_id) as LastID
    from localtemptrans WHERE trans_type <> 'L'\n";

    $errors = \COREPOS\pos\install\db\Creator::dbStructureModify($db,'lttsummary','DROP VIEW lttsummary',$errors);
    $errors = \COREPOS\pos\install\db\Creator::dbStructureModify($db,'lttsummary',$createStr,$errors);
    $rpQ = str_replace("select","select emp_no,register_no,trans_no,",$createStr);
    $rpQ = str_replace("localtemptrans","localtranstoday",$rpQ);
    $rpQ = str_replace("lttsummary","rp_lttsummary",$rpQ);
    $rpQ .= " AND datetime >= " . $db->curdate() . " GROUP BY emp_no,register_no,trans_no";
    $errors = \COREPOS\pos\install\db\Creator::dbStructureModify($db,'rp_lttsummary','DROP VIEW rp_lttsummary',$errors);
    $errors = \COREPOS\pos\install\db\Creator::dbStructureModify($db,'rp_lttsummary',$rpQ,$errors);

    //--------------------------------------------------------------
    // CREATE lttSubTotals VIEW
    //--------------------------------------------------------------

    $createStr = "CREATE VIEW lttsubtotals AS
        select tdate,\n";
    if (count($rates) > 0){
        $expr = "";
        for ($i = 0; $i < count($rates); $i++){
            $expr .= "(noDiscTaxable_".$desc[$i]." * ".$rates[$i].") + ";
            $expr .= "(discTaxable_".$desc[$i]." * ((100-percentDiscount)/100) * ".$rates[$i].") + ";
        }
        $expr = substr($expr,0,strlen($expr)-2);
        $createStr .= convertOrCast($type, $expr) . " AS taxTotal,\n";
    }
    else $createStr .= "0 as taxTotal,\n";

    $createStr .= "fsTendered,
    " . convertOrCast($type, "(fsTendered + fsNoDiscTTL + (fsDiscTTL * ((100-percentDiscount)/100)))") . " AS fsEligible,\n";
    if(count($rates) > 0){
        for ($i = 0; $i < count($rates); $i++){
            $createStr .= convertOrCast($type, "((fsDiscTaxable_".$desc[$i]."*((100-percentDiscount)/100)) + fsTaxable_".$desc[$i].")") . " as fsTaxable_".$desc[$i].",";
            $createStr .= convertOrCast($type, "((fsDiscTaxable_".$desc[$i]."*((100-percentDiscount)/100)*".$rates[$i].")+(fsTaxable_".$desc[$i]."*".$rates[$i]."))") . " as fsTax_".$desc[$i].",\n";
        }
    }
    else $createStr .= "0 as fsTax,\n";

    $createStr .= convertOrCast($type, "(discountableTTL * percentDiscount / 100)") . " as transDiscount
    from lttsummary\n";
    $errors = \COREPOS\pos\install\db\Creator::dbStructureModify($db,'lttsubtotals','DROP VIEW lttsubtotals',$errors);
    $errors = \COREPOS\pos\install\db\Creator::dbStructureModify($db,'lttsubtotals',$createStr,$errors);
    $rpQ = str_replace("select","select emp_no,register_no,trans_no,",$createStr);
    $rpQ = str_replace("lttsummary","rp_lttsummary",$rpQ);
    $rpQ = str_replace("lttsubtotals","rp_lttsubtotals",$rpQ);
    $errors = \COREPOS\pos\install\db\Creator::dbStructureModify($db,'rp_lttsubtotals','DROP VIEW rp_lttsubtotals',$errors);
    $errors = \COREPOS\pos\install\db\Creator::dbStructureModify($db,'rp_lttsubtotals',$rpQ,$errors);

    //--------------------------------------------------------------
    // CREATE SubTotals VIEW
    //--------------------------------------------------------------

    $createStr = "CREATE view subtotals as
    select
    (case when l.LastID is null then 0 else l.LastID end) as LastID,
    l.card_no as card_no,
    l.runningTotal as runningTotal,
    l.discountableTTL as discountableTotal,
    l.tenderTotal as tenderTotal,
    l.chargeTotal as chargeTotal,
    l.paymentTotal as paymentTotal,
    l.discountTTL as discountTTL,
    l.memSpecial as memSpecial,
    l.staffSpecial as staffSpecial,
    s.fsEligible as fsEligible,\n";

    $fsTaxStr = convertOrCast($type, "(" . fsTaxStr($desc, $rates) . ")");

    if(count($rates) > 0){
        $createStr .= $fsTaxStr." as fsTaxExempt,\n";
        $createStr .= convertOrCast($type, "(s.taxTotal+".$fsTaxStr.")") . " as taxTotal,\n";
    } else {
        $createStr .= "0 as fsTaxExempt,\n";
        $createStr .= "0 as taxTotal,\n";
    }
    $createStr .= "
    s.transDiscount as transDiscount,
    l.percentDiscount as percentDiscount,
    l.voidTotal as voidTotal
    from lttsummary l, lttsubtotals s where l.tdate = s.tdate\n";

    $errors = \COREPOS\pos\install\db\Creator::dbStructureModify($db,'subtotals','DROP VIEW subtotals',$errors);
    $errors = \COREPOS\pos\install\db\Creator::dbStructureModify($db,'subtotals',$createStr,$errors);
    $rpQ = str_replace("select","select l.emp_no,l.register_no,l.trans_no,",$createStr);
    $rpQ = str_replace("lttsummary","rp_lttsummary",$rpQ);
    $rpQ = str_replace("lttsubtotals","rp_lttsubtotals",$rpQ);
    $rpQ = str_replace("view subtotals","view rp_subtotals",$rpQ);
    $rpQ .= " AND l.emp_no=s.emp_no AND 
        l.register_no=s.register_no AND
        l.trans_no=s.trans_no";
    $errors = \COREPOS\pos\install\db\Creator::dbStructureModify($db,'rp_subtotals','DROP VIEW rp_subtotals',$errors);
    $errors = \COREPOS\pos\install\db\Creator::dbStructureModify($db,'rp_subtotals',$rpQ,$errors);

    return $errors;
}

function getLttTaxRates($dbc)
{
    $ratesQ = "select description,rate from taxrates order by rate desc";
    $ratesR = $dbc->query($ratesQ);
    $desc =  array();
    $rates = array();
    while ($ratesW = $dbc->fetch_row($ratesR)){
        $desc[] = $ratesW[0];
        $rates[] = $ratesW[1];
    }

    return array($desc, $rates);
}

function fsTaxStr($desc, $rates)
{
    $fsTaxStr = "CASE WHEN ";
    for ($i = 0; $i < count($rates); $i++)
        $fsTaxStr .= "s.fsTaxable_".$desc[$i]."+";
    $fsTaxStr = substr($fsTaxStr,0,strlen($fsTaxStr)-1);
    $fsTaxStr .= " = 0 THEN 0 ELSE CASE WHEN l.fsTendered <> 0 AND -1 * l.fsTendered >= ";
    for ($i = 0; $i < count($rates); $i++)
        $fsTaxStr .= "s.fsTaxable_".$desc[$i]."+";
    $fsTaxStr = substr($fsTaxStr,0,strlen($fsTaxStr)-1);
    $fsTaxStr .= " THEN -1 * (";
    for ($i = 0; $i < count($rates); $i++)
        $fsTaxStr .= "s.fsTax_".$desc[$i]."+";
    $fsTaxStr = substr($fsTaxStr,0,strlen($fsTaxStr)-1);
    $fsTaxStr .= ") ELSE CASE ";
    for ($i = 0; $i < count($rates); $i++){
        $fsTaxStr .= "WHEN -1*l.fsTendered ";
        for ($j = $i-1; $j >= 0; $j--)
            $fsTaxStr .= "-s.fsTaxable_".$desc[$j];
        $fsTaxStr .= "<= s.fsTaxable_".$desc[$i];
        $fsTaxStr .= " THEN -(";
        for ($j = $i-1; $j >= 0; $j--)
            $fsTaxStr .= "s.fsTax_".$desc[$j]."+";
        $fsTaxStr .= "((-1*l.fsTendered ";
        for ($j = $i-1; $j >= 0; $j--)
            $fsTaxStr .= "-s.fsTaxable_".$desc[$j];
        $fsTaxStr .= ") * ".$rates[$i]."))";
    }
    $fsTaxStr .= " ELSE 0 ";
    $fsTaxStr .= " END END END\n";

    return $fsTaxStr;
}

function convertOrCast($dbms, $expr)
{
    if (strstr($dbms, 'mssql')) {
        return 'CONVERT(NUMERIC(10,2), ' . $expr . ')';
    } else {
        return 'CAST(' . $expr . ' AS DECIMAL(10,2))';
    }
}

