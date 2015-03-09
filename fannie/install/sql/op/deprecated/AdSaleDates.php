<?php
/*
Table: AdSaleDates

Columns:
    sale_name varchar
    start_date datetime
    end_date datetime

Depends on:
    none

Use:
Sales start & end dates for
generating signage
*/
$CREATE['op.AdSaleDates'] = "
    CREATE TABLE AdSaleDates (
        sale_name varchar(50),
        start_date datetime,
        end_date datetime,
        PRIMARY KEY (sale_name)
    )
";
?>
