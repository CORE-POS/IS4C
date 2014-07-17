<?php
/*
View: dlog_90_view

Columns:
    tdate datetime
    register_no int
    emp_no int
    trans_no int
    upc varchar
    trans_type varchar
    trans_subtype varchar
    trans_status varchar
    department int
    quantity double
    unitPrice dbms currency
    total dbms currency
    tax int
    foodstamp int
    ItemQtty double
    card_no int
    trans_id int
    pos_row_id int
    store_row_id int
    trans_num varchar

Depends on:
    transarchive (table)

Use:
This view applies the same restrictions
as dlog but to the table transarchive.
With WFC's dayend polling, transarchive
contains transaction entries from the past
90 days, hence the name of this view.
For queries in the given time frame, using
the view can be faster or simpler than
alternatives.
*/
$CREATE['trans.dlog_90_view'] = "
    CREATE VIEW dlog_90_view AS
        SELECT
        datetime AS tdate,
        register_no,
        emp_no,
        trans_no,
        upc,
        description,
        CASE WHEN (trans_subtype IN ('CP','IC') OR upc like('%000000052')) then 'T' WHEN upc = 'DISCOUNT' then 'S' else trans_type end as trans_type,
        CASE WHEN upc = 'MAD Coupon' THEN 'MA' 
           WHEN upc like('%00000000052') THEN 'RR' ELSE trans_subtype END as trans_subtype,
        trans_status,
        department,
        quantity,
        scale,
        cost,
        unitPrice,
        total,
        regPrice,
        tax,
        foodstamp,
        discount,
        memDiscount,
        discountable,
        discounttype,
        voided,
        percentDiscount,
        ItemQtty,
        volDiscType,
        volume,
        VolSpecial,
        mixMatch,
        matched,
        memType,
        staff,
        numflag,
        charflag,
        card_no,
        trans_id,
        pos_row_id,
        store_row_id,
        ".$con->concat(
            $con->convert('emp_no','char'),"'-'",
            $con->convert('register_no','char'),"'-'",
            $con->convert('trans_no','char'),'')
        ." as trans_num
        FROM transarchive
        WHERE trans_status NOT IN ('D','X','Z')
        AND emp_no <> 9999 and register_no <> 99
";
?>
