<?php
/*
Table: localtranstoday

Columns:
	identical to dtransactions

Use:
Contains today's transactions. 
Truncating this table
daily will yield better performance on some actions
that reference the current day's info - for example,
reprinting receipts.
*/
$CREATE['trans.localtranstoday'] = InstallUtilities::duplicateStructure($dbms,'dtransactions','localtranstoday');

if ($CREATE['trans.localtranstoday'] !== false) {
    $CREATE['trans.localtranstoday'] = array(
                                            $CREATE['trans.localtranstoday'],
                                            'ALTER TABLE localtranstoday DROP COLUMN pos_row_id',
                                            'ALTER TABLE localtranstoday ADD INDEX (trans_no)',
                                            'ALTER TABLE localtranstoday ADD INDEX (emp_no)',
                                            'ALTER TABLE localtranstoday ADD INDEX (register_no)',
                                            'ALTER TABLE localtranstoday ADD INDEX (datetime)',
                                            );
}
?>
