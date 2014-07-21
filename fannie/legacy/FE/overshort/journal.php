<?php
include('../../../config.php');

require($FANNIE_ROOT.'src/SQLManager.php');

include('../../db.php');
$sql->query('use is4c_trans');

$pCodes_lookup = array(
	41201 => "DELI PREPARED FOODS",
	41205 => "DELI CHEESE",
	41300 => "PRODUCE",
	41305 => "SEEDS",
	41310 => "TRANSPLANTS",
	41315 => "GEN MERC/FLOWERS",
	41400 => "GROCERY",
	41405 => "GROCERY CLEANING, PAPER",
	41407 => "GROCERY BULK WATER",
	41410 => "BULK A",
	41415 => "BULK B",
	41420 => "COOL",
	41425 => "COOL BUTTER",
	41430 => "COOL MILK",
	41435 => "COOL FROZEN",
	41500 => "HABA BULK/SPICES & HERBS",
	41505 => "HABA BULK/PKG COFFEE",
	41510 => "HABA BODY CARE",
	41515 => "HABA VIT/MN/HRB/HOMEOPA",
	41520 => "GEN MERC/BOOKS",
	41600 => "GROCERY BAKERY FROM VEN",
	41605 => "GEN MERC/HOUSEWARES",
	41610 => "MARKETING",
	41640 => "GEN MERC/CARDS",
	41645 => "GEN MERC/MAGAZINES",
	41700 => "MEAT/POULTRY/SEAFOOD FR",
	41705 => "MEAT/POULTRY/SEAFOOD FZ",
	42225 => "Class",
	42231 => "Misc #1",
	42232 => "Misc #2",
	64410 => "Supplies"
);

$tender_pcode_lookup = array(
	'CA' => 10110,
	'CC' => 10120,
	'AX' => 10120,
	'RR' => 63380,
	'CP' => 10740,
	'GD' => 21205,
	'TC' => 21200,
	'SC' => 21200,
	'MI' => 10710,
	'IC' => 67710,
	'MA' => 66600,
	'RR' => 63380,
	'EF' => 10120,
	'PP' => 10120
);

$double_lookup = array(
	600 => array('Supplies',64410),
	604 => array('Misc PO',''),
	900 => array('Gift Cert sold',''),
	990 => array('AR Payments',10710),
	10710 => array('AR Payments', 10710),
	991 => array('Class B Equity',31110),
	992 => array('Class A Equity',31100),
	700 => array('Totes',63320),
	701 => array('Donations','31130'),
	703 => array('Old Misc.',42230),
	902 => array('Gift Card Sales',21205),
	21205 => array('Gift Card Sales',21205),
	800 => array('IT Corrections',''),
	708 => array('Class',63350),
	610 => array('RRR Department',''),
	881 => array('Misc Inc #1',42231),
	882 => array('Misc Inc #2',42232)
);

if (isset($_GET["action"])){
	$out = $_GET["action"]."`";

	switch($_GET["action"]){
	case 'repull':
		$datestr = $_GET['startDate']." ".$_GET['endDate'];
		$prep = $sql->prepare("DELETE FROM dailyDebitCredit WHERE dateStr=?");
        $sql->execute($prep, array($datestr));
		$out .= $_GET['startDate']."`".$_GET['endDate'];
		break;
	case 'dateinput':
		$startDate = $_GET["startDate"];
		$endDate = $_GET["endDate"];
		$out .= sprintf("<a href=\"journal.php?excel=yes&datestr=%s\">Save to Excel</a>",
				$startDate." ".$endDate);	
		$out .= " | ";
		$out .= "<a href=\"\" onclick=\"repull('$startDate','$endDate');return false;\">Reload from POS</a>";
		$out .= display($startDate,$endDate);
		break;
	case 'dateinput2':
		$dateStr = $_GET['dateStr'];
		$temp = explode(" ",$dateStr);
		$startDate = $temp[0];
		$endDate = $startDate;
		if (count($temp) > 1)
			$endDate = $temp[1];
		$out .= sprintf("<a href=\"journal.php?excel=yes&datestr=%s\">Save to Excel</a>",
				$startDate." ".$endDate);	
		$out .= " | ";
		$out .= "<a href=\"\" onclick=\"repull('$startDate','$endDate');return false;\">Reload from POS</a>";
		$out .= display($startDate,$endDate);
		break;
	case 'save2':
		$datestr = $_GET['datestr'];
		$val = $_GET['val'];
		$k1 = $_GET['key1'];
		$k2 = $_GET['key2'];

		$prep = $sql->prepare("SELECT phpData FROM dailyDebitCredit WHERE dateStr=?");
        $dataR = $sql->execute($prep, array($datestr));
        $dataW = $sql->fetch_row($dataR);
        $data = unserialize($dataW['phpData']);

		$data[$k1][$k2] = $val;		
		$prep = $sql->prepare("UPDATE dailyDebitCredit SET phpData=? WHERE dateStr=?");
        $sql->execute($prep, array(serialize($data), $datestr));
		break;
	case 'save3':
		$datestr = $_GET['datestr'];
		$val = $_GET['val'];
		$k1 = $_GET['key1'];
		$k2 = $_GET['key2'];
		$k3 = $_GET['key3'];

		$prep = $sql->prepare("SELECT phpData FROM dailyDebitCredit WHERE dateStr=?");
        $dataR = $sql->execute($prep, array($datestr));
        $dataW = $sql->fetch_row($dataR);

		$data[$k1][$k2][$k3] = $val;		
		$prep = $sql->prepare("UPDATE dailyDebitCredit SET phpData=? WHERE dateStr=?");
        $sql->execute($prep, array(serialize($data), $datestr));
		break;
	case 'save4':
		$datestr = $_GET['datestr'];
		$val = $_GET['val'];
		$k1 = $_GET['key1'];
		$k2 = $_GET['key2'];
		$k3 = $_GET['key3'];
		$k4 = $_GET['key4'];

		$prep = $sql->prepare("SELECT phpData FROM dailyDebitCredit WHERE dateStr=?");
        $dataR = $sql->execute($prep, array($datestr));
        $dataW = $sql->fetch_row($dataR);

		$data[$k1][$k2][$k3][$k4] = $val;		
		$prep = $sql->prepare("UPDATE dailyDebitCredit SET phpData=? WHERE dateStr=?");
        $sql->execute($prep, array(serialize($data), $datestr));
		break;
	case 'saveMisc':
		$datestr = $_GET['datestr'];
		$val = $_GET['val'];
		$misc = $_GET['misc'];
		$ts = $_GET['ts'];
		$type = $_GET['type'];

		$prep = $sql->prepare("SELECT phpData FROM dailyDebitCredit WHERE dateStr=?");
        $dataR = $sql->execute($prep, array($datestr));
        $dataW = $sql->fetch_row($dataR);

		if ($type == 'sales')
			$data['other'][$misc][1][$ts] = $val;
		elseif ($type == 'pcode')
			$data['other'][$misc][0] = $val;

		$prep = $sql->prepare("UPDATE dailyDebitCredit SET phpData=? WHERE dateStr=?");
        $sql->execute($prep, array(serialize($data), $datestr));
		break;
	}

	echo $out;
	return;
}
elseif (isset($_GET['excel'])){
	$dates = explode(" ",$_GET["datestr"]);

	header('Content-Type: application/ms-excel');
	header("Content-Disposition: attachment; filename=\"journal $dates[0] to $dates[1].xls\"");

	echo display($dates[0],$dates[1],True);
	return;
}

function display($date1,$date2,$excel=False){
	global $sql,$pCodes_lookup,$tender_pcode_lookup,$double_lookup;

	$classes = array("one","two");
	$c = 0;

	$data = array();
	$dataP = $sql->prepare("SELECT phpData FROM dailyDebitCredit WHERE dateStr=?");
    $dataR = $sql->execute($dataP, array($date1.' '.$date2));
	if ($sql->num_rows($dataR) == 0){
		$data = fetch_data($date1,$date2);
		$saveP = $sql->prepare("INSERT INTO dailyDebitCredit (dateStr, phpData) VALUES (?, ?)");
        $saveR = $sql->execute($saveP, array($date1.' '.$date2, serialize($data)));
	}
	else {
        $dataW = $sql->fetch_row($dataR);
        $data = unserialize($dataW['phpData']);
	}

	$ret = "<table cellspacing=0 cellpadding=4 border=1>";
	$ret .= "<tr class=$classes[$c]>"; $c = ($c+1)%2;
	$ret .= "<td colspan=2>Sales Entries<br />$date1 through $date2</td>";

	if (!$excel)
		$ret .= "<input type=hidden id=datestr value=\"$date1 $date2\" />";

	$temp = explode("-",$date1);
	$startTS = mktime(0,0,0,$temp[1],$temp[2],$temp[0]);
	$sY = (int)$temp[0]; $sM = (int)$temp[1]; (int)$sD = $temp[2];
	$temp = explode("-",$date2);
	$endTS = mktime(0,0,0,$temp[1],$temp[2],$temp[0]);

	$num_days = round( ($endTS - $startTS) / (60*60*24) ) + 1;

	$stamps = "";
	$overshorts = array();
	for($i=0;$i<$num_days;$i++){
		$ts = mktime(0,0,0,$sM,$sD+$i,$sY);
		$stamps .= $ts.":";
		$overshorts[$ts] = 0;
	}
	$stamps = substr($stamps,strlen($stamps)-1);
	$ret .= "<input type=hidden id=timestamps value=\"$stamps\" />";

	for($i=0;$i<$num_days;$i++){
		$ret .= "<td colspan=2>Type: General<br />";
		$ret .= sprintf("Date: %s</td>",date("m/d/y",mktime(0,0,0,$sM,$sD+$i,$sY)));
	}
	$ret .= "</tr>";

	$ret .= "<tr class=$classes[$c]>"; $c = ($c+1)%2;
	$ret .= "<td class=heading>Description</td><td class=heading>Account</td>";
	for($i=0;$i<$num_days;$i++){
		$ret .= "<td class=heading>Debit</td><td class=heading>Credit</td>";
	}
	$ret .= "</tr>";	

	$ret .= "<tr class=$classes[$c]>"; $c = ($c+1)%2;
	$ret .= "<td>Cash deposit</td><td>10120</td>";
	for ($i=0;$i<$num_days-1;$i++)
		$ret .= "<td>&nbsp;</td><td>&nbsp;</td>";
	$ret .= "<td class=money>";
	if (!$excel){
		$ret .= "<input type=text size=7 value=\"";
		$ret .= (isset($data['other']['depositAmount'])?$data['other']['depositAmount']:'')."\"";
		$ret .= " onchange=\"save2(this.value,'other','depositAmount');rb($endTS);\" ";
		$ret .= "style=\"text-align:right\" name=debit$endTS />";
	}
	else
		$ret .= (isset($data['other']['depositAmount'])?$data['other']['depositAmount']:'');
	$overshorts[$endTS] += isset($data['other']['depositAmount'])?$data['other']['depositAmount']:0;
	
	$ret .= "</td>";
	$ret .= "<td>&nbsp;</td>";
	$ret .= "</tr>";

	$ret .= "<tr class=$classes[$c]>"; $c = ($c+1)%2;
	$ret .= "<td>Check deposit</td><td>10120</td>";
	for ($i=0;$i<$num_days-1;$i++)
		$ret .= "<td>&nbsp;</td><td>&nbsp;</td>";
	$ret .= "<td class=money>";
	if (!$excel){
		$ret .= "<input type=text size=7 value=\"";
		$ret .= (isset($data['other']['depositChecks'])?$data['other']['depositChecks']:'')."\"";
		$ret .= " onchange=\"save2(this.value,'other','depositChecks');rb($endTS);\" ";
		$ret .= "style=\"text-align:right\" name=debit$endTS />";
	}
	else
		$ret .= (isset($data['other']['depositChecks'])?$data['other']['depositChecks']:'');
	$overshorts[$endTS] += isset($data['other']['depositChecks'])?$data['other']['depositChecks']:0;

	$ret .= "</td>";
	$ret .= "<td>&nbsp;</td>";
	$ret .= "</tr>";

	$ret .= "<tr class=$classes[$c]>"; $c = ($c+1)%2;
	$ret .= "<td>ATM Cash</td><td>10112</td>";
	for ($i=0;$i<$num_days-1;$i++)
		$ret .= "<td>&nbsp;</td><td>&nbsp;</td>";
	$ret .= "<td class=money>";
	if (!$excel){
		$ret .= "<input type=text size=7 value=\"";
		$ret .= (isset($data['other']['atmNet'])?$data['other']['atmNet']:'')."\"";
		$ret .= " onchange=\"save2(this.value,'other','atmNet');rb($endTS);\" ";
		$ret .= "style=\"text-align:right\" name=debit$endTS />";
	}
	else
		$ret .= (isset($data['other']['atmNet'])?$data['other']['atmNet']:'');

	$ret .= "</td>";
	$ret .= "<td>&nbsp;</td>";
	$ret .= "</tr>";

	foreach($data['tenders'] as $k=>$v){
		if ($k == "SCA" || $k == "") continue;
		$ret .= "<tr class=$classes[$c]>"; $c = ($c+1)%2;
		$ret .= "<td>";
		$ret .= $v['name'];
		$ret .= "</td><td>";
		$ret .= $tender_pcode_lookup[$k];
		$ret .= "</td>";
		for($i=0;$i<$num_days;$i++){
			$ts = mktime(0,0,0,$sM,$sD+$i,$sY);
			if ($i==$num_days-1 && $k == "CA"){
				$v[$ts] = 0;
				$v[$ts] = array_sum($v)*-1;
			}	
			$ret .= "<td class=money>";	
			if (!isset($v[$ts])) $v[$ts] = 0;
			$v[$ts] = round($v[$ts],2);
			if ($v[$ts] >= 0){
				if (!$excel){
					$ret .= "<input type=text size=7 value=\"".$v[$ts]."\" ";
					$ret .= "onchange=\"save3(this.value,'tenders','$k','$ts');rb($ts);\" ";
					$ret .= "style=\"text-align:right\" name=debit$ts id=tender$ts$k />";
				}
				else 
					$ret .= $v[$ts];
				$overshorts[$ts] += $v[$ts];
			}
			else
				$ret .= "&nbsp;";
			$ret .= "</td><td class=money>";
			if ($v[$ts] < 0){
				if (!$excel){
					$ret .= "<input type=text size=7 value=\"".(-1*$v[$ts])."\" ";
					$ret .= "onchange=\"save3(this.value,'tenders','$k','$ts');rb($ts);\" ";
					$ret .= "style=\"text-align:right\" name=credit$ts id=tender$ts$k />";
				}
				else 
					$ret .= -1*$v[$ts];
				$overshorts[$ts] -= -1*$v[$ts];
			}
			else
				$ret .= "&nbsp;";
			$ret .= "</td>";
		}
		$ret .= "</tr>";
	}

	foreach($data['sales'] as $k=>$v){
		$ret .= "<tr class=$classes[$c]>"; $c = ($c+1)%2;
		if ($k > 40000){
			$ret .= "<td>";
			$ret .= isset($pCodes_lookup[$k])?$pCodes_lookup[$k]:'';
			$ret .= "</td>";
			$ret .= "<td>$k</td>";
		}
		else {
			$ret .= "<td>";
			$ret .= $double_lookup[$k][0];
			$ret .= "</td><td>";
			$ret .= $double_lookup[$k][1];
			$ret .= "</td>";	
		}
		for($i=0;$i<$num_days;$i++){
			$ts = mktime(0,0,0,$sM,$sD+$i,$sY);
			$ret .= "<td class=money>";	
			if (isset($v[$ts]) && $v[$ts] < 0){
				if (!$excel){
					$ret .= "<input type=text size=7 value=\"".sprintf("%.2f",-1*$v[$ts])."\" ";
					$ret .= " onchange=\"save3(this.value,'sales','$k','$ts');rb($ts);\" ";
					$ret .= "style=\"text-align:right\" name=debit$ts />";
				}
				else 
					$ret .= sprintf("%.2f",-1*$v[$ts]);
				$overshorts[$ts] += -1*$v[$ts];
			}
			else
				$ret .= "&nbsp;";
			$ret .= "</td><td class=money>";
			if (isset($v[$ts]) && $v[$ts] >= 0){
				if (!$excel){
					$ret .= "<input type=text size=7 value=\"".sprintf("%.2f",$v[$ts])."\" ";
					$ret .= "onchange=\"save3(this.value,'sales','$k','$ts');rb($ts);\" ";
					$ret .= "style=\"text-align:right\" name=credit$ts />";
				}
				else 
					$ret .= sprintf("%.2f",$v[$ts]);
				$overshorts[$ts] -= $v[$ts];
			}
			else
				$ret .= "&nbsp;";
			$ret .= "</td>";
		}
		$ret .= "</tr>";
	}
	
	foreach($data['other']['discount'] as $k=>$v){
		$ret .= "<tr class=$classes[$c]>"; $c = ($c+1)%2;
		$ret .= "<td>".$k."</td>";
		if ($k == "Member")
			$ret .= "<td>66600</td>";
		else
			$ret .= "<td>61170</td>";
		for($i=0;$i<$num_days;$i++){
			$ts = mktime(0,0,0,$sM,$sD+$i,$sY);
			$ret .= "<td class=money>";
			if (isset($v[$ts]) && $v[$ts] >= 0){
				if (!$excel){
					$ret .= "<input type=text size=7 value=\"".$v[$ts]."\" ";
					$ret .= "onchange=\"save4(this.value,'other','discount','$k','$ts');rb($ts);\" ";
					$ret .= "style=\"text-align:right\" name=debit$ts />";
				}
				else 
					$ret .= $v[$ts];
				$overshorts[$ts] += $v[$ts];
			}
			else
				$ret .= "&nbsp;";
			$ret .= "</td>";
			$ret .= "<td class=money>";
			if (isset($v[$ts]) && $v[$ts] < 0){
				if (!$excel){
					$ret .= "<input type=text size=7 value=\"".(-1*$v[$ts])."\" ";
					$ret .= "onchange=\"save4(this.value,'other','discount','$k','$ts');rb($ts);\" ";
					$ret .= "style=\"text-align:right\" name=credit$ts />";
				}
				else 
					$ret .= -1*$v[$ts];
				$overshorts[$ts] -= -1*$v[$ts];
			}
			else
				$ret .= "&nbsp;";
			$ret .= "</td>";
		}
		$ret .= "</tr>";
	}

	$ret .= "<tr class=$classes[$c]>"; $c = ($c+1)%2;
	$ret .= "<td>Sales Tax Collected</td><td>21180</td>";
	for($i=0;$i<$num_days;$i++){
		$ts = mktime(0,0,0,$sM,$sD+$i,$sY);
		$ret .= "<td class=money>";
		if (isset($data['other']['tax'][$ts]) && $data['other']['tax'][$ts] < 0){
			if (!$excel){
				$ret .= "<input type=text size=7 value=\"".sprintf("%.2f",-1*$data['other']['tax'][$ts])."\" ";
				$ret .= "onchange=\"save3(this.value,'other','tax','$ts');rb($ts);\" ";
				$ret .= "style=\"text-align:right\" name=debit$ts />";
			}
			else 
				$ret .= -1*$data['other']['tax'][$ts];
			$overshorts[$ts] += -1*$data['other']['tax'][$ts];
		}
		else
			$ret .= "&nbsp;";
		$ret .= "</td><td class=money>";
		if (isset($data['other']['tax'][$ts]) && $data['other']['tax'][$ts] >= 0){
			if (!$excel){
				$ret .= "<input type=text size=7 value=\"".sprintf("%.2f",$data['other']['tax'][$ts])."\" ";
				$ret .= "onchange=\"save3(this.value,'other','tax','$ts');rb($ts);\" ";
				$ret .= "style=\"text-align:right\" name=credit$ts />";
			}
			else 
				$ret .= $data['other']['tax'][$ts];
			$overshorts[$ts] -= $data['other']['tax'][$ts];
		}
		else
			$ret .= "&nbsp;";
		$ret .= "</td>";
	}
	$ret .= "</tr>";

	$ret .= "<tr class=$classes[$c]>"; $c = ($c+1)%2;
	$ret .= "<td>Gazette Ads</td><td>10730</td>";
	for ($i=0;$i<$num_days;$i++){
		$ts = mktime(0,0,0,$sM,$sD+$i,$sY);
		$ret .= "<td class=money>";
		$ret .= "</td><td class=money>";
		if (isset($data['other']['gazette'][$ts]) && $data['other']['gazette'][$ts] < 0){
			if (!$excel){
				$ret .= "<input type=text size=7 value=\"".(-1*$data['other']['gazette'][$ts])."\" ";
				$ret .= "onchange=\"save3(this.value,'other','gazette','$ts');rb($ts);\" ";
				$ret .= "style=\"text-align:right\" name=debit$ts />";
			}
			else 
				$ret .= -1*$data['other']['gazette'][$ts];
			$overshorts[$ts] += (-1*$data['other']['gazette'][$ts]);
		}
		else
			$ret .= "&nbsp;";
		if (isset($data['other']['gazette'][$ts]) && $data['other']['gazette'][$ts] >= 0){
			if (!$excel){
				$ret .= "<input type=text size=7 value=\"".$data['other']['gazette'][$ts]."\" ";
				$ret .= "onchange=\"save3(this.value,'other','gazette','$ts');rb($ts);\" ";
				$ret .= "style=\"text-align:right\" name=credit$ts />";
			}
			else 
				$ret .= $data['other']['gazette'][$ts];
			$overshorts[$ts] -= $data['other']['gazette'][$ts];
		}
		else
			$ret .= "&nbsp;";
		
	}
	$ret .= "</tr>";

	$ret .= "<tr class=$classes[$c]>"; $c = ($c+1)%2;
	$ret .= "<td>Found Money</td><td>63350</td>";
	for ($i=0;$i<$num_days;$i++){
		$ts = mktime(0,0,0,$sM,$sD+$i,$sY);
		$ret .= "<td class=money>";
		$ret .= "</td><td class=money>";
		if (isset($data['other']['foundmoney'][$ts]) && $data['other']['foundmoney'][$ts] < 0){
			if (!$excel){
				$ret .= "<input type=text size=7 value=\"".(-1*$data['other']['foundmoney'][$ts])."\" ";
				$ret .= "onchange=\"save3(this.value,'other','foundmoney','$ts');rb($ts);\" ";
				$ret .= "style=\"text-align:right\" name=debit$ts />";
			}
			else 
				$ret .= -1*$data['other']['foundmoney'][$ts];
			$overshorts[$ts] += (-1*$data['other']['foundmoney'][$ts]);
		}
		else
			$ret .= "&nbsp;";
		if (isset($data['other']['foundmoney'][$ts]) && $data['other']['foundmoney'][$ts] >= 0){
			if (!$excel){
				$ret .= "<input type=text size=7 value=\"".$data['other']['foundmoney'][$ts]."\" ";
				$ret .= "onchange=\"save3(this.value,'other','foundmoney','$ts');rb($ts);\" ";
				$ret .= "style=\"text-align:right\" name=credit$ts />";
			}
			else 
				$ret .= $data['other']['foundmoney'][$ts];
			$overshorts[$ts] -= $data['other']['foundmoney'][$ts];
		}
		else
			$ret .= "&nbsp;";
		
	}
	$ret .= "</tr>";

	$ret .= "<tr class=$classes[$c]>"; $c = ($c+1)%2;
	$ret .= "<td>Old Misc</td><td>";
	if (!$excel){
		$ret .= "<input type=text onchange=\"saveMisc(this.value,'misc0','0','pcode');\" ";
		$ret .= "value=\"".$data['other']['misc0'][0]."\" size=6 />";
	}
	else
		$ret .= $data['other']['misc0'][0];
	$ret .= "</td>";
	for ($i=0;$i<$num_days;$i++){
		$ts = mktime(0,0,0,$sM,$sD+$i,$sY);
		$ret .= "<td class=money>";
		if (isset($data['other']['misc0'][1][$ts]) && $data['other']['misc0'][1][$ts] < 0){
			if (!$excel){
				$ret .= "<input type=text size=7 value=\"".(-1*$data['other']['misc0'][1][$ts])."\" ";
				$ret .= "onchange=\"saveMisc(this.value,'misc0','$ts','sales');rb($ts);\" ";
				$ret .= "style=\"text-align:right\" name=debit$ts />";
			}
			else 
				$ret .= -1*$data['other']['misc0'][1][$ts];
			$overshorts[$ts] += (-1*$data['other']['misc0'][1][$ts]);
		}
		else
			$ret .= "&nbsp;";
		$ret .= "</td><td class=money>";
		if (isset($data['other']['misc0'][1][$ts]) && $data['other']['misc0'][1][$ts] >= 0){
			if (!$excel){
				$ret .= "<input type=text size=7 value=\"".$data['other']['misc0'][1][$ts]."\" ";
				$ret .= "onchange=\"saveMisc(this.value,'misc0','$ts','sales');rb($ts);\" ";
				$ret .= "style=\"text-align:right\" name=credit$ts />";
			}
			else 
				$ret .= $data['other']['misc0'][1][$ts];
			$overshorts[$ts] -= $data['other']['misc0'][1][$ts];
		}
		else
			$ret .= "&nbsp;";
	}
	$ret .= "</tr>";

	$ret .= "<tr class=$classes[$c]>"; $c = ($c+1)%2;
	$ret .= "<td>Misc #1</td><td>";
	if (!$excel){
		$ret .= "<input type=text onchange=\"saveMisc(this.value,'misc1','0','pcode');\" ";
		$ret .= "value=\"".$data['other']['misc1'][0]."\" size=6 />";
	}
	else
		$ret .= $data['other']['misc1'][0];
	$ret .= "</td>";
	for ($i=0;$i<$num_days;$i++){
		$ts = mktime(0,0,0,$sM,$sD+$i,$sY);
		$ret .= "<td class=money>";
		if (isset($data['other']['misc1'][1][$ts]) && $data['other']['misc1'][1][$ts] < 0){
			if (!$excel){
				$ret .= "<input type=text size=7 value=\"".(-1*$data['other']['misc1'][1][$ts])."\" ";
				$ret .= "onchange=\"saveMisc(this.value,'misc1','$ts','sales');rb($ts);\" ";
				$ret .= "style=\"text-align:right\" name=debit$ts />";
			}
			else 
				$ret .= -1*$data['other']['misc1'][1][$ts];
			$overshorts[$ts] += (-1*$data['other']['misc1'][1][$ts]);
		}
		else
			$ret .= "&nbsp;";
		$ret .= "</td><td class=money>";
		if (isset($data['other']['misc1'][1][$ts]) && $data['other']['misc1'][1][$ts] >= 0){
			if (!$excel){
				$ret .= "<input type=text size=7 value=\"".$data['other']['misc1'][1][$ts]."\" ";
				$ret .= "onchange=\"saveMisc(this.value,'misc1','$ts','sales');rb($ts);\" ";
				$ret .= "style=\"text-align:right\" name=credit$ts />";
			}
			else 
				$ret .= $data['other']['misc1'][1][$ts];
			$overshorts[$ts] -= $data['other']['misc1'][1][$ts];
		}
		else
			$ret .= "&nbsp;";
	}
	$ret .= "</tr>";

	$ret .= "<tr class=$classes[$c]>"; $c = ($c+1)%2;
	$ret .= "<td>Misc #2</td><td>";
	if (!$excel){
		$ret .= "<input type=text onchange=\"saveMisc(this.value,'misc2','0','pcode');\" ";
		$ret .= "value=\"".$data['other']['misc2'][0]."\" size=6 />";
	}
	else
		$ret .= $data['other']['misc2'][0];
	$ret .= "</td>";
	for ($i=0;$i<$num_days;$i++){
		$ts = mktime(0,0,0,$sM,$sD+$i,$sY);
		$ret .= "<td class=money>";
		if (isset($data['other']['misc2'][1][$ts]) && $data['other']['misc2'][1][$ts] < 0){
			if (!$excel){
				$ret .= "<input type=text size=7 value=\"".(-1*$data['other']['misc2'][1][$ts])."\" ";
				$ret .= "onchange=\"saveMisc(this.value,'misc2','$ts','sales');rb($ts);\" ";
				$ret .= "style=\"text-align:right\" name=debit$ts />";
			}
			else 
				$ret .= -1*$data['other']['misc2'][1][$ts];
			$overshorts[$ts] += (-1*$data['other']['misc2'][1][$ts]);
		}
		else
			$ret .= "&nbsp;";
		$ret .= "</td><td class=money>";
		if (isset($data['other']['misc2'][1][$ts]) && $data['other']['misc2'][1][$ts] >= 0){
			if (!$excel){
				$ret .= "<input type=text size=7 value=\"".$data['other']['misc2'][1][$ts]."\" ";
				$ret .= "onchange=\"saveMisc(this.value,'misc2','$ts','sales');rb($ts);\" ";
				$ret .= "style=\"text-align:right\" name=credit$ts />";
			}
			else 
				$ret .= $data['other']['misc2'][1][$ts];
			$overshorts[$ts] -= $data['other']['misc2'][1][$ts];
		}
		else
			$ret .= "&nbsp;";
		
	}
	$ret .= "</tr>";

	$ret .= "<tr class=$classes[$c]>"; $c = ($c+1)%2;
	$ret .= "<td>AMEX Fees</td><td>";
	$ret .= $data['other']['axfees'][0];
	$ret .= "</td>";
	for ($i=0;$i<$num_days;$i++){
		$ts = mktime(0,0,0,$sM,$sD+$i,$sY);
		$ret .= "<td class=money>";
		if (isset($data['other']['axfees'][1][$ts]) && $data['other']['axfees'][1][$ts] >= 0){
			if (!$excel){
				$ret .= "<input type=text size=7 value=\"".(-1*$data['other']['axfees'][1][$ts])."\" ";
				$ret .= "onchange=\"saveMisc(this.value,'axfees','$ts','sales');rb($ts);\" ";
				$ret .= "style=\"text-align:right\" name=debit$ts id=axfees$ts />";
			}
			else 
				$ret .= -1*$data['other']['axfees'][1][$ts];
			$overshorts[$ts] += (-1*$data['other']['axfees'][1][$ts]);
		}
		else
			$ret .= "&nbsp;";
		$ret .= "</td><td class=money>";
		if (isset($data['other']['axfees'][1][$ts]) && $data['other']['axfees'][1][$ts] < 0){
			if (!$excel){
				$ret .= "<input type=text size=7 value=\"".$data['other']['axfees'][1][$ts]."\" ";
				$ret .= "onchange=\"saveMisc(this.value,'axfees','$ts','sales');rb($ts);\" ";
				$ret .= "style=\"text-align:right\" name=credit$ts id=axfees$ts />";
			}
			else 
				$ret .= $data['other']['axfees'][1][$ts];
			$overshorts[$ts] -= $data['other']['axfees'][1][$ts];
		}
		else
			$ret .= "&nbsp;";
		
	}
	$ret .= "</tr>";

	$ret .= "<tr class=$classes[$c]>"; $c = ($c+1)%2;
	$ret .= "<td>Change Buy</td><td>10120</td>";
	for ($i=0;$i<$num_days-1;$i++)
		$ret .= "<td>&nbsp;</td><td>&nbsp;</td>";
	$ret .= "<td>&nbsp;</td>";
	$ret .= "<td class=money>";
	if (!$excel){
		$ret .= "<input type=text size=7 value=\"";
		$ret .= (isset($data['other']['buyAmount'])?$data['other']['buyAmount']:'')."\"";
		$ret .= " onchange=\"save2(this.value,'other','buyAmount');rb($endTS);\" ";
		$ret .= "style=\"text-align:right\" name=credit$endTS />";
	}
	else
		$ret .= (isset($data['other']['buyAmount'])?$data['other']['buyAmount']:'');
	$overshorts[$endTS] -= isset($data['other']['buyAmount'])?$data['other']['buyAmount']:0;
	$ret .= "</td>";
	$ret .= "</tr>";

	$ret .= "<tr class=$classes[$c]>"; $c = ($c+1)%2;
	$ret .= "<td>Over/Short</td><td>63350</td>";
	for($i=0;$i<$num_days;$i++){
		$ts = mktime(0,0,0,$sM,$sD+$i,$sY);
		$overshorts[$ts] = round($overshorts[$ts],2);
		$ret .= "<td class=money id=overshortDebit$ts>";
		if ($overshorts[$ts] < 0){
			$ret .= -1*$overshorts[$ts];
		}
		else
			$ret .= "&nbsp;";
		$ret .= "</td><td class=money id=overshortCredit$ts>";
		if ($overshorts[$ts] >= 0){
			$ret .= $overshorts[$ts];
		}
		else
			$ret .= "&nbsp;";
		$ret .= "</td>";
	}
	$ret .= "</tr>";


	$ret .= "</table>";
	return $ret;
}

function fetch_data($date1,$date2){
	global $sql,$pCodes_lookup;

	$data = array(
		'tenders'=>array(),
		'sales'=>array(),
		'other'=>array()	
	);
	$ret = "";
	$dlog = DTransactionsModel::selectDlog($date1,$date2);
	$dlog = "trans_archive.dlogBig";

    $default_args = array($date1.' '.$date2);

	$pageOneP = $sql->prepare("select rowName,sum(amt) from dailyDeposit
		WHERE dateStr = ? and
		rowName in ('depositAmount','buyAmount')
		group by rowName");
	$pageOneR = $sql->execute($pageOneP, $default_args);
	while($pageOneW = $sql->fetch_row($pageOneR)){
		$data['other'][$pageOneW[0]] = $pageOneW[1];
	}

	$data['other']['depositChecks'] = 0;
	$data['other']['atmNet'] = 0;
	$pageOneP2 = $sql->prepare("select denomination,amt FROM dailyDeposit
		WHERE dateStr = ? and
		rowName in ('depositAmount','atm')
		and denomination in ('Checks','fill','reject')");
	$pageOneR2 = $sql->execute($pageOneP2, $default_args);
	while($w = $sql->fetch_row($pageOneR2)){
		switch(strtolower($w['denomination'])){
			case 'checks':
				$data['other']['depositChecks'] += $w['amt'];
				$data['other']['depositAmount'] -= $w['amt'];
				break;
			case 'fill':
				$data['other']['atmNet'] += $w['amt'];
				break;
			case 'reject':
				$data['other']['atmNet'] -= $w['amt'];
				break;
		}
	}

    $date_args = array($date1.' 00:00:00', $date2.' 23:59:59');

	$tenderP = $sql->prepare("select sum(amt),
		tender_type,tendername,
		YEAR(date),MONTH(date),DAY(date)
		FROM dailyCounts as d left join is4c_op.tenders as t
		on d.tender_type = t.tenderCode
		WHERE date between ? AND ?
		group by
		YEAR(date),MONTH(date),DAY(date),
		tender_type,tendername order by tendername");
	$tenderR = $sql->execute($tenderP, $date_args);
	while ($tenderW = $sql->fetch_row($tenderR)){
		$y = $tenderW[3];
		$m = $tenderW[4];
		$d = $tenderW[5];	
		$timestamp = mktime(0,0,0,$m,$d,$y);

		$code = $tenderW[1];
		$name = $tenderW[2];
		if ($code == "SCA"){
			$code = "CA";
			$tenderW[0] *= -1;
		}
		if ($code == "CK") $code = "CA";
		if ($code == "CA") $name = "Coin, Cash, and Checks";
		if ($code == "EC") $code = "EF";
		if ($code == "CC") {
			$name = "Electronic Deposit (CC)";
		}
		if ($code == "EF"){
			$name = "Electronic Deposit (EBT)";
			$data['tenders']['AX']['name'] = 'Electronic Deposit (AMEX)';
		}

		if (!isset($data['tenders'][$code]))
			$data['tenders'][$code] = array();

		if (!isset($data['tenders'][$code][$timestamp]))
			$data['tenders'][$code][$timestamp] = 0;

		$data['tenders'][$code]['name'] = $name;
		$data['tenders'][$code][$timestamp] += $tenderW[0];
		//$data['tenders']['AX'][$timestamp] = 0;
	}

	$extraTenderP = $sql->prepare("select YEAR(tdate),MONTH(tdate),DAY(tdate),
			trans_subtype,sum(total)*-1 FROM $dlog as d
			WHERE tdate between ? AND ?
			and trans_subtype in ('MA','RR','PP')
			group by
			YEAR(tdate),MONTH(tdate),DAY(tdate),
			trans_subtype");
	$extraTenderR = $sql->execute($extraTenderP, $date_args);
	while($extraTenderW = $sql->fetch_row($extraTenderR)){
		$y = $extraTenderW[0];
		$m = $extraTenderW[1];
		$d = $extraTenderW[2];	
		$timestamp = mktime(0,0,0,$m,$d,$y);
		$code = $extraTenderW[3];

		if (!isset($data['tenders'][$code]))
			$data['tenders'][$code] = array();

		if (!isset($data['tenders'][$code][$timestamp]))
			$data['tenders'][$code][$timestamp] = 0;

		$name = "RRR Coupons";
		if ($code == "MA") $name = "Mad Coupons";
		if ($code == "PP") $name = "Pay Pal";

		$data['tenders'][$code]['name'] = $name;
		$data['tenders'][$code][$timestamp] += $extraTenderW[4];
	}
			
	// always include these
	$data['other']['misc0'] = array('',array());
	$data['other']['misc1'] = array('',array());
	$data['other']['misc2'] = array('',array());
	$data['other']['axfees'] = array('63340',array());

	$salesP = $sql->prepare("select YEAR(tdate),MONTH(tdate),DAY(tdate),
		CASE WHEN department = 991 then '991' when department=992 then '992' 
			else convert(t.salesCode,char) end as pcode,
		sum(total),trans_type
		FROM $dlog as d left join is4c_op.departments as t on
		d.department = t.dept_no 
		WHERE tdate BETWEEN ? AND ?
		AND trans_subtype NOT IN ('CP','IC')
		and trans_type not in ('S','T')
		AND (register_no <> 20 or department=703)
		GROUP BY 
		YEAR(tdate),MONTH(tdate),DAY(tdate),
		trans_type,
		CASE WHEN department = 991 then '991' when department=992 then '992' else convert(s.SalesCode,char) end
		ORDER BY
		CASE WHEN department = 991 then '991' when department=992 then '992' else convert(s.SalesCode,char) end");
	$salesR = $sql->execute($salesP, $date_args);
	$preTS = 0;
	while($salesW = $sql->fetch_row($salesR)){
		$y = $salesW[0];
		$m = $salesW[1];
		$d = $salesW[2];	
		$timestamp = mktime(0,0,0,$m,$d,$y);

		/* fill in zeroes for all pcodes */
		if ($timestamp != $preTS){
			foreach($pCodes_lookup as $k=>$v){
				if (!isset($data['sales'][$k]))
					$data['sales'][$k] = array();
				if (!isset($data['sales'][$k][$timestamp]))
					$data['sales'][$k][$timestamp] = 0;
			}
			$preTS = $timestamp;
		}

		if (!isset($data['other']['axfees'][1][$timestamp]))
			$data['other']['axfees'][1][$timestamp] = 0;
		$pcode = $salesW[3];	
		if ($pcode == "42231"){
			if (!isset($data['other']['misc1'][1][$timestamp]))
				$data['other']['misc1'][1][$timestamp] = 0;
			$data['other']['misc1'][1][$timestamp] += $salesW[4];
		}
		elseif ($pcode == "42232"){
			if (!isset($data['other']['misc2'][1][$timestamp]))
				$data['other']['misc2'][1][$timestamp] = 0;
			$data['other']['misc2'][1][$timestamp] += $salesW[4];
		}
		elseif ($pcode == "63350"){
			if (!isset($data['other']['misc0'][1][$timestamp]))
				$data['other']['misc0'][1][$timestamp] = 0;
			$data['other']['misc0'][1][$timestamp] += $salesW[4];
		}
		elseif ($pcode != 0){
			if (!isset($data['sales'][$pcode]))
				$data['sales'][$pcode] = array();
			if (!isset($data['sales'][$pcode][$timestamp]))
				$data['sales'][$pcode][$timestamp] = 0;
			$data['sales'][$pcode][$timestamp] += $salesW[4];
		}
		elseif ($salesW[5] == "A"){
			if (!isset($data['other']['tax']))
				$data['other']['tax'] = array();
			$data['other']['tax'][$timestamp] = $salesW[4];
		}
		$data['other']['gazette'][$timestamp] = 0;
		$data['other']['foundmoney'][$timestamp] = 0;
	}

	$discountP = $sql->prepare("select YEAR(tdate),MONTH(tdate),DAY(tdate),
		memDesc,-sum(total)
		from $dlog as d left join is4c_op.memTypeID as m on d.memtype=m.memtypeID
		where upc='DISCOUNT' and tdate between ? AND ?
		group by YEAR(tdate),MONTH(tdate),DAY(tdate),
		memDesc");
	$discountR = $sql->execute($discountP, $date_args);
	$data['other']['discount'] = array();
	while($discountW = $sql->fetch_row($discountR)){
		$y = $discountW[0];
		$m = $discountW[1];
		$d = $discountW[2];
		$ts = mktime(0,0,0,$m,$d,$y);
		
		$type = $discountW[3];
		if ($type == 'Non Member') $type = 'Staff Member';
		if (!isset($data['other']['discount'][$type]))
			$data['other']['discount'][$type] = array();
		if (!isset($data['other']['discount'][$type][$ts]))
			$data['other']['discount'][$type][$ts] = 0;
		$data['other']['discount'][$type][$ts] += $discountW[4];
	}

	return $data;
}

?>

<html>
<head>
	<title>Journal</title>
<script type=text/javascript src=journal.js></script>
<link href="<?php echo $FANNIE_URL; ?>src/style.css"
      rel="stylesheet" type="text/css">
<link href="<?php echo $FANNIE_URL; ?>src/javascript/jquery-ui.css"
      rel="stylesheet" type="text/css">
<script src="<?php echo $FANNIE_URL; ?>src/javascript/jquery.js"
        language="javascript"></script>
<script src="<?php echo $FANNIE_URL; ?>src/javascript/jquery-ui.js"
        language="javascript"></script>
<script type="text/javascript">
$(document).ready(function(){
    $('#startDate').datepicker();
    $('#endDate').datepicker();
});
</script>
</head>
<style type=text/css>
td.money {
	text-align: right;
	padding-left: 1em;
}
td.heading {
	text-align: center;
	
}
tr.one {
	background: #ffffcc;
}
tr.one input{
	background: #ffffcc;
}
tr.two {
	background: #ffffff;
}
a {
	color: blue;
}
#input {
	float: left;
}
#selecter {
	float: left;
	margin-left: 20px;
}
</style>
<body>

<div id=input>
<table>
<tr>
	<th>Start Date</th><td><input type=text id=startDate /></td>
</tr>
<tr>
	<th>End Date</th><td><input type=text id=endDate /></td>
</tr>
</tr>
</table>
<input type=submit Value=Load onclick="loader();" />
</div>
<div id=selecter>
<i>Or choose from calculated deposits</i><br />
<select id=selectDate>
<?php
$res = $sql->query("SELECT dateStr FROM dailyDeposit GROUP BY dateStr ORDER BY dateStr DESC");
while($row = $sql->fetch_row($res))
	echo "<option>".$row[0]."</option>";	
?>
</select> 
<input type=submit Value=Load onclick="loader2();" />
</div>
<div style="clear:left;"></div>

<hr />

<div id=display></div>

</body>
</html>
