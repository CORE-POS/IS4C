<?php
/*
View: vRingSalesToday

Columns:
    tdate date
    upc varchar
    total currency,
    quantity double

Depends on:
    none

Use:
Same as sumRingSalesByDay, but just
for the current day
*/
$names = qualified_names();
$CREATE['arch.vRingSalesToday'] = "
    CREATE VIEW vRingSalesToday AS
    SELECT
    DATE(tdate) AS tdate,
    upc,
    department as dept,
    SUM(total) as total,
    SUM(CASE WHEN trans_status='M' THEN itemQtty ELSE quantity END) as quantity
    FROM {$names['trans']}.dlog
    WHERE trans_type IN ('I','D')
    AND upc <> '0'
    GROUP BY DATE(tdate), upc, department
";
if ($dbms == 'MSSQL'){
    $CREATE['arch.vRingSalesToday'] = str_replace(" date"," datetime",$CREATE['arch.vRingSalesToday']);
}
?>
