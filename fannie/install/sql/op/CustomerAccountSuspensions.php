<?php
/*
Table: CustomerAccountSuspensions

Columns:
    username varchar
    postdate datetime
    post text
    cardno int
    reasoncode int

Depends on:
    none

Use:
*/
$CREATE['op.CustomerAccountSuspensions'] = "
    CREATE TABLE CustomerAccountSuspensions (
        customerAccountSuspensionID INT NOT NULL AUTO_INCREMENT,
        card_no INT,
        active TINYINT,
        tdate DATETIME,
        suspensionTypeID SMALLINT,
        reasonCode INT,
        legacyReason TEXT,
        username VARCHAR(50),
        savedType VARCHAR(10),
        savedMemType SMALLINT,
        savedDiscount SMALLINT,
        savedChargeLimit " . $con->currency() . ",
        savedMailFlag TINYINT,
        PRIMARY KEY (customerAccountSuspensionID),
        INDEX (card_no),
        INDEX (active)
    )
";
if ($dbms == "MSSQL") {
    $CREATE['op.CustomerAccountSuspensions'] = str_replace('AUTO_INCREMENT', 'IDENTITY(1, 1)', $CREATE['op.CustomerAccountSuspensions']);
}
