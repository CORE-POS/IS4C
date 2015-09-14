<?php
/*
Table: subdepts

Columns:
    subdept_no smallint
    subdept_name varchar
    dept_ID smallint

Depends on:
    departments (table)

Use:
A department may contain multiple subdepartments.
In most implementations I've seen, invidual products
can be tagged with a subdepartment, but that
setting doesn't go into the final transaction log
*/
$CREATE['op.subdepts'] = "
    CREATE TABLE `subdepts` (
      `subdept_no` smallint(4) NOT NULL, 
      `subdept_name` varchar(30) default NULL,
      `dept_ID` smallint(4) default NULL,
      PRIMARY KEY `subdept_no` (`subdept_no`),
      KEY `subdept_name` (`subdept_name`)
    ) 
";

if ($dbms == "MSSQL"){
    $CREATE['op.subdepts'] = "
        CREATE TABLE subdepts (
        subdept_no smallint,
        subdept_name varchar(30),
        dept_ID smallint
        )
    ";
}

?>
