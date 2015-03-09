<?php
/*
Table: ServiceScales

Columns:
    serviceScaleID int
    description varchar

Depends on:
    none

Use:
*/
$CREATE['op.ServiceScales'] = "
    CREATE TABLE ServiceScales (
        serviceScaleID INT NOT NULL AUTO_INCREMENT,
        description VARCHAR(50),
        host VARCHAR(50),
        scaleType VARCHAR(50),
        scaleDeptName VARCHAR(25),
        superID INT,
        PRIMARY KEY (serviceScaleID)
    )
";

if ($dbms == "MSSQL") {
    $CREATE['op.ServiceScales'] = str_replace('NOT NULL AUTO_INCREMENT', 
                                              'IDENTITY (1, 1) NOT NULL', 
                                              $CREATE['op.ServiceScales']);
} 

