<?php
/*
View: dheader

Columns:

Depends on:
    dlog_90_view

Use:
Receipt header for dlog_90, basically.
Legacy.
*/
$edept = equity_departments();
if (strlen($edept) <= 2)
    $edept = "(-999)";
$adept = ar_departments();
if (strlen($adept) <= 2)
    $adept = "(-999)";
$CREATE['trans.dheader'] = "
    CREATE   view dheader as
    select
    min(tdate) as proc_date,
    min(tdate) as datetime,
    min(tdate) as starttime,
    max(tdate) as endtime,
    emp_no,
    register_no as till_no,
    register_no as register_no,
    trans_no,
    'N' as trans_type,
    '' as receipt_type,
    card_no as cust_id,
    sum((case when trans_type = 'T' then -1 * total else 0 end)) as total,
    sum((case when trans_type = 'T' then -1*total else 0 end) + (case when trans_type = 'S' then -1*total else 0 end)) as pretax,
    sum((case when trans_type = 'T' then -1*total else 0 end) + (case when trans_type = 'S' then -1*total else 0 end)+(case when upc like 'TAX%' then total else 0 end)) as tot_gross,
    sum((case when trans_status = 'R' then -1 * total else 0 end)) as tot_ref,
    sum((case when trans_status = 'V' then -1 * total else 0 end)) as tot_void,
    sum((case when upc like 'TAX%' then total else 0 end)) as tot_taxA,
    sum((case when trans_type = 'S' then -1*total else 0 end)) as discount,
    sum((case when department IN $adept then total else 0 end)) as arPayments,
    sum((case when department IN $edept then total else 0 end)) as stockPayments,
    sum((case when trans_subtype = 'MI' then -1*total else 0 end)) as chargeTotal,
    SUM((case when upc like '%MAD%' then total else 0 end)) as memCoupons,
    0 as tot_taxB,
    0 as tot_taxC,
    0 as tot_taxD,
    (case when trans_no = 1 then 0 else sum((case when trans_type = 'I' or trans_type = 'D' then 1 else 0 end))  end) as tot_rings,
    ".$con->seconddiff('min(tdate)','max(tdate)')." as time,
    0 as rings_per_min,
    0 as rings_per_total,
    0 as timeon,
    0 as points_earned,
    1 as uploaded,
    0 as points_used,
    trans_num
    from dlog_90_view
    group by trans_num,emp_no, register_no, trans_no, card_no
";
?>
