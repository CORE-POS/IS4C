<?php
/*
Table: memDates

Columns:
    card_no int
    start_date datetime
    end_date datetime

Depends on:
    custdata (table)

Use:
Start and end dates for a membership.
Both date fields optional.
*/
$CREATE['op.memDates'] = "
    CREATE TABLE memDates (
        card_no int,
        start_date datetime,
        end_date datetime,
        PRIMARY KEY (card_no)
    )
";
?>
