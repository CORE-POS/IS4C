<?php
/*
Table: origins

Columns:
    originID int
    countryID int
    stateProvID int
    customID int
    local int

Depends on:
    originCountry
    originStateProv
    originCustomRegion

Use:
This table defines locations.
The IDs correspond to the other origin
tables. The local field indicates whether
or not this origin is considered local
by the co-op.
*/
$CREATE['op.origins'] = "
    CREATE TABLE `origins` (
      `originID` INT NOT NULL auto_increment,
      countryID INT,
      stateProvID INT,
      customID INT,
      local TINYINT DEFAULT 0,
      PRIMARY KEY  (`originID`)
    )
";

if ($dbms == "MSSQL"){
    $CREATE['op.origins'] = "
        CREATE TABLE [origins] (
            [originID] [int] IDENTITY (1, 1) NOT NULL ,
            countryID INT,
            stateProvID INT,
            customID INT,
            local TINYINT
        ) ON [PRIMARY]
    ";
}

?>
