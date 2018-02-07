<?php

use COREPOS\pos\lib\Database;

include(__DIR__ . '/../../../../lib/AutoLoader.php');

if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
    header('HTTP/1.1 304 Not Modified');
    return;
}

if (isset($_GET['imgID'])) {
    $dbc = Database::pDataConnect();
    $prep = $dbc->prepare('SELECT * FROM QuickLookups WHERE quickLookupID=?');
    $row = $dbc->getRow($prep, array($_GET['imgID']));
    if ($row) {
        header('Content-type: ' . $row['imageType']);
        header('Cache-control: max-age='.(60*60*24*365));
        header('Expires: '.gmdate(DATE_RFC1123,time()+60*60*24*365));
        header_remove('Pragma');
        echo $row['image'];
    }
}

