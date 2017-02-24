<?php
use COREPOS\Fannie\API\item\StandardAccounting;

include('../../../config.php');

require($FANNIE_ROOT.'src/SQLManager.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}

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

if (filter_input(INPUT_GET, 'action')) {
    $out = filter_input(INPUT_GET, 'action') . '`';

    switch(filter_input(INPUT_GET, 'action')) {
    case 'repull':
        $datestr = filter_input(INPUT_GET, 'startDate')." ".filter_input(INPUT_GET, 'endDate');
        $prep = $sql->prepare("DELETE FROM dailyDebitCredit WHERE dateStr=?");
        $sql->execute($prep, array($datestr));
        $datestr = filter_input(INPUT_GET, 'startDate')."`".filter_input(INPUT_GET, 'endDate');
        break;
    case 'dateinput':
        $startDate = filter_input(INPUT_GET, "startDate");
        $endDate = filter_input(INPUT_GET, "endDate");
        $out .= sprintf("<a href=\"journal.php?excel=yes&datestr=%s\">Save to Excel</a>",
                $startDate." ".$endDate);    
        $out .= " | ";
        $out .= "<a href=\"\" onclick=\"repull('$startDate','$endDate');return false;\">Reload from POS</a>";
        $out .= display($startDate,$endDate);
        break;
    case 'dateinput2':
        $dateStr = filter_input(INPUT_GET, 'dateStr');
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
        $datestr = filter_input(INPUT_GET, 'datestr');
        $val = filter_input(INPUT_GET, 'val');
        $key1 = filter_input(INPUT_GET, 'key1');
        $key2 = filter_input(INPUT_GET, 'key2');

        $prep = $sql->prepare("SELECT phpData FROM dailyDebitCredit WHERE dateStr=?");
        $dataR = $sql->execute($prep, array($datestr));
        $dataW = $sql->fetch_row($dataR);
        $data = unserialize($dataW['phpData']);

        $data[$key1][$key2] = $val;        
        $prep = $sql->prepare("UPDATE dailyDebitCredit SET phpData=? WHERE dateStr=?");
        $sql->execute($prep, array(serialize($data), $datestr));
        break;
    case 'save3':
        $datestr = filter_input(INPUT_GET, 'datestr');
        $val = filter_input(INPUT_GET, 'val');
        $key1 = filter_input(INPUT_GET, 'key1');
        $key2 = filter_input(INPUT_GET, 'key2');
        $key3 = filter_input(INPUT_GET, 'key3');

        $prep = $sql->prepare("SELECT phpData FROM dailyDebitCredit WHERE dateStr=?");
        $dataR = $sql->execute($prep, array($datestr));
        $dataW = $sql->fetch_row($dataR);

        $data[$key1][$key2][$key3] = $val;        
        $prep = $sql->prepare("UPDATE dailyDebitCredit SET phpData=? WHERE dateStr=?");
        $sql->execute($prep, array(serialize($data), $datestr));
        break;
    case 'save4':
        $datestr = filter_input(INPUT_GET, 'datestr');
        $val = filter_input(INPUT_GET, 'val');
        $key1 = filter_input(INPUT_GET, 'key1');
        $key2 = filter_input(INPUT_GET, 'key2');
        $key3 = filter_input(INPUT_GET, 'key3');
        $key4 = filter_input(INPUT_GET, 'key4');

        $prep = $sql->prepare("SELECT phpData FROM dailyDebitCredit WHERE dateStr=?");
        $dataR = $sql->execute($prep, array($datestr));
        $dataW = $sql->fetch_row($dataR);

        $data[$key1][$key2][$key3][$key4] = $val;        
        $prep = $sql->prepare("UPDATE dailyDebitCredit SET phpData=? WHERE dateStr=?");
        $sql->execute($prep, array(serialize($data), $datestr));
        break;
    case 'saveMisc':
        $datestr = filter_input(INPUT_GET, 'datestr');
        $val = filter_input(INPUT_GET, 'val');
        $misc = filter_input(INPUT_GET, 'misc');
        $tstamp = filter_input(INPUT_GET, 'ts');
        $type = filter_input(INPUT_GET, 'type');

        $prep = $sql->prepare("SELECT phpData FROM dailyDebitCredit WHERE dateStr=?");
        $dataR = $sql->execute($prep, array($datestr));
        $dataW = $sql->fetch_row($dataR);

        if ($type == 'sales')
            $data['other'][$misc][1][$tstamp] = $val;
        elseif ($type == 'pcode')
            $data['other'][$misc][0] = $val;

        $prep = $sql->prepare("UPDATE dailyDebitCredit SET phpData=? WHERE dateStr=?");
        $sql->execute($prep, array(serialize($data), $datestr));
        break;
    }

    echo $out;
    return;
} elseif (filter_input(INPUT_GET, 'excel')) {
    $dates = explode(" ", filter_input(INPUT_GET, 'datestr'));

    header('Content-Type: application/ms-excel');
    header("Content-Disposition: attachment; filename=\"journal $dates[0] to $dates[1].xls\"");

    echo display($dates[0],$dates[1],True);
    return;
}

function display($date1,$date2,$excel=False){
    global $sql,$pCodes_lookup,$tender_pcode_lookup,$double_lookup;

    $classes = array("one","two");
    $cur = 0;

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
    $ret .= "<tr class=$classes[$cur]>"; $cur = ($cur+1)%2;
    $ret .= "<td colspan=2>Sales Entries<br />$date1 through $date2</td>";

    if (!$excel)
        $ret .= "<input type=hidden id=datestr value=\"$date1 $date2\" />";

    $temp = explode("-",$date1);
    $startTS = mktime(0,0,0,$temp[1],$temp[2],$temp[0]);
    $sYear = (int)$temp[0]; $sMonth = (int)$temp[1]; (int)$sDay = $temp[2];
    $temp = explode("-",$date2);
    $endTS = mktime(0,0,0,$temp[1],$temp[2],$temp[0]);

    $num_days = round( ($endTS - $startTS) / (60*60*24) ) + 1;

    $stamps = "";
    $overshorts = array();
    for($i=0;$i<$num_days;$i++){
        $tstamp = mktime(0,0,0,$sMonth,$sDay+$i,$sYear);
        $stamps .= $tstamp.":";
        $overshorts[$tstamp] = 0;
    }
    $stamps = substr($stamps,strlen($stamps)-1);
    $ret .= "<input type=hidden id=timestamps value=\"$stamps\" />";

    for($i=0;$i<$num_days;$i++){
        $ret .= "<td colspan=2>Type: General<br />";
        $ret .= sprintf("Date: %s</td>",date("m/d/y",mktime(0,0,0,$sMonth,$sDay+$i,$sYear)));
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
    $amt = isset($data['other']['depositAmount']) ? $data['other']['depositAmount'] : '';
    $ret .= inputTypeOther(array('other','depositAmount','debit'), $amt, $excel, $endTS);
    $overshorts[$endTS] += $amt === '' ? 0 : $amt;
    
    $ret .= "</td>";
    $ret .= "<td>&nbsp;</td>";
    $ret .= "</tr>";

    $ret .= "<tr class=$classes[$c]>"; $c = ($c+1)%2;
    $ret .= "<td>Check deposit</td><td>10120</td>";
    for ($i=0;$i<$num_days-1;$i++)
        $ret .= "<td>&nbsp;</td><td>&nbsp;</td>";
    $ret .= "<td class=money>";
    $amt = isset($data['other']['depositChecks']) ? $data['other']['depositChecks'] : '';
    $ret .= inputTypeOther(array('other','depositChecks','debit'), $amt, $excel, $endTS);
    $overshorts[$endTS] += $amt === '' ? 0 : $amt;

    $ret .= "</td>";
    $ret .= "<td>&nbsp;</td>";
    $ret .= "</tr>";

    $ret .= "<tr class=$classes[$c]>"; $c = ($c+1)%2;
    $ret .= "<td>ATM Cash</td><td>10112</td>";
    for ($i=0;$i<$num_days-1;$i++)
        $ret .= "<td>&nbsp;</td><td>&nbsp;</td>";
    $ret .= "<td class=money>";
    $amt = isset($data['other']['atmNet']) ? $data['other']['atmNet'] : '';
    $ret .= inputTypeOther(array('other','atmNet','debit'), $amt, $excel, $endTS);
    $overshorts[$endTS] += $amt === '' ? 0 : $amt;

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
            $tstamp = mktime(0,0,0,$sMonth,$sDay+$i,$sYear);
            if ($i==$num_days-1 && $k == "CA"){
                $v[$tstamp] = 0;
                $v[$tstamp] = array_sum($v)*-1;
            }    
            $ret .= "<td class=money>";    
            if (!isset($v[$tstamp])) $v[$tstamp] = 0;
            $v[$tstamp] = round($v[$tstamp],2);
            if ($v[$tstamp] >= 0){
                if (!$excel){
                    $ret .= "<input type=text size=7 value=\"".$v[$tstamp]."\" ";
                    $ret .= "onchange=\"save3(this.value,'tenders','$k','$tstamp');rb($tstamp);\" ";
                    $ret .= "style=\"text-align:right\" name=debit$tstamp id=tender$tstamp$k />";
                }
                else 
                    $ret .= $v[$tstamp];
                $overshorts[$tstamp] += $v[$tstamp];
            }
            else
                $ret .= "&nbsp;";
            $ret .= "</td><td class=money>";
            if ($v[$tstamp] < 0){
                if (!$excel){
                    $ret .= "<input type=text size=7 value=\"".(-1*$v[$tstamp])."\" ";
                    $ret .= "onchange=\"save3(this.value,'tenders','$k','$tstamp');rb($tstamp);\" ";
                    $ret .= "style=\"text-align:right\" name=credit$tstamp id=tender$tstamp$k />";
                }
                else 
                    $ret .= -1*$v[$tstamp];
                $overshorts[$tstamp] -= -1*$v[$tstamp];
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
            $key = substr($k, 0, 5);
            $ret .= isset($pCodes_lookup[$key])?$pCodes_lookup[$key]:'';
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
            $tstamp = mktime(0,0,0,$sMonth,$sDay+$i,$sYear);
            $ret .= "<td class=money>";    
            if (isset($v[$tstamp]) && $v[$tstamp] < 0){
                if (!$excel){
                    $ret .= "<input type=text size=7 value=\"".sprintf("%.2f",-1*$v[$tstamp])."\" ";
                    $ret .= " onchange=\"save3(this.value,'sales','$k','$tstamp');rb($tstamp);\" ";
                    $ret .= "style=\"text-align:right\" name=debit$tstamp />";
                }
                else 
                    $ret .= sprintf("%.2f",-1*$v[$tstamp]);
                $overshorts[$tstamp] += -1*$v[$tstamp];
            }
            else
                $ret .= "&nbsp;";
            $ret .= "</td><td class=money>";
            if (isset($v[$tstamp]) && $v[$tstamp] >= 0){
                if (!$excel){
                    $ret .= "<input type=text size=7 value=\"".sprintf("%.2f",$v[$tstamp])."\" ";
                    $ret .= "onchange=\"save3(this.value,'sales','$k','$tstamp');rb($tstamp);\" ";
                    $ret .= "style=\"text-align:right\" name=credit$tstamp />";
                }
                else 
                    $ret .= sprintf("%.2f",$v[$tstamp]);
                $overshorts[$tstamp] -= $v[$tstamp];
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
            $tstamp = mktime(0,0,0,$sMonth,$sDay+$i,$sYear);
            $ret .= "<td class=money>";
            if (isset($v[$tstamp]) && $v[$tstamp] >= 0){
                if (!$excel){
                    $ret .= "<input type=text size=7 value=\"".$v[$tstamp]."\" ";
                    $ret .= "onchange=\"save4(this.value,'other','discount','$k','$tstamp');rb($tstamp);\" ";
                    $ret .= "style=\"text-align:right\" name=debit$tstamp />";
                }
                else 
                    $ret .= $v[$tstamp];
                $overshorts[$tstamp] += $v[$tstamp];
            }
            else
                $ret .= "&nbsp;";
            $ret .= "</td>";
            $ret .= "<td class=money>";
            if (isset($v[$tstamp]) && $v[$tstamp] < 0){
                if (!$excel){
                    $ret .= "<input type=text size=7 value=\"".(-1*$v[$tstamp])."\" ";
                    $ret .= "onchange=\"save4(this.value,'other','discount','$k','$tstamp');rb($tstamp);\" ";
                    $ret .= "style=\"text-align:right\" name=credit$tstamp />";
                }
                else 
                    $ret .= -1*$v[$tstamp];
                $overshorts[$tstamp] -= -1*$v[$tstamp];
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
        $tstamp = mktime(0,0,0,$sMonth,$sDay+$i,$sYear);
        $ret .= "<td class=money>";
        if (isset($data['other']['tax'][$tstamp]) && $data['other']['tax'][$tstamp] < 0){
            if (!$excel){
                $ret .= "<input type=text size=7 value=\"".sprintf("%.2f",-1*$data['other']['tax'][$tstamp])."\" ";
                $ret .= "onchange=\"save3(this.value,'other','tax','$tstamp');rb($tstamp);\" ";
                $ret .= "style=\"text-align:right\" name=debit$tstamp />";
            }
            else 
                $ret .= -1*$data['other']['tax'][$tstamp];
            $overshorts[$tstamp] += -1*$data['other']['tax'][$tstamp];
        }
        else
            $ret .= "&nbsp;";
        $ret .= "</td><td class=money>";
        if (isset($data['other']['tax'][$tstamp]) && $data['other']['tax'][$tstamp] >= 0){
            if (!$excel){
                $ret .= "<input type=text size=7 value=\"".sprintf("%.2f",$data['other']['tax'][$tstamp])."\" ";
                $ret .= "onchange=\"save3(this.value,'other','tax','$tstamp');rb($tstamp);\" ";
                $ret .= "style=\"text-align:right\" name=credit$tstamp />";
            }
            else 
                $ret .= $data['other']['tax'][$tstamp];
            $overshorts[$tstamp] -= $data['other']['tax'][$tstamp];
        }
        else
            $ret .= "&nbsp;";
        $ret .= "</td>";
    }
    $ret .= "</tr>";

    $other = array(
        'gazette' => array('Gazette Ads', 10730),
        'foundmoney' => array('Found Money', 63350),
    );
    foreach ($other as $key => $pair) {
        $ret .= "<tr class=$classes[$c]>"; $c = ($c+1)%2;
        $ret .= "<td>{$pair[0]}</td><td>{$pair[1]}</td>";
        for ($i=0;$i<$num_days;$i++){
            $tstamp = mktime(0,0,0,$sMonth,$sDay+$i,$sYear);
            $ret .= "<td class=money>";
            $ret .= "</td><td class=money>";
            if (isset($data['other'][$key][$tstamp]) && $data['other'][$key][$tstamp] < 0){
                if (!$excel){
                    $ret .= "<input type=text size=7 value=\"".(-1*$data['other'][$key][$tstamp])."\" ";
                    $ret .= "onchange=\"save3(this.value,'other','{$key}','$tstamp');rb($tstamp);\" ";
                    $ret .= "style=\"text-align:right\" name=debit$tstamp />";
                }
                else 
                    $ret .= -1*$data['other'][$key][$tstamp];
                $overshorts[$tstamp] += (-1*$data['other'][$key][$tstamp]);
            }
            else
                $ret .= "&nbsp;";
            if (isset($data['other'][$key][$tstamp]) && $data['other'][$key][$tstamp] >= 0){
                if (!$excel){
                    $ret .= "<input type=text size=7 value=\"".$data['other'][$key][$tstamp]."\" ";
                    $ret .= "onchange=\"save3(this.value,'other','{$key}','$tstamp');rb($tstamp);\" ";
                    $ret .= "style=\"text-align:right\" name=credit$tstamp />";
                }
                else 
                    $ret .= $data['other'][$key][$tstamp];
                $overshorts[$tstamp] -= $data['other'][$key][$tstamp];
            }
            else
                $ret .= "&nbsp;";
            
        }
        $ret .= "</tr>";
    }

    $misc = array(
        'misc0' => 'Old Misc',
        'misc1' => 'Misc #1',
        'misc2' => 'Misc #2',
    );

    foreach ($misc as $key => $label) {
        $ret .= "<tr class=$classes[$c]>"; $c = ($c+1)%2;
        $ret .= "<td>{$label}</td><td>";
        if (!$excel){
            $ret .= "<input type=text onchange=\"saveMisc(this.value,'{$key}','0','pcode');\" ";
            $ret .= "value=\"".$data['other'][$key][0]."\" size=6 />";
        } else {
            $ret .= $data['other'][$key][0];
        }
        $ret .= "</td>";
        for ($i=0;$i<$num_days;$i++){
            $tstamp = mktime(0,0,0,$sMonth,$sDay+$i,$sYear);
            $ret .= "<td class=money>";
            if (isset($data['other'][$key][1][$tstamp]) && $data['other'][$key][1][$tstamp] < 0){
                if (!$excel){
                    $ret .= "<input type=text size=7 value=\"".(-1*$data['other'][$key][1][$tstamp])."\" ";
                    $ret .= "onchange=\"saveMisc(this.value,'{$key}','$tstamp','sales');rb($tstamp);\" ";
                    $ret .= "style=\"text-align:right\" name=debit$tstamp />";
                } else {
                    $ret .= -1*$data['other'][$key][1][$tstamp];
                }
                $overshorts[$tstamp] += (-1*$data['other'][$key][1][$tstamp]);
            } else {
                $ret .= "&nbsp;";
            }
            $ret .= "</td><td class=money>";
            if (isset($data['other'][$key][1][$tstamp]) && $data['other'][$key][1][$tstamp] >= 0){
                if (!$excel){
                    $ret .= "<input type=text size=7 value=\"".$data['other'][$key][1][$tstamp]."\" ";
                    $ret .= "onchange=\"saveMisc(this.value,'{$key}','$tstamp','sales');rb($tstamp);\" ";
                    $ret .= "style=\"text-align:right\" name=credit$tstamp />";
                } else {
                    $ret .= $data['other'][$key][1][$tstamp];
                }
                $overshorts[$tstamp] -= $data['other'][$key][1][$tstamp];
            } else {
                $ret .= "&nbsp;";
            }
        }
        $ret .= "</tr>";
    }

    $ret .= "<tr class=$classes[$c]>"; $c = ($c+1)%2;
    $ret .= "<td>AMEX Fees</td><td>";
    $ret .= $data['other']['axfees'][0];
    $ret .= "</td>";
    for ($i=0;$i<$num_days;$i++){
        $tstamp = mktime(0,0,0,$sMonth,$sDay+$i,$sYear);
        $ret .= "<td class=money>";
        if (isset($data['other']['axfees'][1][$tstamp]) && $data['other']['axfees'][1][$tstamp] >= 0){
            if (!$excel){
                $ret .= "<input type=text size=7 value=\"".(-1*$data['other']['axfees'][1][$tstamp])."\" ";
                $ret .= "onchange=\"saveMisc(this.value,'axfees','$tstamp','sales');rb($tstamp);\" ";
                $ret .= "style=\"text-align:right\" name=debit$tstamp id=axfees$tstamp />";
            }
            else 
                $ret .= -1*$data['other']['axfees'][1][$tstamp];
            $overshorts[$tstamp] += (-1*$data['other']['axfees'][1][$tstamp]);
        }
        else
            $ret .= "&nbsp;";
        $ret .= "</td><td class=money>";
        if (isset($data['other']['axfees'][1][$tstamp]) && $data['other']['axfees'][1][$tstamp] < 0){
            if (!$excel){
                $ret .= "<input type=text size=7 value=\"".$data['other']['axfees'][1][$tstamp]."\" ";
                $ret .= "onchange=\"saveMisc(this.value,'axfees','$tstamp','sales');rb($tstamp);\" ";
                $ret .= "style=\"text-align:right\" name=credit$tstamp id=axfees$tstamp />";
            }
            else 
                $ret .= $data['other']['axfees'][1][$tstamp];
            $overshorts[$tstamp] -= $data['other']['axfees'][1][$tstamp];
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
    $amt = isset($data['other']['buyAmount']) ? $data['other']['buyAmount'] : '';
    $ret .= inputTypeOther(array('other','buyAmount','credit'), $amt, $excel, $endTS);
    $overshorts[$endTS] -= $amt === '' ? 0 : $amt;
    $ret .= "</td>";
    $ret .= "</tr>";

    $ret .= "<tr class=$classes[$c]>"; $c = ($c+1)%2;
    $ret .= "<td>Over/Short</td><td>63350</td>";
    for($i=0;$i<$num_days;$i++){
        $tstamp = mktime(0,0,0,$sMonth,$sDay+$i,$sYear);
        $overshorts[$tstamp] = round($overshorts[$tstamp],2);
        $ret .= "<td class=money id=overshortDebit$tstamp>";
        if ($overshorts[$tstamp] < 0){
            $ret .= -1*$overshorts[$tstamp];
        }
        else
            $ret .= "&nbsp;";
        $ret .= "</td><td class=money id=overshortCredit$tstamp>";
        if ($overshorts[$tstamp] >= 0){
            $ret .= $overshorts[$tstamp];
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
    while($row = $sql->fetch_row($pageOneR2)){
        switch(strtolower($row['denomination'])){
            case 'checks':
                $data['other']['depositChecks'] += $row['amt'];
                $data['other']['depositAmount'] -= $row['amt'];
                break;
            case 'fill':
                $data['other']['atmNet'] += $row['amt'];
                break;
            case 'reject':
                $data['other']['atmNet'] -= $row['amt'];
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
        $timestamp = getTS($tenderW, 3, 4, 5);

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
        $timestamp = getTS($extraTenderW, 0, 1, 2);
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
        sum(total),trans_type, d.store_id
        FROM $dlog as d left join is4c_op.departments as t on
        d.department = t.dept_no 
        WHERE tdate BETWEEN ? AND ?
        AND trans_subtype NOT IN ('CP','IC')
        and trans_type not in ('S','T')
        AND (register_no <> 20 or department=703)
        GROUP BY 
        YEAR(tdate),MONTH(tdate),DAY(tdate),
        trans_type,
        CASE WHEN department = 991 then '991' when department=992 then '992' else convert(t.salesCode,char) end,
        d.store_id
        ORDER BY
        CASE WHEN department = 991 then '991' when department=992 then '992' else convert(t.salesCode,char) end");
    $salesR = $sql->execute($salesP, $date_args);
    $preTS = 0;
    while($salesW = $sql->fetch_row($salesR)){
        $timestamp = getTS($salesW, 0, 1, 2);

        /* fill in zeroes for all pcodes */
        if ($timestamp != $preTS){
            foreach($pCodes_lookup as $k=>$v){
                foreach (array(1,2) as $store) {
                    $key = StandardAccounting::extend($k, $store);
                    $pCodes_lookup[$key] = $v;
                    if (!isset($data['sales'][$key]))
                        $data['sales'][$key] = array();
                    if (!isset($data['sales'][$key][$timestamp]))
                        $data['sales'][$key][$timestamp] = 0;
                }
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
            $pcode = StandardAccounting::extend($pcode, $salesW['store_id']);
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
        from $dlog as d left join is4c_op.memtype as m on d.memtype=m.memtype
        where upc='DISCOUNT' and tdate between ? AND ?
        group by YEAR(tdate),MONTH(tdate),DAY(tdate),
        memDesc");
    $discountR = $sql->execute($discountP, $date_args);
    $data['other']['discount'] = array();
    while($discountW = $sql->fetch_row($discountR)){
        $tstamp = getTS($discountW, 0, 1, 2);
        
        $type = $discountW[3];
        if ($type == 'Non Member') $type = 'Staff Member';
        if (!isset($data['other']['discount'][$type]))
            $data['other']['discount'][$type] = array();
        if (!isset($data['other']['discount'][$type][$tstamp]))
            $data['other']['discount'][$type][$tstamp] = 0;
        $data['other']['discount'][$type][$tstamp] += $discountW[4];
    }

    return $data;
}

function inputTypeOther($type, $amt, $excel, $endTS)
{
    $ret = '';
    if (!$excel){
        $ret .= "<input type=text size=7 value=\"";
        $ret .= ($amt)."\"";
        $ret .= " onchange=\"save2(this.value,'{$type[0]}','{$type[1]}');rb($endTS);\" ";
        $ret .= "style=\"text-align:right\" name={$type[2]}$endTS />";
    } else {
        $ret .= $amt;
    }

    return $ret;
}

function getTS($row, $year, $month, $day)
{
    return mktime(
        0,
        0,
        0,
        $row[$month],
        $row[$day],
        $row[$year]
    );
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
