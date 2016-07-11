<?php
/*
Table: transarchive

Columns:
    identical to dtransactions

Depends on:
    dtransactions (table)

Use:
This is a look-up table. Under WFC's day
end polling, transarchive contains the last
90 days' transaction entries. For queries
in that time frame, using this table can
simplify or speed up queries.

Maintenance:
cron/nightly.dtrans.php appends all of dtransactions
 and deletes records older than 90 days.
*/


/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    * 23Oct2012 Eric Lee Added Maintenance section.

*/

$CREATE['trans.transarchive'] = duplicate_structure($dbms,
                    'dtransactions','transarchive');
?>
