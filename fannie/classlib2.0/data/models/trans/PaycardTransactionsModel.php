<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of CORE-POS.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/**
  @class PaycardTransactionsModel
*/
class PaycardTransactionsModel extends BasicModel
{

    protected $name = "PaycardTransactions";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'paycardTransactionID' => array('type'=>'INT', 'index'=>true),
    'dateID' => array('type'=>'INT', 'index'=>true),
    'empNo' => array('type'=>'INT'),
    'registerNo' => array('type'=>'INT', 'index'=>true),
    'transNo' => array('type'=>'INT', 'index'=>true),
    'transID' => array('type'=>'INT'),
    'previousPaycardTransactionID' => array('type'=>'INT'),
    'processor' => array('type'=>'VARCHAR(25)'),
    'refNum' => array('type'=>'VARCHAR(50)'),
    'live' => array('type'=>'TINYINT'),
    'cardType' => array('type'=>'VARCHAR(15)'),
    'transType' => array('type'=>'VARCHAR(15)'),
    'amount' => array('type'=>'MONEY'),
    'PAN' => array('type'=>'VARCHAR(19)'),
    'issuer' => array('type'=>'VARCHAR(20)'),
    'name' => array('type'=>'VARCHAR(50)'),
    'manual' => array('type'=>'TINYINT'),
    'requestDatetime' => array('type'=>'DATETIME'),
    'responseDatetime' => array('type'=>'DATETIME'),
    'seconds' => array('type'=>'FLOAT'),
    'commErr' => array('type'=>'SMALLINT'),
    'httpCode' => array('type'=>'SMALLINT'),
    'validResponse' => array('type'=>'SMALLINT'),
    'xResultCode' => array('type'=>'VARCHAR(8)'),
    'xApprovalNumber' => array('type'=>'VARCHAR(20)'),
    'xResponseCode' => array('type'=>'VARCHAR(8)'),
    'xResultMessage' => array('type'=>'VARCHAR(100)'),
    'xTransactionID' => array('type'=>'VARCHAR(12)'),
    'xBalance' => array('type'=>'VARCHAR(8)'),
    'xToken' => array('type'=>'VARCHAR(64)'),
    'xProcessorRef' => array('type'=>'VARCHAR(24)'),
    'xAcquirerRef' => array('type'=>'VARCHAR(100)'),
    'storeRowId' => array('type'=>'INT', 'increment'=>true, 'index'=>true),
    );

    public function doc()
    {
        return '
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
  * processor is the name of the PHP class that\'s
    making the request
  * refNum is a reference number sent to the processor.
    This is usually just a memo field that will come
    back unchanged on the response.
  * live indicates whether it\'s a live or testing
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
  * name is the cardholder\'s name. This is not always 
    available and depends on what\'s on the magnetic stripe.
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
        ';
    }
}

