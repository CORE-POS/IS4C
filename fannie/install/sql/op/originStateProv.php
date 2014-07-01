<?php
/*
Table: originStateProv

Columns:
    stateProvID int
    name varchar

Depends on:
    origins

Use:
This table lists major sub-national
divisions (e.g., US States, Canadian
Provinces)
*/
$CREATE['op.originStateProv'] = "
    CREATE TABLE `originStateProv` (
      `stateProvID` INT NOT NULL auto_increment,
      name VARCHAR(50),
      PRIMARY KEY  (`stateProvID`)
    )
";

if ($dbms == "MSSQL"){
    $CREATE['op.originStateProv'] = "
        CREATE TABLE [originStateProv] (
            [stateProvID] [int] IDENTITY (1, 1) NOT NULL ,
            name VARCHAR(50)
        ) ON [PRIMARY]
    ";
}

?>
