<?php 
include(dirname(__FILE__) . '/../../../../config.php');
if (!class_exists('FannieAPI.php')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}
session_start();
?>
<html>
<meta name="viewport" content="width=device-width, initial-scale=1">
<head>
  <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
  <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
</head>
<head>

<style>
body {
    background-color: #9ce094;
}
button {
	width:300px;
	height: 75px;
    border-radius: 5px;
    font-size: 18;
}
button.good {
	background-color: lightgreen;
}
button.error {
	background-color: #fa7d7d;
}
button.missing {
	background-color: #f0f56c;
}
button.addItem {
    background-color: #27e5f2;
}
.blue {
    color: blue;
}
.red {
    color: red;
}
a {
    font-size: 18;
    text-align: center;
}
table, tr, td, th {
    border: 1px solid #9ce094;
	padding: none;   
	font-size: 12px;
}

</style>
<script type="text/javascript" src="/git/fannie/src/javascript/jquery.js"></script>
<script type="text/javascript" src="/git/fannie/src/javascript/linea/cordova-2.2.0.js"></script>
<script type="text/javascript" src="/git/fannie/src/javascript/linea/ScannerLib-Linea-2.0.0.js"></script>
<script type="text/javascript" src="scanner.js"></script>
<script type="text/javascript">
$(document).ready(function(){
    enableLinea('#upc', function(){$('#my-form').submit();});
});
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
    <title>SalesChangeScanner</title>
</head>
<body>
    <br><br><br>
<form method='get' name='MyForm' id="my-form">
    <input type='text' name='upc' id="upc">
    <input type='submit' value='go'>
</form>
<div id="ajax-resp" style="font-weight:bold; font-size: 14pt;"></div>

<script>
function myFunction() {
    document.getElementById("field2").value = document.getElementById("field1").value;
}
</script>

<?php
$item = array ( array() );

echo "<div align=\"center\">";
if ($_SESSION['store_id'] == 1) {
    echo "<h2>Hillside</h2>";
} else {
    echo "<h2>Denfeld</h2>";
}
echo "</div>";

if ($_SESSION['store_id'] == NULL) {
    echo "<strong class=\"red\" text-align=\"center\">
        WARNING : YOU HAVE NOT SELECTED
        A STORE.<br> NO ITEMS WILL BE UPDATED IN BATCH 
        CHECK. <br>PLEASE SELECT A STORE AT BOTTOM OF 
        PAGE.</strong>";
}

$database_name = "woodshed_no_replicate";
$dbc = FannieDB::getReadOnly($database_name);

if ($_GET['upc']) {
    echo "<table class='table'  align='center' width='100%'>";
    //* Find UPCs and Queues in Woodshed */
    $query = "SELECT q.queue, 
            u.brand as ubrand, 
            u.description as udesc,
            p.upc, 
            p.size as psize, 
            p.normal_price, 
            v.size as vsize,
            p.brand as pbrand, 
            p.description as pdesc
            FROM is4c_op.products as p
                LEFT JOIN is4c_op.productUser as u on u.upc=p.upc 
                LEFT JOIN SaleChangeQueues as q ON q.upc=p.upc
                LEFT JOIN is4c_op.vendorItems as v on v.upc=p.upc
            
            WHERE p.upc={$_GET['upc']}

            GROUP BY p.upc
            ;";
    $result = $dbc->query($query);
    while ($row = $dbc->fetchRow($result)) {
        echo "<tr><td><b>upc</td><td>" . $row['upc'] . "</tr>";
        if ($row['ubrand'] != NULL) {
            echo "<tr><td><b>brand</td><td>" . $row['ubrand'] . "</tr>";
        } else {
            echo "<tr><td><b>brand</td><td>" . $row['pbrand'] . "</tr>";
        }
        
        if ($row['udesc'] != NULL) {
            echo "<tr><td><b>product </td><td>" . $row['udesc'] . "</tr>";
        } else {
            echo "<tr><td><b>product </td><td>" . $row['pdesc'] . "</tr>";
        }
        
        if ($row['psize'] == NULL) {
            echo "<tr><td><b>size</td><td>" . $row['psize'] . "</tr>";
        } else {
            echo "<tr><td><b>size</td><td>" . $row['vsize'] . "</tr>";
        }
        
	
        if ($row['queue'] != NULL)  {
            echo "<tr><td><b>queue</td><td>" . $row['queue'] . "</tr>";
        } else if ($row['queue'] == NULL) {
            echo "<tr><td><b>queue</td><td><i class=\"red\">Item not in today's batch list</tr>";
        }
        echo "<tr><td><b>Price</td><td>" . "$" . $row['normal_price'] . "</tr>";
	
        
    /*    
        if ($row['queue'] == 0) {
            echo "<tr><td><b>queue</td><td><i>item has not been checked yet</tr>";
        } else if ($row['queue'] == 1) {
            echo "<tr><td><b>queue</td><td>good tag</tr>";
        } else if ($row['queue'] >=2 && $row['queue'] <=7) {
            echo "<tr><td><b>queue</td><td>tag error</tr>";
        } else if ($row['queue'] == 8) {
            echo "<tr><td><b>queue</td><td>tag missing</tr>";
        } else if ($row['queue'] == NULL){
            echo "<tr><td><b>queue</td><td>tag is not in a queue</tr>";
        }
    */  
    }
	

    //  Procure batches from stardate
    $query = "select batchID, owner 
            from is4c_op.batches 
            where CURDATE() BETWEEN startDate AND endDate
            ;";
    $result = $dbc->query($query);
    while ($row = $dbc->fetchRow($result)) {
        $batchID[] = $row['batchID'];
        $owner[] = $row['owner'];
    }
    if ($dbc->error()) {
        echo $dbc->error(). "<br>";
    }

    // Procure Product Information from batchList
    $query = "SELECT l.upc, l.salePrice, b.batchName
        FROM is4c_op.batches AS b 
        LEFT JOIN is4c_op.batchList AS l ON l.batchID=b.batchID 
        WHERE CURDATE() BETWEEN b.startDate AND b.endDate 
            AND l.upc={$_GET['upc']}
        ;";
    $result = $dbc->query($query);
    while ($row = $dbc->fetchRow($result)) {
        echo "<tr><td><b>sale price</td><td class=\"blue\">" . $row['salePrice'] . "</tr>";
        echo "<tr><td><b>batch name</td><td>" . $row['batchName'] . "</tr>";
    } 
    if ($dbc->error()) {
        echo $dbc->error(). "<br>";
    }

    echo "</table>";
}

echo "<table class='table'>";
echo "<tr><td><button class=\"good\" type=\"button\" onclick=\"sendToQueue(this, '{$_GET['upc']}', 1); return false;\">Check Sign</button></tr>";
echo "<tr><td><button class=\"missing\" type=\"button\" onclick=\"sendToQueue(this, '{$_GET['upc']}', 8); return false;\">Missing Sign</button></tr>";
echo "<tr><td><button class=\"error\" type=\"button\" onclick=\"sendToQueue(this, '{$_GET['upc']}', 2); return false;\">Tag Error</button></tr>";
echo "<tr><td><button class=\"addItem\" type=\"button\" onclick=\"sendToQueue(this, '{$_GET['upc']}', 99); return false;\">Add Item to Queue</button></tr>";
echo "</table>";

echo "<br><div align='center'><a href=\"http://key/git/fannie/item/handheld/ItemStatusPage.php\">BACK TO FANNIE <a></div>";
?>
<div align="center">
<br><br><br>
<p>Change Store ID
    <table class="table">
        <tr><td><button class="good" type="button" onclick="changeStoreID(this, 1); return false; window.location.reload();">Hillside</button></tr>
        <tr><td><button class="good" type="button" onclick="changeStoreID(this, 2); return false; window.location.reload();">Denfeld</button></tr>
    </table>
</p></div>
