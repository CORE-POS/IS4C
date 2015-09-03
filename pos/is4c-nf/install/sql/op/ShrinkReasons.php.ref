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
} else if ($dbms == "PDOLITE") {
    // swap where PRIMARY KEY appears, remove trailing comma on description line
    $CREATE['op.ShrinkReasons'] = str_replace('PRIMARY KEY (shrinkReasonID)',
                                              '', 
                                              $CREATE['op.ShrinkReasons']);
    $CREATE['op.ShrinkReasons'] = str_replace('description VARCHAR(30),',
                                              'description VARCHAR(30)', 
                                              $CREATE['op.ShrinkReasons']);
    $CREATE['op.ShrinkReasons'] = str_replace('NOT NULL AUTO_INCREMENT', 
                                              'PRIMARY KEY AUTOINCREMENT', 
                                              $CREATE['op.ShrinkReasons']);
}
