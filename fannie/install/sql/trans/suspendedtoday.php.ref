<?php
/*
View: suspendedtoday

Columns:
    identical to dtransactions

Depends on:
    suspended (table)

Use:
This view omits all entries in suspended
that aren't from the current day. Resuming
a transaction from a previous day wouldn't
necessarily cause problems, but "stale"
suspended transactions that never get resumed
could eventually make the list of available
transactions unwieldy.
*/
$CREATE['trans.suspendedtoday'] = "
    CREATE VIEW suspendedtoday AS
    SELECT * FROM suspended WHERE "
    .$con->datediff($con->now(),'datetime')." = 0
";
?>
