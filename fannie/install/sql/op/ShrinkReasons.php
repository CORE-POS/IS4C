<?php
/*
Table: ShrinkReasons

Columns:
    shrinkReasonID int
    description varchar

Depends on:
    none

Use:
Maintain list of reasons for marking
shrink

*/
$CREATE['op.ShrinkReasons'] = "
    CREATE TABLE ShrinkReasons (
        shrinkReasonID INT NOT NULL AUTO_INCREMENT,
        description VARCHAR(30),
        PRIMARY KEY (shrinkReasonID)
    )
";

if ($dbms == "MSSQL"){
    $CREATE['op.ShrinkReasons'] = str_replace('NOT NULL AUTO_INCREMENT', 
                                              'IDENTITY (1, 1) NOT NULL', 
                                              $CREATE['op.ShrinkReasons']);
} 

