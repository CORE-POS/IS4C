<?php 
include(dirname(__FILE__) . '/../../../../config.php');
if (!class_exists('FannieAPI.php')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}
?>
<style>
p {
    font-size: 30;
    text-align: center;
    border: 1px solid black;
    background-color: #acfaf7;
}
</style>

<?php
session_start();
$upc = $_GET['upc'];
$queue = $_GET['queue'];

echo '<p>UPC ' . $upc . ', Sent to Queue ' . $queue . '</p';

$database_name = "woodshed_no_replicate";

$dbc = FannieDB::get($database_name);

$query = "UPDATE SaleChangeQueues
        SET queue={$queue}
		WHERE upc={$upc}
        AND store_id={$_SESSION['store_id']}
        ;";
$result = $dbc->query($query);

if ($queue == 99) {
    $query = "INSERT INTO SaleChangeQueues (queue, upc, store_id)
        VALUES (
        99,
        '{$upc}',
        '{$_SESSION['store_id']}'
        )
        ;";
    $result = $dbc->query($query);
}
