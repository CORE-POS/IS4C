<?php
/*
View: CashPerformDay

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
View of transaction timing to generate
cashier performance reports
*/
$CREATE['trans.CashPerformDay'] = "
    CREATE VIEW CashPerformDay AS
    SELECT
    min(tdate) as proc_date,
    max(emp_no) as emp_no,
    max(trans_num) as Trans_Num,
    min(tdate) as startTime,
    max(tdate) as endTime,
    CASE WHEN ".$con->seconddiff('min(tdate)', 'max(tdate)')." =0 
        then 1 else 
        ".$con->seconddiff('min(tdate)', 'max(tdate)') ."
    END as transInterval,
    sum(CASE WHEN abs(quantity) > 30 THEN 1 else abs(quantity) END) as items,
    Count(upc) as rings,
    SUM(case when trans_status = 'V' then 1 ELSE 0 END) AS Cancels,
    max(card_no) as card_no
    from dlog_90_view 
    where trans_type IN ('I','D','0','C')
    group by year(tdate),month(tdate),day(tdate),trans_num
";
?>
