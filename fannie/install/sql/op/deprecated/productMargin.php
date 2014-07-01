<?php
/*
View: productMargin

Columns:
    upc int or varchar, dbms dependent
    cost dbms currency
    normal_price dbms currency
    actualMargin (calculated)
    desiredMargin decimal(10,5)
    srp (calculated)

Depends on:
    products (table)
    deptMargin (table)

Use:
Calculates SRP required to meet desired margin
as well as current margin
*/
$CREATE['op.productMargin'] = "
    create view productMargin as
    select upc,
    cost,normal_price,
    (normal_price-cost) / normal_price as actualMargin,
    d.margin as desiredMargin,
    convert(cost/(1-d.margin),decimal(10,2)) as srp
    from products as p left join deptMargin as d
    on p.department=d.dept_ID
    where normal_price > 0
    and d.margin <> 1
";

if ($dbms == 'mssql'){
    $CREATE['op.productMargin'] = "
        create view productMargin as
        select upc,
        cost,normal_price,
        (normal_price-cost) / normal_price as actualMargin,
        d.margin as desiredMargin,
        convert(numeric(10,2),cost/(1-d.margin)) as srp
        from products as p left join deptMargin as d
        on p.department=d.dept_ID
        where normal_price > 0
        and d.margin <> 1
    ";
}

?>
