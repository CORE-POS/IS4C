<?php
/*
View: unfi_diff

Columns:
    upc varchar
    upcc varchar
    description varchar
    item_desc varchar
    wholesale dbms currency
    vd_cost dbms currency
    normal_price dbms currency
    unfi_sku varchar
    wfc_srp dbms currency
    cat int
    department int
    our_margin (calculated)
    unfi_margin (calculated)
    diff int

Depends on:
    products (table)
    unfi_order (table)

Use:
The same as unfi_all, except items whose current
price matches the SRP are omitted.

Deprecated. Use the vendor tables instead.
This stuff shouldn't be tied to one vendor.
*/
$CREATE['op.unfi_diff'] = "
    CREATE view unfi_diff as 
        select p.upc,u.upcc, 
        p.description,
        u.item_desc,
        u.wholesale,
        u.vd_cost,
        p.normal_price,
        u.unfi_sku,
        u.wfc_srp,
        u.cat,
        p.department,
        CASE WHEN p.normal_price = 0 THEN 0 ELSE
        CONVERT((p.normal_price - (u.vd_cost/u.pack))/p.normal_price,decimal(10,2)) 
        END as our_margin,
        CONVERT((u.wfc_srp - (u.vd_cost/u.pack))/ u.wfc_srp,decimal(10,2))
        as unfi_margin,
        case when u.wfc_srp > p.normal_price then 1 else 0 END as diff
        from products as p 
        right join unfi_order as u 
        on u.upcc=p.upc
        where 
        p.normal_price <> u.wfc_srp and
        p.upc is not NULL
";

if ($dbms == 'MSSQL'){
    $CREATE['op.unfi_diff'] = "
        CREATE view unfi_diff as 
            select p.upc,u.upcc, 
            p.description,
            u.item_desc,
            u.wholesale,
            u.vd_cost,
            p.normal_price,
            u.unfi_sku,
            u.wfc_srp,
            u.cat,
            p.department,
            CASE WHEN p.normal_price = 0 THEN 0 ELSE
            CONVERT(decimal(10,5),(p.normal_price - (u.vd_cost/u.pack))/p.normal_price) 
            END as our_margin,
            CONVERT(decimal(10,5),(u.wfc_srp - (u.vd_cost/u.pack))/ u.wfc_srp)
            as UNFI_margin,
            case when u.wfc_srp > p.normal_price then 1 else 0 END as diff
            from products as p 
            right join unfi_order as u 
            on left(u.upcc,13)=p.upc
            where 
            p.normal_price <> u.wfc_srp and
            p.upc is not NULL
    ";
}
?>
