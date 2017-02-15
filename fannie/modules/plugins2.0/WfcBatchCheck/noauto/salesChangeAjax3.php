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
$_SESSION['store_id'] = $_GET['store_id'];

echo '<p>';
if ($_SESSION['store_id'] == 1) {
    echo "You are set to scan the Hillside store</p>";
} else {
    echo "You are set to scan the Denfeld store </p>";
}

