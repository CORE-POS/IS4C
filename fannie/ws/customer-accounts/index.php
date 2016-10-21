<?php
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../../classlib2.0/FannieAPI.php');
}

if (isset($_GET['id'])) {
    $json = \COREPOS\Fannie\API\member\MemberREST::get($_GET['id']);
    header('Content-type: application/json');
    echo json_encode($json);
} else {
    echo 'Append an id to the URL.';
}

