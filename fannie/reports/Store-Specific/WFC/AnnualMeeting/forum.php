<?php
include('../../../../config.php');
include_once($FANNIE_ROOT.'src/SQLManager.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
if (!function_exists('wfc_am_get_names')) {
    include(dirname(__FILE__) . '/lib.php');
}

if (FormLib::get('excel') !== '') {
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

include($FANNIE_ROOT.'src/Credentials/OutsideDB.tunneled.php');
// online registrations
$query = "SELECT datetime as tdate,u.owner as card_no,
    real_name as name,name as email,
    '' as phone,
    d.description,
    'website' AS source
    FROM dtransactions AS d LEFT JOIN
    Users AS u ON d.emp_no=u.uid
    WHERE upc LIKE '0000098%'
    ORDER BY upc,tdate";
$res = $dbc->query($query);
while($row = $dbc->fetch_row($res)){
    $records[] = $row;
}
$totals = array();
echo '<table cellspacing="0" cellpadding="4" border="1">
    <tr>
    <th>Reg. Date</th><th>Owner#</th><th>Last Name</th><th>First Name</th>
    <th>Email</th><th>Ph.</th><th>Event</th><th>Source</th>
    </tr>';
foreach($records as $w){
    list($w['email'], $w['name']) = wfc_am_check_email($w['email'], $w['name']);
    list($fname, $lname) = wfc_am_get_names($w['name']);
    printf('<tr><td>%s</td><td>%d</td><td>%s</td><td>%s</td>
        <td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
        $w['tdate'],$w['card_no'],$lname,$fname,$w['email'],
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

if (FormLib::get('excel') !== '') {
    $output = ob_get_contents();
    ob_end_clean();

    $array = \COREPOS\Fannie\API\data\DataConvert::htmlToArray($output);
    $xls = \COREPOS\Fannie\API\data\DataConvert::arrayToExcel($array);
    
    echo $xls;
}

