<?php
/*
View: vDeptSalesToday

Columns:
    tdate date
    dept_ID int
    total currency
    quantity double

Depends on:
    none

Use:
Same as sumDeptSalesByDay, but just
for the current day
*/
$names = qualified_names();
$CREATE['arch.vDeptSalesToday'] = "
    CREATE VIEW vDeptSalesToday AS
    SELECT
    DATE(tdate) AS tdate,
    department as dept_ID,
    SUM(total) as total,
    SUM(CASE WHEN trans_status='M' THEN itemQtty ELSE quantity END) as quantity
    FROM {$names['trans']}.dlog
    WHERE trans_type IN ('I','D')
    GROUP BY DATE(tdate), department
";
if ($dbms == 'MSSQL'){
    $CREATE['arch.vDeptSalesToday'] = str_replace(" date"," datetime",$CREATE['arch.vDeptSalesToday']);
}
?>
