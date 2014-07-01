<?php
/*
Table: PaycardTransaction

Columns:
    paycardTransactionID int
    dateID int
    empNo int
    registerNo int
    transNo int
    transID int
    previousPaycardTransactionID int
    processor varchar
    refNum varchar
    live tinyint
    cardType varchar
    transType varchar
    amount double
    PAN varchar
    issuer varchar
    name varchar
    manual tinyint
    requestDatetime datetime
    responseDatetime datetime
    seconds float
    commErr int
    httpCode int
    validResponse tinyint
    xResultCode varchar
    xApprovalNumber varchar
    xResponseCode varchar
    xResultMessage varchar
    xTransactionID varchar
    xBalance varchar
    xToken varchar
    xProcessorRef varchar
    xAcquirerRef varchar

Depends on:
    none

Use:
This table records information about integrated
card transactions.

The first set of columns simply identifies the
transaction.
  * paycardTransactionID is an identity column.
    It should be unique at the lane level and
    unique in conjunction with registerNo at
    the server level.
  * dateID, empNo, registerNo, transNo, and transID
    refer to the corresponding tender record in
    dtransaction. The camelCase names instead of
    underscores are for compliance with newer project
    style guidlines.
  * previousPaycardTransactionID refers to a previous
    record in this table. A void transaction should
    refer to the previous approved transaction.

The next set of column has information about what
was sent to the server.
  * processor is the name of the PHP class that's
    making the request
  * refNum is a reference number sent to the processor.
    This is usually just a memo field that will come
    back unchanged on the response.
  * live indicates whether it's a live or testing
    transaction
  * cardType is Credit, Debit, etc
  * transType is Sale, Return, etc
  * amount is the Sale, Return, etc amount. This value
    is always positive.
  * PAN is the last four digits of the card. Do not
    record full card numbers for credit or debit cards.
    May be permissible with some gift card providers to
    keep the whole number.
  * issuer is Visa, MasterCard, etc
  * name is the cardholder's name. This is not always 
    available and depends on what's on the magnetic stripe.
  * manual indicates how the card was entered. 1 means
    keyed in, 0 means swiped.
  * requestDatetime is a timestamp when the request was sent

The last set of fields deal with the response. Fields
that start with "x" are data returned by the processor.
  * responseDatetime is a timestamp when the response was received
  * seconds indicates how long the request took
  * commErr is cURL error code, if any
  * httpCode is the HTTP response code
  * validResponse is a normalized indicator of what happened.
    Not all processors use the same codes for approve, decline, etc
      0 => no response at all
      1 => approved
      2 => declined
      3 => processor reported error
      4 => response was malformed
  * xResultCode indicates what happened - typically approved,
    declined, or an error
  * xApprovalNumber is the actual authorization number
  * xResponseCode is further data about the result such as
    a specific error code or decline code
  * xResultMessage is a descriptive text response of the
    the result or response code
  * xTransactionID is an additional processor reference like a
    sequence number
  * xBalance is remaining balance on the card
  * xToken is a reference  value for making future modifications 
    to the transaction
  * xProcessorRef is another reference number field
  * xAcquirerRef is another reference number field

Not all processors will provide data for all fields. At minimum,
there should be a value for validResponse to show whether the
transaction was approved. On approved transactions, there needs
to be an xApprovalNumber and some kind of sequence or reference 
number in xTransactionID. There should be some value indicating
approval in xResultMessage. All the other response fields
are optional.
*/
$CREATE['trans.PaycardTransactions'] = "
    CREATE TABLE PaycardTransactions (
        paycardTransactionID INT, 
        dateID INT ,
        empNo INT ,
        registerNo INT ,
        transNo INT ,
        transID INT ,
        previousPaycardTransactionID INT,
        processor VARCHAR(25),
        refNum VARCHAR(50) ,
        live tinyint ,
        cardType VARCHAR(15) ,
        transType VARCHAR(15) ,
        amount DOUBLE ,
        PAN VARCHAR (19) ,
        issuer VARCHAR (20) ,
        name VARCHAR (50) ,
        manual TINYINT ,
        requestDatetime DATETIME,
        responseDatetime DATETIME,
        seconds FLOAT ,
        commErr SMALLINT ,
        httpCode SMALLINT ,
        validResponse SMALLINT ,
        xResultCode VARCHAR (8), 
        xApprovalNumber VARCHAR (20),
        xResponseCode VARCHAR (8),
        xResultMessage VARCHAR (100),
        xTransactionID VARCHAR (12),
        xBalance VARCHAR(8),
        xToken VARCHAR(64),
        xProcessorRef VARCHAR(24),
        xAcquirerRef VARCHAR(100),
        INDEX(paycardTransactionID),
        INDEX(dateID),
        INDEX(registerNo),
        INDEX(transNo)
    )
";

if ($dbms == "MSSQL") {
    $CREATE['trans.PaycardTransactions'] = str_replace('NOT NULL AUTO_INCREMENT', 
                                                 'IDENTITY (1, 1) NOT NULL', 
                                                 $CREATE['trans.PaycardTransactions']);
}

