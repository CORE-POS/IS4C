<?php

use Endroid\QrCode\QrCode;

include(dirname(__FILE__) . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}

$text = FormLib::get('in');

$qrCode = new QrCode();
$qrCode
    ->setText($text)
    ->setSize(200)
    ->setPadding(10)
    ->setErrorCorrection('high')
    ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
    ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
    ->setLabelFontSize(16)
    ->render()
;

