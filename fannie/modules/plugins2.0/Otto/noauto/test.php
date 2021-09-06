<?php

include('../../../../config.php');
include('../../../../classlib2.0/FannieAPI.php');
include('../OttoMem.php');

$om = new OttoMem();
$om->post(10000, 'teams!!');

