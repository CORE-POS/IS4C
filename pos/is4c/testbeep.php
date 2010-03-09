<?php
$beep = "echo -e 'S334\\r' > /dev/ttyS0";
exec($beep);

?>