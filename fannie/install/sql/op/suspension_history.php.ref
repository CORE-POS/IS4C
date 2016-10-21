<?php
/*
Table: suspension_history

Columns:
    username varchar
    postdate datetime
    post text
    cardno int
    reasoncode int

Depends on:
    suspensions (table)

Use:
This table just keeps a record of member accounts
being suspended and restored
*/
$CREATE['op.suspension_history'] = "
    CREATE TABLE suspension_history (
        username varchar(50),
        postdate datetime,
        post text,
        cardno int,
        reasoncode int
    )
";
?>
