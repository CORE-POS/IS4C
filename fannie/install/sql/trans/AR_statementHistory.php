<?php
/*
View: AR_statementHistory

Columns:
    card_no int
    charges currency
    payments currency
    tdate datetime
    trans_num char
    upc char
    description char
    dept_name char

Depends on:
    ar_history (table)
    transarchive (table)
    dtransactions (table)

Use:
Line items for generating AR
statement PDFs. Legacy/WFC related; can
normally be ignored
*/
$dlist = ar_departments();
if (strlen($dlist <= 2))
    $dlist = "(-999)";
$CREATE['trans.AR_statementHistory'] = "
    CREATE VIEW AR_statementHistory AS

    SELECT a.card_no,a.charges,a.payments,
    tdate as date,
    a.trans_num,'' as upc,
    'Payment - Thank You' as description,
    '' as dept_name
    FROM ar_history as a
    where ".$con->datediff('a.tdate',$con->now())." > -91
    and ".$con->monthdiff('a.tdate',$con->now())." <= 0
    AND a.payments > 0

    UNION ALL

    SELECT 
    a.card_no, a.charges, a.payments,
    ".$con->convert('a.tdate','char')." as date,
    a.trans_num,
    d.upc,
    case when (d.trans_type='T' AND register_no=20) THEN 'Gazette Advertisement'
    ELSE d.description
    END as description,
    d.description as dept_name
    FROM ar_history as a LEFT JOIN
    transarchive as d ON ".$con->datediff('a.tdate','d.datetime')."=0 and 
    a.trans_num=".
    $con->concat($con->convert('d.emp_no','char'),"'-'",
        $con->convert('d.register_no','char'),"'-'",
        $con->convert('d.trans_no','char'),'')
    ."
    where ".$con->datediff('a.tdate',$con->now())."> -91
    and ".$con->monthdiff('a.tdate',$con->now())." <= 0
    and d.trans_status <> 'X'
    AND a.payments <= 0
    and (
    (d.trans_type in ('I','D') and d.trans_subtype not in ('0','CP'))
    or
    (d.trans_type in ('T') and register_no=20 and ".$con->monthdiff('tdate','2009-05-01')."=0)
    )

    union all

    SELECT d.card_no, 
    case when d.trans_subtype='MI' then -d.total else 0 end as charges,
    case when d.department IN $dlist then d.total else 0 end as payments,
    ".$con->convert('d.datetime','char')." as date,".
    $con->concat($con->convert('d.emp_no','char'),"'-'",
        $con->convert('d.register_no','char'),"'-'",
        $con->convert('d.trans_no','char'),'')
    ." as trans_num,
    d.upc,
    case 
    when (d.department IN $dlist) then 'Payment - Thank You' 
    ELSE a.description
    END as description,
    d.description as dept_name
    FROM dtransactions as d left join dtransactions as a
    on d.register_no=a.register_no and d.emp_no=a.emp_no
    and d.trans_no=a.trans_no
    where (d.department IN $dlist or d.trans_subtype='MI') AND
    d.trans_status <> 'X' AND
    (a.trans_type in ('I','D') and a.trans_subtype not in ('0','CP'))
";
?>
