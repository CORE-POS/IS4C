<?php
/*
Table: employees

Columns:
    emp_no int  
    CashierPassword varchar
    AdminPassword varchar
    FirstName varchar
    LastName varchar
    JobTitle varchar
    EmpActive int
    frontendsecurity int
    backendsecurity int
    birthdate datetime
    

Depends on:
    none

Use:
Table of cashiers. emp_no identifies
a cashier uniquely. CashierPassword and
AdminPassword are numeric passcodes used
pretty interchangably (should probably match).
EmpActive toggles whether an account can
actually log in. frontendsecurity is used
to restrict certain actions at the register
based on security level.
*/
$CREATE['op.employees'] = "
    CREATE TABLE employees (
        emp_no smallint,
        CashierPassword VARCHAR(50),
        AdminPassword VARCHAR(50),
        FirstName varchar(255),
        LastName varchar(255),
        JobTitle varchar(255),
        EmpActive tinyint,
        frontendsecurity smallint,
        backendsecurity smallint,
        birthdate datetime,
        PRIMARY KEY (emp_no))
";
?>
