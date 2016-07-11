<?php 
include(dirname(__FILE__) . '/../../../../config.php');
if (!class_exists('FannieAPI.php')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}
session_start();
?>
<html>
<head>
    <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
    <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
</head>
<title>
    Sales Change Queues
</title>
<style>
    <?php //include 'SalesChange.css'; ?>
    body {
        background-color: #9ce094;
    }
    table, th, td {
        border: 1px solid #9ce094;
        border-collapse: collapse;
        background-color: #b3ffb0;
    }
</style>
<script type="text/javascript" src="/git/fannie/src/javascript/jquery.js"></script>
<script type="text/javascript" src="/git/fannie/src/javascript/linea/cordova-2.2.0.js"></script>
<script type="text/javascript" src="/git/fannie/src/javascript/linea/ScannerLib-Linea-2.0.0.js"></script>
<script type="text/javascript" src="scanner.js"></script>
<script type="text/javascript">

function sendToQueue(button, upc, queue_id)
{
    $.ajax({

        // Info will be sent to this URL
        url: 'salesChangeAjax2.php',

        // The actual data to send
        data: 'upc='+upc+'&queue='+queue_id,

        // callback to process the response
        success: function(response)
        {
            // display the response in element w/ id=ajax-resp
            $('#ajax-resp').html('AJAX call returned: ' + response);

            // search DOM upword for a <tr> tag and hide that element
            // as well as its children
            $(button).closest('tr').hide();
        }
    });
}

function changeStoreID(button, store_id)
{
    $.ajax({

        url: 'salesChangeAjax3.php',
        data: 'store_id='+store_id,
        success: function(response)
        {
            $('#ajax-resp').html(response);
            window.location.reload();
        }
    });
}
</script>
<body>
    <?php include 'SalesChangeLinks.html'; ?>

    <div align="right">
        <br>
        <button class="btn btn-default" type="button" onclick="changeStoreID(this, 1); return false; window.location.reload();">Hillside</button>
        <button class="btn btn-default" type="button" onclick="changeStoreID(this, 2); return false; window.location.reload();">Denfeld</button>
    </div>

    <?php
$database_name = "woodshed_no_replicate";
$dbc= FannieDB::getReadOnly($database_name);
if($_SESSION['store_id'])
{
    //* Find UPCs and Queues in the table */
    $query = "SELECT q.queue,
            u.brand,
            u.description,
            u.upc,
            p.size,
            p.normal_price,
            p.special_price as price,
            b.batchName,
            p.last_sold,
            b.batchID
            FROM SaleChangeQueues as q
                LEFT JOIN is4c_op.products as p on p.upc=q.upc
                LEFT JOIN is4c_op.productUser as u on u.upc=p.upc
                LEFT JOIN is4c_op.batchList AS l ON q.upc=l.upc
                LEFT JOIN is4c_op.batches AS b ON l.batchID=b.batchID
            WHERE q.queue=98
                AND p.inUse = 1
                AND CURDATE() BETWEEN b.startDate AND b.endDate
                AND q.store_id={$_SESSION['store_id']}
            GROUP BY upc
            ORDER BY u.brand ASC
            ;";
    $result = $dbc->query($query);
    while ($row = $dbc->fetchRow($result)) {
        $upc[] = $row['upc'];
        $brand[] = $row['brand'];
        $desc[] = $row['description'];
        $queue[] = $row['queue'];
        $size[] = $row['size'];
        $price[] = $row['price'];
        $batch[] = "<a href='http://key/git/fannie/batches/newbatch/EditBatchPage.php?id="
			. $row['batchID'] . "' target='_blank'>" . $row['batchName'] . "</a>";
        $upcLink[] = "<a href='http://key/git/fannie/item/ItemEditorPage.php?searchupc="
                    . $row['upc']
                    . "&ntype=UPC&searchBtn=' class='blue' target='_blank'>{$row['upc']}
                    </a>";
        $last[] = $row['last_sold'];
    }
    if ($dbc->error()) {
        echo $dbc->error(). "<br>";
    }
    echo "<h1 align='center'>Do Not Carry</h1>";
    echo "<div align='center'>";
    if ($_SESSION['store_id'] == 1) {
        echo "Hillside<br>";
    } else {
        echo "Denfeld<br>";
    }
    echo "</div>";
    echo "<p align='center'>" . count($upc) . " tags in this queue</p>";
    echo "<table class='table table'>";
    echo "<th>Brand</th>
          <th>Name</th>
          <th>Size</th>
          <th>Price</th>
          <th>UPC</th>
          <th>Batch</th>
          <th>Last Sold</th>";
    for ($i=0; $i<count($upc); $i++) {
        if ($upc[$i] > 0) {
            echo "<tr><td>" . $brand[$i] . "</td>";
            echo "<td>" . $desc[$i] . "</td>";
            echo "<td>" . $size[$i] . "</td>";
            echo "<td>" . $price[$i] . "</td>";
            echo "<td>" . $upcLink[$i] . "</td>";
            echo "<td>" . $batch[$i] . "</td>";
            echo "<td>" . $last[$i] . "</td>";
            echo "<td><button class=\"btn btn-default\" type=\"button\" onclick=\"sendToQueue(this, '{$upc[$i]}', 0); return false;\">put back in unchecked</button></tr>";

        }
    }
    echo "</table>";
} else {
    echo '<h1 class="text text-danger" align="right">Select a store</h1>';
}
