<html>
<head>
  <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
  <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
</head>
<title>
    Sales Change ListGen
</title>
<style>
body {
    background-color: #9ce094;
}
</style>
<body>
<fieldset>
    <div class="container" ><h3>
        Submission <i>truncates</i> 
        old tag queues. Do not submit unless you are 
        finished with the current queues!
    </h3>

    <form method="get" id='form1' >
        <label>Name Your Session</label><br>
        <input type="text" class="form-control" name="startdate" required><br><br>
        <input type="submit" class="btn btn-danger" value="clear old / create new queues">
    </form> 
</div>
</fieldset>
<?php
include(dirname(__FILE__) . '/../../../../config.php');
if (!class_exists('FannieAPI.php')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}
$database_name = "woodshed_no_replicate";
$upc = array();

$dbc = FannieDB::get($database_name);

if($_GET['startdate'] != NULL) {

    //  Clean table of previous data
    $query = "truncate SaleChangeQueues;";
    $result = $dbc->query($query);
     
    //  Procure batches FROM stardate
    $query = "SELECT batchID, 
        owner
        FROM is4c_op.batches 
        WHERE CURDATE() BETWEEN startDate AND endDate 
    ;";
    $result = $dbc->query($query);
    while ($row = $dbc->fetchRow($result)) {
        $batchID[] = $row['batchID'];
        $owner[] = $row['owner'];
    }
    if ($dbc->error()) {
        echo $dbc->error(). "<br>";
    }

    // Procure Product Information FROM batchList
    for ($i = 0; $i < count($batchID); $i++) {
       $query = "SELECT bl.upc
            FROM is4c_op.batchList AS bl
            WHERE bl.batchID='{$batchID[$i]}'
            ;";
        $result = $dbc->query($query);
        while ($row = $dbc->fetchRow($result)) {
            $upc[] = $row['upc'];
            $store_id[] = $row['store_id'];
            $sessionName = $_GET['startdate'];
        } 
    }

    $date = date('Y-m-d');

    // Insert Items into SaleChangeQueues
    for ($i = 0; $i < count($upc); $i++) {
        $query = "INSERT INTO SaleChangeQueues (session, queue, upc, store_id, date) VALUES (
            '{$_GET['startdate']}',
            '0',
            '{$upc[$i]}',
            '1',
            '{$date}'
            )
            ;";
        $result = $dbc->query($query);
        if ($dbc->error()) {
            echo $dbc->error(). "<br>";
        }
        $query = "INSERT INTO SaleChangeQueues (session, queue, upc, store_id, date) VALUES (
            '{$_GET['startdate']}',
            '0',
            '{$upc[$i]}',
            '2',
            '{$date}'
            )
            ;";
        $result = $dbc->query($query);
        if ($dbc->error()) {
            echo $dbc->error(). "<br>";
        }
    }
    echo count($upc) . " items have been added to the Sales Change Queues.";
}
