<?php
/*
Table: originCountry

Columns:
    countryID int
    name varchar

Depends on:
    origins

Use:
This table lists countries
*/
$CREATE['op.originCountry'] = "
    CREATE TABLE `originCountry` (
      `countryID` INT NOT NULL auto_increment,
      name VARCHAR(50),
      PRIMARY KEY  (`countryID`)
    )
";

if ($dbms == "MSSQL"){
    $CREATE['op.originCountry'] = "
        CREATE TABLE [originCountry] (
            [countryID] [int] IDENTITY (1, 1) NOT NULL ,
            name VARCHAR(50)
        ) ON [PRIMARY]
    ";
}

?>
