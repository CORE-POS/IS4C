<?php
include('../../../../config.php');
include_once($FANNIE_ROOT.'src/SQLManager.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

if (isset($_REQUEST['excel'])){
    header('Content-Type: application/ms-excel');
    header('Content-Disposition: attachment; filename="AnnualMtg2011.xls"');
    ob_start();
}
else {
    echo '<a href="report.php?excel=yes">Save as Excel</a><br /><br />';
}

$fannieDB = FannieDB::get($FANNIE_OP_DB);

// POS registrations from today
$hereQ = "SELECT MIN(tdate) AS tdate,d.card_no,".
    $fannieDB->concat('c.FirstName',"' '",'c.LastName','')." as name,
    m.phone, m.email_1 as email,
    SUM(CASE WHEN charflag IN ('M','V','S') THEN quantity ELSE 0 END)-1 as guest_count,
    SUM(CASE WHEN charflag IN ('K') THEN quantity ELSE 0 END) as child_count,
    SUM(CASE WHEN charflag = 'M' THEN quantity ELSE 0 END) as chicken,
    SUM(CASE WHEN charflag = 'V' THEN quantity ELSE 0 END) as veg,
    SUM(CASE WHEN charflag = 'S' THEN quantity ELSE 0 END) as vegan,
    'pos' AS source
    FROM ".$FANNIE_TRANS_DB.$fannieDB->sep()."dlog AS d
    LEFT JOIN custdata AS c ON c.CardNo=d.card_no AND c.personNum=1
    LEFT JOIN meminfo AS m ON d.card_no=m.card_no
    WHERE upc IN ('0000000001041','0000000001042')
    GROUP BY d.card_no
    ORDER BY MIN(tdate)";
$records = array();
$hereR = $fannieDB->query($hereQ);
while($hereW = $fannieDB->fetch_row($hereR)){
    $records[] = $hereW;
}

// POS registrations from last 90 days
$hereQ = str_replace('dlog ','dlog_90_view ',$hereQ);
$hereR = $fannieDB->query($hereQ);
while($hereW = $fannieDB->fetch_row($hereR)){
    $records[] = $hereW;
}

include($FANNIE_ROOT.'src/Credentials/OutsideDB.is4c.php');
// online registrations
$q = "SELECT tdate,r.card_no,name,email,
    phone,guest_count,child_count,
    SUM(CASE WHEN m.subtype=1 THEN 1 ELSE 0 END) as chicken,
    SUM(CASE WHEN m.subtype=2 THEN 1 ELSE 0 END) as veg,
    SUM(CASE WHEN m.subtype=3 THEN 1 ELSE 0 END) as vegan,
    'website' AS source
    FROM registrations AS r LEFT JOIN
    regMeals AS m ON r.card_no=m.card_no
    WHERE paid=1
    GROUP BY tdate,r.card_no,name,email,
    phone,guest_count,child_count
    ORDER BY tdate";
$r = $dbc->query($q);
while($w = $dbc->fetch_row($r)){
    $records[] = $w;
}
echo '<table cellspacing="0" cellpadding="4" border="1">
    <tr>
    <th>Reg. Date</th><th>Owner#</th><th>Last Name</th><th>First Name</th>
    <th>Email</th><th>Ph.</th><th>Adults</th><th>Steak</th><th>Risotto</th><th>Squash</th>
    <th>Kids</th><th>Source</th>
    </tr>';
$sum = 0;
$ksum = 0;
$xsum = 0;
$vsum = 0;
$gsum = 0;
foreach($records as $w){
    if (!strstr($w['email'],'@') && !preg_match('/\d+/',$w['email']) &&
        $w['email'] != 'no email'){
        $w['name'] .= ' '.$w['email'];  
        $w['email'] = '';
    }
    $ln = ""; $fn="";
    if (strstr($w['name'],' ')){
        $w['name'] = trim($w['name']);
        $parts = explode(' ',$w['name']);
        if (count($parts) > 1){
            $ln = $parts[count($parts)-1];
            for($i=0;$i<count($parts)-1;$i++)
                $fn .= ' '.$parts[$i];
        }
        else if (count($parts) > 0)
            $ln = $parts[0];
    }
    else
        $ln = $w['name'];
    printf('<tr><td>%s</td><td>%d</td><td>%s</td><td>%s</td>
        <td>%s</td><td>%s</td><td>%d</td><td>%d</td>
        <td>%d</td><td>%d</td><td>%d</td><td>%s</td></tr>',
        $w['tdate'],$w['card_no'],$ln,$fn,$w['email'],
        $w['phone'],$w['guest_count']+1,$w['chicken'],$w['veg'],$w['vegan'],$w['child_count'],
        $w['source']
    );
    $sum += ($w['guest_count']+1);
    $ksum += $w['child_count'];
    $xsum += $w['chicken'];
    $vsum += $w['veg'];
    $gsum += $w['vegan'];
}
echo '<tr><th colspan="6" align="right">Totals</th>';
echo '<td>'.$sum.'</td>';
echo '<td>'.$xsum.'</td>';
echo '<td>'.$vsum.'</td>';
echo '<td>'.$gsum.'</td>';
echo '<td>'.$ksum.'</td>';
echo '<td>&nbsp;</td>';
echo '</table>';

if (isset($_REQUEST['excel'])){
    $output = ob_get_contents();
    ob_end_clean();

    include($FANNIE_ROOT.'src/ReportConvert/HtmlToArray.php');
    include($FANNIE_ROOT.'src/ReportConvert/ArrayToXls.php');
    $array = HtmlToArray($output);
    $xls = ArrayToXls($array);
    
    echo $xls;
}

?>
