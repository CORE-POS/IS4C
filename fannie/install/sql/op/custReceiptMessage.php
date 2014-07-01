<?php
/*
Table: custReceiptMessage

Columns:
    card_no int
    msg_text varchar    
    modifier_module varchar

Depends on:
    custdata (table)

Use:
Create member-specific messages for
receipts.

- card_no is the member number
- msg_text is the message itself
- modifier_module is [optionally] the name
  of a class that should be invoked
  to potentially modify the message.
  An equity message, for example, might
  use a modifier module to check and see
  if payment was made in the current 
  transaction
*/
$CREATE['op.custReceiptMessage'] = "
    CREATE TABLE custReceiptMessage (
        card_no int,
        msg_text varchar(255),
        modifier_module varchar(50)
    )
";
?>
