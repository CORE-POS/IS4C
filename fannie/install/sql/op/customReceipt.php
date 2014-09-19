<?php
/*
Table: customReceipt

Columns:
    text varchar
    seq int
    type varchar

Depends on:
    none

Use:
This table contains strings of text
that originally lived in the lane's 
ini.php. At first it was only used
for receipt headers and footers, hence
the name. Submit a patch if you want
a saner name.

Current valid types are:
receiptHeader
receiptFooter
ckEndorse
welcomeMsg
farewellMsg
trainingMsg
chargeSlip

*/
$CREATE['op.customReceipt'] = "
    CREATE TABLE customReceipt (
        text varchar(80),
        seq int,
        type varchar(20),
        PRIMARY KEY (seq, type)
    )
";
?>
