<?php
/*
Table: originCustomRegion

Columns:
    customID int
    name varchar

Depends on:
    origins

Use:
This table lists custom defined
regions (counties, neighborhoods,
internal jargon, etc)
*/
$CREATE['op.originCustomRegion'] = "
    CREATE TABLE `originCustomRegion` (
      `customID` INT NOT NULL auto_increment,
      name VARCHAR(50),
      PRIMARY KEY  (`customID`)
    )
";

if ($dbms == "MSSQL"){
    $CREATE['op.originCustomRegion'] = "
        CREATE TABLE [originCustomRegion] (
            [customID] [int] IDENTITY (1, 1) NOT NULL ,
            name VARCHAR(50)
        ) ON [PRIMARY]
    ";
}

?>
