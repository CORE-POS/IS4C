<?php
include('../../../config.php');
if (!class_exists('FannieAPI'))
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
header('Location: ' . $FANNIE_URL . 'item/vendors/BrowseVendorItems.php?vid=1');

