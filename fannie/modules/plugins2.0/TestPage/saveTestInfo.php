<?php

$host = "68.112.171.192"; //129.103.2.3   //68.112.171.192
$username = "root"; //phpmyadmin
$password = "testql"; //wfc
$database_name = "optdata"; //is4c_op

$dbc = mysql_connect($host, $username, $password);
mysql_select_db($database_name, $dbc);

$query = ("
        INSERT INTO test VALUES(
            1,
            2,
            3
        );
    ");
    $result = mysql_query($query, $dbc);
