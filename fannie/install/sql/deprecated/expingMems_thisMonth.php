<?php
/*
View: expingMems

Columns:

Depends on:
    meminfo
    custdata
    newBalanceStockToday_test

Use:
Contact info for members that will expire
this month
*/
$names = qualified_names();
$CREATE['op.expingMems_thisMonth'] = "
CREATE VIEW expingMems_thisMonth as
    select 
    m.street,
    m.city,
    m.state,
    m.zip,
    m.card_no as memnum,
    n.payments,
    n.startdate,
    ".$con->convert('d.end_date','char')." as enddate,
    c.type,
    c.memType
    from meminfo as m,{$names['trans']}.newBalanceStockToday_test as n,custdata as c, memDates as d
    where d.end_date is not null
    AND d.end_date <> ''
    AND m.card_no = n.memnum
    AND c.CardNo = m.card_no
    AND m.card_no = d.card_no
    AND n.payments < 100
    AND c.type <> 'REG'
    AND ".$con->monthdiff('d.end_date',$con->now())." = 0
    AND c.personNum = 1
";
?>
