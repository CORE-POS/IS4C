<?php

// use configured home page if present
if (file_exists('config.php')) {
    include('config.php');
    if (isset($FANNIE_HOME_PAGE) && strlen($FANNIE_HOME_PAGE) > 0) {
        header('Location: ' . $FANNIE_HOME_PAGE);
        return;
    }
}
$slash = strrpos($_SERVER['REQUEST_URI'],"/");
$rel_uri = substr($_SERVER['REQUEST_URI'],0,$slash);
header("Location: $rel_uri/item/ItemEditorPage.php");

