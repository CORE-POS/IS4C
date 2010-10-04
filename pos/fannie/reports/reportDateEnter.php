<?php
$page_title = 'Fannie - Reports Module';
$header = 'Dayend Report';
include('../src/header.html');

echo '<script src="../src/CalendarControl.js" language="javascript"></script>
<script src="../src/putfocus.js" language="javascript"></script>

<form action=reportDate.php name=datelist method=post target=_blank>
<input type=text size=10 name=date onclick="showCalendarControl(this);">
Pick a date to run that days dayend report
<br><br>
<input name=Submit type=submit value=submit>
</form>';

include('../src/footer.html');
?>

