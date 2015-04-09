<?php
/*
Table: Stores

Columns:
    storeID int
    description varchar
    dbHost VARCHAR(50)
    dbDriver VARCHAR(15)
    dbUser VARCHAR(25)
    dbPassword VARCHAR(25)
    transDB VARCHAR(20)
    opDB VARCHAR(20)
    push int
    pull int

Depends on:
    none

Use:
List of known stores. By convention
storeID zero should NOT be used; it represents
all stores combined.

The local store should have an entry containing at 
least dbHost so it can identify itself. The other
database credentials are not necessary for the
local store since they must be known already to
access the table.

Entries for remote stores need full credentials.
Setting up user accounts with read-only to remote
store databases should work fine.
*/
$CREATE['op.Stores'] = "
    CREATE TABLE Stores (
        storeID INT NOT NULL AUTO_INCREMENT,
        description VARCHAR(50),
        dbHost VARCHAR(50),
        dbUser VARCHAR(25),
        dbPassword VARCHAR(25),
        transDB VARCHAR(20),
        opDB VARCHAR(20),
        push TINYINT DEFAULT 1,
        pull TINYINT DEFAULT 1,
        PRIMARY KEY (storeID)
    )
";

if ($dbms == "MSSQL"){
    $CREATE['op.Stores'] = str_replace('NOT NULL AUTO_INCREMENT', 
                                              'IDENTITY (1, 1) NOT NULL', 
                                              $CREATE['op.Stores']);
} 

