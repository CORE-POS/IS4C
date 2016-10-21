<?php
/*
View: TenderTapeGeneric

Columns:
    tdate datetime
    emp_no int
    register_no int
    trans_no int
    trans_subtype (calculated)
    total (calculated)

Depends on:
    dlog (view)

Use:
This view lists all a cashier's 
tenders for the day. It is used for 
generating tender reports at the registers.

Ideally this deprecates the old system of
having a different view for every tender
type.

Behavior in calculating trans_subtype and
total may be customized on a per-co-op
basis without changes to the register code
*/
$CREATE['trans.TenderTapeGeneric'] = "
    CREATE view TenderTapeGeneric
        as
        select 
        max(tdate) as tdate, 
        emp_no, 
        register_no,
        trans_no,
        CASE WHEN trans_subtype = 'CP' AND upc LIKE '%MAD%' THEN ''
             WHEN trans_subtype IN ('EF','EC','TA') THEN 'EF'
             ELSE trans_subtype
        END AS tender_code,
        -1 * sum(total) as tender
        from dlog
        where ".$con->datediff($con->now(),'tdate')."= 0
        and trans_subtype not in ('0','')
        GROUP BY emp_no, register_no, trans_no,
        tender_code
";

