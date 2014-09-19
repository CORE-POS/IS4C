<?php
/*
View: checkOver

Columns:

Depends on:
    dlog

Use:
*/
$CREATE['trans.checkOver'] = "
    CREATE  VIEW checkOver AS
    select sum(case when trans_subtype='ck' then total else 0 end) as ckTotal,
    sum(case when trans_subtype='CA' then total else 0 end) as caTotal, 
    max(card_no) as card_no 
    from dlog where trans_type='T' 
    group by trans_num having ckTotal < 0 and caTotal > 0
";
?>
