<?php
/*
View: dddItems

Columns:
    year
    month
    day
    upc
    description
    dept_no
    dept_name
    quantity
    total

Depends on:
    dtransactions (table)
    transarchive (table)

Use:
List of DDD (dropped, dented, damaged)
items entered at the tills
*/
$names = qualified_names();
$CREATE['trans.dddItems'] = "
    CREATE     view dddItems as
    SELECT 
    year(datetime) as year,
    month(datetime) as month,
    day(datetime) as day,
    d.upc,d.description,
    e.dept_no,e.dept_name,
    sum(d.quantity) as quantity,
    sum(d.total) as total
    FROM
    dtransactions as d
    LEFT JOIN {$names['op']}.departments as e
    ON d.department=e.dept_no
    WHERE trans_status='Z'
    AND trans_type in ('D','I')
    AND trans_subtype = ''
    AND emp_no <> 9999
    AND register_no <> 99
    and ".$con->datediff($con->now(),'datetime')."=0
    GROUP BY 
    year(datetime),
    month(datetime),
    day(datetime),
    d.upc,d.description,
    e.dept_no,e.dept_name

    union all

    SELECT 
    year(datetime) as year,
    month(datetime) as month,
    day(datetime) as day,
    d.upc,d.description,
    e.dept_no,e.dept_name,
    sum(d.quantity) as quantity,
    sum(d.total) as total
    FROM
    transarchive as d
    LEFT JOIN {$names['op']}.departments as e
    ON d.department=e.dept_no
    WHERE trans_status='Z'
    AND trans_type in ('D','I')
    AND trans_subtype = ''
    AND emp_no <> 9999
    AND register_no <> 99
    GROUP BY 
    year(datetime),
    month(datetime),
    day(datetime),
    d.upc,d.description,
    e.dept_no,e.dept_name
";
?>
