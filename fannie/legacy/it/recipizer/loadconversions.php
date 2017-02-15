<?php

include('../../../config.php');
include_once('dbconnect.php');

$sql = dbconnect();

$truncQ = "truncate table convertor";
$truncR = $sql->query($truncQ);

$fp = fopen('conversions.txt','r');
$insQ = $sql->prepare("insert into convertor values (?, ?, ?)");
while ($line = fgets($fp)){
    $matches = array();
    preg_match("/^(.+?)\\t+(.+?)\\t+(.+?)\\n$/", $line, $matches);
    echo $matches[1].":".$matches[2].":".$matches[3]."<br />";
    $from = $matches[1];
    $to = $matches[2];
    $mult = $matches[3];
    $insR = $sql->execute($insQ, array($from, $to, $mult));
}

