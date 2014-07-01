<?php
/*
Table: superDeptEmails

Columns:
    superID int
    email_address varchar

Depends on:
    superdepts (table)

Use:
Associating a person or people with
a super department for the purpose of
notifications. 

There is one record per super department
but the email_address field may contain
multiple addresses in a comma-separated
list or whatever your mail server 
understands.
*/
$CREATE['op.superDeptEmails'] = "
    CREATE TABLE superDeptEmails (
        superID INT,
        email_address VARCHAR(255),
        PRIMARY KEY (superID)
    )
";

