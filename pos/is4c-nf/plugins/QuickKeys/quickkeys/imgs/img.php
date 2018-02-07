<?php

use COREPOS\pos\lib\Database;

include(__DIR__ . '/../../../../lib/AutoLoader.php');

if (isset($_GET['imgID'])) {
    $dbc = Database::pDataConnect();
    $prep = $dbc->prepare('SELECT * FROM QuickLookups WHERE quickLookupID=?');
    $row = $dbc->getRow($prep, array($_GET['imgID']));
    if ($row) {
        header('Content-type: ' . $row['imageType']);
        echo $row['image'];
    }
}

