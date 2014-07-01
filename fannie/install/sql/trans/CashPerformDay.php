<?php
/*
Table: CashPerformDay

Columns:
    proc_date datetime
    emp_no int
    trans_num char
    startTime datetime
    endTime datetime
    transInterval int
    items int
    rings int
    cancels int
    card_no int

Depends on:
    dlog_90_view (view)

Use:
Stores cashier performance metrics to
speed up reporting. 
*/
$CREATE['trans.CashPerformDay'] = "
    CREATE TABLE CashPerformDay
    (proc_date datetime,
    emp_no smallint,
    trans_num varchar(25),
    startTime datetime,
    endTime datetime,
    transInterval int,
    items float,
    rings int,
    Cancels int,
    card_no int,
    INDEX(emp_no)
    )
";
?>
