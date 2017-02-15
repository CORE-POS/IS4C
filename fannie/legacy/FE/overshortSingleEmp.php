<?php
include('../../config.php');

$date = filter_input(INPUT_GET, 'date');
$emp_no = filter_input(INPUT_GET, 'emp_no');

header('Location: '.$FANNIE_URL.'modules/plugins2.0/OverShortTools/OverShortDayPage.php?action=date&arg='.$date.'&emp_no='.$emp_no);

