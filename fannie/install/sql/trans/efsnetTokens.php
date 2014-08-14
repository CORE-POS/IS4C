<?php
/*
Table: efsnetTokens

Columns:
    expireDay datetime
    refNum varchar
    token varchar
    processData varchar
    acqRefData

Depends on:
    efsnetRequest (table)
    efsnetResponse (table)

Use:
This table logs tokens used for modifying
later transactions.

expireDay is when(ish) the token is no longer valid

refNum maps to efsnetRequest & efsnetResponse
records

token is the actual token

processData and acqRefData are additional
values needed in addition to the token for
certain kinds of modifying transactions
*/
$CREATE['trans.efsnetTokens'] = "
    CREATE TABLE efsnetTokens (
        expireDay datetime, 
        refNum varchar(50),
        token varchar(100),
        processData varchar(255),
        acqRefData varchar(255),
        PRIMARY KEY (refNum,token)
    )
";
?>
