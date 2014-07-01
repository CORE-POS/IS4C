<?php
/*
Table: AR_EOM_Summary

Columns:
    card_no int
    memName varchar
    priorBalance money
    threeMonthCharges money
    threeMonthPayments money
    threeMonthBalance money
    twoMonthCharges money
    twoMonthPayments money
    twoMonthBalance money
    lastMonthCharges money
    lastMonthPayments money
    lastMonthBalance money

Use:
List of customer start/end AR balances
over past few months

Maintenance:
cron/nightly.ar.php, after updating ar_history,
 truncates ar_history_backup and then appends all of ar_history
 to it, giving new data for AR_EOM_Summary.

*/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    * 22Oct2012 EL Add Maintenance section
  * 20Oct2012 Eric Lee Fix capitalization in array index.
    *                    Add MSSQL version.

*/

$CREATE['trans.AR_EOM_Summary'] = "
    CREATE TABLE AR_EOM_Summary(
    cardno int,
    memName varchar(100),
    priorBalance decimal(10,2),
    threeMonthCharges decimal(10,2),
    threeMonthPayments decimal(10,2),
    threeMonthBalance decimal(10,2),    
    twoMonthCharges decimal(10,2),
    twoMonthPayments decimal(10,2),
    twoMonthBalance decimal(10,2),  
    lastMonthCharges decimal(10,2),
    lastMonthPayments decimal(10,2),
    lastMonthBalance decimal(10,2), 
    PRIMARY KEY (cardno)
    )
";
