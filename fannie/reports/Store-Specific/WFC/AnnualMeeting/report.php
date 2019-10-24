<?php
include('../../../../config.php');
include_once(__DIR__ . '/../../../../src/SQLManager.php');
include_once(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
if (!function_exists('wfc_am_get_names')) {
    include(dirname(__FILE__) . '/lib.php');
}

if (FormLib::get('excel') !== '') {
    $ext = \COREPOS\Fannie\API\data\DataConvert::excelFileExtension();
    header('Content-Type: application/ms-excel');
    header('Content-Disposition: attachment; filename="AnnualMtg2011.' . $ext . '"');
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
    SUM(CASE WHEN charflag IN ('S','C','T', 'D') THEN quantity ELSE 0 END)-1 as guest_count,
    SUM(CASE WHEN charflag IN ('K') THEN quantity ELSE 0 END) as child_count,
    SUM(CASE WHEN charflag = 'S' THEN quantity ELSE 0 END) as squash,
    SUM(CASE WHEN charflag = 'T' THEN quantity ELSE 0 END) as squashgf,
    SUM(CASE WHEN charflag = 'C' THEN quantity ELSE 0 END) as chicken,
    SUM(CASE WHEN charflag = 'D' THEN quantity ELSE 0 END) as chickengf,
    'pos' AS source,
    n.note AS notes
    FROM ".$FANNIE_TRANS_DB.$fannieDB->sep()."dlog AS d
    LEFT JOIN custdata AS c ON c.CardNo=d.card_no AND c.personNum=1
    LEFT JOIN meminfo AS m ON d.card_no=m.card_no
    LEFT JOIN regNotes AS n ON d.card_no=n.card_no
    WHERE upc IN ('0000000001041','0000000001042')
        AND d.card_no <> 3145
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

include(__DIR__ . '/../../../../src/Credentials/OutsideDB.tunneled.php');
// online registrations
$query = "SELECT r.tdate,r.card_no,name,email,
    phone,SUM(CASE WHEN m.type='GUEST' THEN 1 ELSE 0 END) AS guest_count,child_count,
    SUM(CASE WHEN m.subtype=1 THEN 1 ELSE 0 END) as squash,
    SUM(CASE WHEN m.subtype=2 THEN 1 ELSE 0 END) as squashgf,
    SUM(CASE WHEN m.subtype=3 THEN 1 ELSE 0 END) as chicken,
    SUM(CASE WHEN m.subtype=4 THEN 1 ELSE 0 END) as chickengf,
    'website' AS source,
    n.notes
    FROM registrations AS r LEFT JOIN
    regMeals AS m ON r.card_no=m.card_no
    LEFT JOIN regNotes AS n ON r.card_no=n.card_no
    WHERE paid=1
    GROUP BY tdate,r.card_no,name,email,
    phone,guest_count,child_count
    ORDER BY tdate";
$res = $dbc->query($query);
var_dump($dbc->error());
var_dump($dbc->tableDefinition('regMeals'));
while($row = $dbc->fetch_row($res)){
    $records[] = $row;
}
echo '<table cellspacing="0" cellpadding="4" border="1">
    <tr>
    <th>Reg. Date</th><th>Owner#</th><th>Last Name</th><th>First Name</th>
    <th>Email</th><th>Ph.</th><th>Adults</th><th>Squash</th><th>Squash G/F</th>
    <th>Chicken</th><th>Chicken G/F</th><th>Kids</th><th>Source</th><th>Notes</th>
    </tr>';
$sum = array(
    'squash' => 0,
    'squashgf' => 0,
    'chicken' => 0,
    'chickengf' => 0,
    'kids' => 0,
    'adults' => 0,
);
foreach($records as $w){
    list($w['email'], $w['name']) = wfc_am_check_email($w['email'], $w['name']);
    list($fname, $lname) = wfc_am_get_names($w['name']);
    printf('<tr><td>%s</td><td>%d</td><td>%s</td><td>%s</td>
        <td>%s</td><td>%s</td><td>%d</td><td>%d</td>
        <td>%d</td><td>%d</td><td>%d</td>
        <td>%s</td><td>%s</td></tr>',
        $w['tdate'],$w['card_no'],$lname,$fname,$w['email'],
        $w['phone'],$w['guest_count']+1,
        $w['squash'],$w['squashgf'],
        $w['chicken'],$w['chickengf'],
        $w['child_count'],
        $w['source'], $w['notes']
    );
    $sum['adults'] += ($w['guest_count']+1);
    $sum['kids'] += $w['child_count'];
    $sum['chicken'] += $w['chicken'];
    $sum['squash'] += $w['squash'];
    $sum['chickengf'] += $w['chickengf'];
    $sum['squashgf'] += $w['squashgf'];
}
echo '<tr><th colspan="6" align="right">Totals</th>';
echo '<td>'.$sum['adults'].'</td>';
echo '<td>'.$sum['squash'].'</td>';
echo '<td>'.$sum['squashgf'].'</td>';
echo '<td>'.$sum['chicken'].'</td>';
echo '<td>'.$sum['chickengf'].'</td>';
echo '<td>'.$sum['kids'].'</td>';
echo '<td>&nbsp;</td>';
echo '</table>';

if (FormLib::get('excel') !== '') {
    $output = ob_get_contents();
    ob_end_clean();

    $array = \COREPOS\Fannie\API\data\DataConvert::htmlToArray($output);
    $xls = \COREPOS\Fannie\API\data\DataConvert::arrayToExcel($array);
    
    echo $xls;
}

