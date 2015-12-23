<?php
include('../../../../config.php');
include_once($FANNIE_ROOT.'src/SQLManager.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

if (isset($_REQUEST['excel'])){
    $ext = \COREPOS\Fannie\API\data\DataConvert::excelFileExtension();
    header('Content-Type: application/ms-excel');
    header('Content-Disposition: attachment; filename="OwnerForums.' .$ext .'"');
    ob_start();
}
else {
    echo '<a href="report.php?excel=yes">Save as Excel</a><br /><br />';
}

$fannieDB = FannieDB::get($FANNIE_OP_DB);

// POS registrations from today
$hereQ = "SELECT tdate AS tdate,d.card_no,".
    $fannieDB->concat('c.FirstName',"' '",'c.LastName','')." as name,
    m.phone, m.email_1 as email,
    d.description,
    'pos' AS source
    FROM ".$FANNIE_TRANS_DB.$fannieDB->sep()."dlog AS d
    LEFT JOIN custdata AS c ON c.CardNo=d.card_no AND c.personNum=1
    LEFT JOIN meminfo AS m ON d.card_no=m.card_no
    WHERE upc LIKE '0000098%'
    ORDER BY upc,tdate";
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
$q = "SELECT datetime as tdate,u.owner as card_no,
    real_name as name,name as email,
    '' as phone,
    d.description,
    'website' AS source
    FROM dtransactions AS d LEFT JOIN
    Users AS u ON d.emp_no=u.uid
    WHERE upc LIKE '0000098%'
    ORDER BY upc,tdate";
$r = $dbc->query($q);
while($w = $dbc->fetch_row($r)){
    $records[] = $w;
}
$totals = array();
echo '<table cellspacing="0" cellpadding="4" border="1">
    <tr>
    <th>Reg. Date</th><th>Owner#</th><th>Last Name</th><th>First Name</th>
    <th>Email</th><th>Ph.</th><th>Event</th><th>Source</th>
    </tr>';
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
    } else {
        $ln = $w['name'];
    }
    printf('<tr><td>%s</td><td>%d</td><td>%s</td><td>%s</td>
        <td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
        $w['tdate'],$w['card_no'],$ln,$fn,$w['email'],
        $w['phone'],$w['description'],
        $w['source']
    );
    if (!isset($totals[$w['description']])) {
        $totals[$w['description']] = 0;
    }
    $totals[$w['description']]++;
}
echo '<tr><th colspan="8" align="left">Totals:</th></tr>';
foreach($totals as $event => $count) {
    printf('<tr><td>%s</td><td>%d</td><td colspan="6">&nbsp;</td></tr>',
        $event, $count);
}
echo '</table>';

if (isset($_REQUEST['excel'])){
    $output = ob_get_contents();
    ob_end_clean();

    $array = \COREPOS\Fannie\API\data\DataConvert::htmlToArray($output);
    $xls = \COREPOS\Fannie\API\data\DataConvert::arrayToExcel($array);
    
    echo $xls;
}

