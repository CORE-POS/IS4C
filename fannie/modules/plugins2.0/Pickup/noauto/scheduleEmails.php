<?php

include('../../../../config.php');
include('../../../../classlib2.0/FannieAPI.php');

$dbc = FannieDB::get(FannieConfig::config('OP_DB'));

$insP = "INSERT INTO core_schedule_email.ScheduledEmailQueue
    (scheduledEmailTemplateID, cardNo, sendDate, templateData, sentToEmail)
    VALUES (?, 0, ?, ?, ?)";

$res = $dbc->query("SELECT email, pDate, pTime, curbside, storeID FROM PickupOrders WHERE status='NEW'");
while ($row = $dbc->fetchRow($res)) {
    $template = $row['storeID'] == 1 ? 7 : 8;
    $method = $row['curbside'] ? 'curbside pickup' : 'in store pickup';
    $json = array(
        'time' => $row['pTime'],
        'method' => $method,
    );
    $args = array(
        $template,
        $row['pDate'],
        json_encode($json),
        $row['email'],

    );
    $dbc->execute($insP, $args);
    echo $row['email'] . "\n";
}
