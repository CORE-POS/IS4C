<?php

$upc = $_GET['upc'];
$queue = $_GET['queue'];

echo 'UPC ' . $upc . ', Queue ' . $queue;

/**
  User account has full access to this database to
  create tables, insert/update/delete rows, etc
*/
$db_name = "woodshed_no_replicate";

