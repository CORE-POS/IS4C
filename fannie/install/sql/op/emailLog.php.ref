<?php
/*
Table: emailLog

Columns:
    tdate datetime
    card_no int
    send_addr varchar
    message type

Depends on:
    none

Use:
WFC sends emails to members who owe
equity or store charge balances. This just
logs who was sent what when.
*/
$CREATE['op.emailLog'] = "
    CREATE TABLE emailLog (
        tdate datetime,
        card_no int,
        send_addr varchar(150),
        message_type varchar(100)
    )
";
?>
