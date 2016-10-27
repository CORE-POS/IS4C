<?php
include('../../../../config.php');
include_once($FANNIE_ROOT.'src/SQLManager.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

$fannieDB = FannieDB::get($FANNIE_OP_DB);

// POS registrations from today
$hereQ = "SELECT MIN(tdate) AS tdate,d.card_no,".
    $fannieDB->concat('c.FirstName',"' '",'c.LastName','')." as name,
    m.phone, m.email_1 as email,
    SUM(CASE WHEN charflag IN ('M','V','N','W') THEN quantity ELSE 0 END)-1 as guest_count,
    SUM(CASE WHEN charflag IN ('K') THEN quantity ELSE 0 END) as child_count,
    SUM(CASE WHEN charflag = 'M' THEN quantity ELSE 0 END) as chicken,
    SUM(CASE WHEN charflag = 'V' THEN quantity ELSE 0 END) as veg,
    SUM(CASE WHEN charflag = 'N' THEN quantity ELSE 0 END) as mgf,
    SUM(CASE WHEN charflag = 'W' THEN quantity ELSE 0 END) as vgf,
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

include($FANNIE_ROOT.'src/Credentials/OutsideDB.tunneled.php');
// online registrations
$q = "SELECT tdate,r.card_no,name,email,
    phone,guest_count,child_count,
    SUM(CASE WHEN m.subtype=1 THEN 1 ELSE 0 END) as chicken,
    SUM(CASE WHEN m.subtype=2 THEN 1 ELSE 0 END) as veg,
    SUM(CASE WHEN m.subtype=3 THEN 1 ELSE 0 END) as mgf,
    SUM(CASE WHEN m.subtype=4 THEN 1 ELSE 0 END) as vgf,
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

$dbc = FannieDB::get($FANNIE_OP_DB);

foreach ($records as $w) {
    printf("INSERT INTO registrations (tdate, card_no, name, email, phone, guest_count, child_count, paid, checked_in)
        VALUES ('%s', %d, %s, %s, %s, %d, %d, 1, 0);\n",
        $w['tdate'], $w['card_no'], $dbc->escape($w['name']),
        $dbc->escape($w['email']), $dbc->escape($w['phone']),
        $w['guest_count'], $w['child_count']);
    $adult = 'OWNER';
    for ($i=0; $i<$w['chicken']; $i++) {
        printf("INSERT INTO regMeals (card_no, type, subtype) VALUES (%d, '%s', %d);\n",
            $w['card_no'], $adult, 1);
        $adult = 'GUEST';
    }
    for ($i=0; $i<$w['veg']; $i++) {
        printf("INSERT INTO regMeals (card_no, type, subtype) VALUES (%d, '%s', %d);\n",
            $w['card_no'], $adult, 2);
        $adult = 'GUEST';
    }
    for ($i=0; $i<$w['mgf']; $i++) {
        printf("INSERT INTO regMeals (card_no, type, subtype) VALUES (%d, '%s', %d);\n",
            $w['card_no'], $adult, 3);
        $adult = 'GUEST';
    }
    for ($i=0; $i<$w['vgf']; $i++) {
        printf("INSERT INTO regMeals (card_no, type, subtype) VALUES (%d, '%s', %d);\n",
            $w['card_no'], $adult, 4);
        $adult = 'GUEST';
    }
    for ($i=0; $i<$w['child_count']; $i++) {
        printf("INSERT INTO regMeals (card_no, type, subtype) VALUES (%d, 'CHILD', 0);\n",
            $w['card_no']);
        $adult = 'GUEST';
    }
}

