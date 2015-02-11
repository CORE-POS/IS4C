<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of Fannie.

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
    'paycardTransactionID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
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
    );

    public function doc()
    {
        return '
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

    /* START ACCESSOR FUNCTIONS */

    public function paycardTransactionID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["paycardTransactionID"])) {
                return $this->instance["paycardTransactionID"];
            } elseif(isset($this->columns["paycardTransactionID"]["default"])) {
                return $this->columns["paycardTransactionID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["paycardTransactionID"] = func_get_arg(0);
        }
    }

    public function dateID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dateID"])) {
                return $this->instance["dateID"];
            } elseif(isset($this->columns["dateID"]["default"])) {
                return $this->columns["dateID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["dateID"] = func_get_arg(0);
        }
    }

    public function empNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["empNo"])) {
                return $this->instance["empNo"];
            } elseif(isset($this->columns["empNo"]["default"])) {
                return $this->columns["empNo"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["empNo"] = func_get_arg(0);
        }
    }

    public function registerNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["registerNo"])) {
                return $this->instance["registerNo"];
            } elseif(isset($this->columns["registerNo"]["default"])) {
                return $this->columns["registerNo"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["registerNo"] = func_get_arg(0);
        }
    }

    public function transNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["transNo"])) {
                return $this->instance["transNo"];
            } elseif(isset($this->columns["transNo"]["default"])) {
                return $this->columns["transNo"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["transNo"] = func_get_arg(0);
        }
    }

    public function transID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["transID"])) {
                return $this->instance["transID"];
            } elseif(isset($this->columns["transID"]["default"])) {
                return $this->columns["transID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["transID"] = func_get_arg(0);
        }
    }

    public function previousPaycardTransactionID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["previousPaycardTransactionID"])) {
                return $this->instance["previousPaycardTransactionID"];
            } elseif(isset($this->columns["previousPaycardTransactionID"]["default"])) {
                return $this->columns["previousPaycardTransactionID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["previousPaycardTransactionID"] = func_get_arg(0);
        }
    }

    public function processor()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["processor"])) {
                return $this->instance["processor"];
            } elseif(isset($this->columns["processor"]["default"])) {
                return $this->columns["processor"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["processor"] = func_get_arg(0);
        }
    }

    public function refNum()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["refNum"])) {
                return $this->instance["refNum"];
            } elseif(isset($this->columns["refNum"]["default"])) {
                return $this->columns["refNum"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["refNum"] = func_get_arg(0);
        }
    }

    public function live()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["live"])) {
                return $this->instance["live"];
            } elseif(isset($this->columns["live"]["default"])) {
                return $this->columns["live"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["live"] = func_get_arg(0);
        }
    }

    public function cardType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["cardType"])) {
                return $this->instance["cardType"];
            } elseif(isset($this->columns["cardType"]["default"])) {
                return $this->columns["cardType"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["cardType"] = func_get_arg(0);
        }
    }

    public function transType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["transType"])) {
                return $this->instance["transType"];
            } elseif(isset($this->columns["transType"]["default"])) {
                return $this->columns["transType"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["transType"] = func_get_arg(0);
        }
    }

    public function amount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["amount"])) {
                return $this->instance["amount"];
            } elseif(isset($this->columns["amount"]["default"])) {
                return $this->columns["amount"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["amount"] = func_get_arg(0);
        }
    }

    public function PAN()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["PAN"])) {
                return $this->instance["PAN"];
            } elseif(isset($this->columns["PAN"]["default"])) {
                return $this->columns["PAN"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["PAN"] = func_get_arg(0);
        }
    }

    public function issuer()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["issuer"])) {
                return $this->instance["issuer"];
            } elseif(isset($this->columns["issuer"]["default"])) {
                return $this->columns["issuer"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["issuer"] = func_get_arg(0);
        }
    }

    public function name()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["name"])) {
                return $this->instance["name"];
            } elseif(isset($this->columns["name"]["default"])) {
                return $this->columns["name"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["name"] = func_get_arg(0);
        }
    }

    public function manual()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["manual"])) {
                return $this->instance["manual"];
            } elseif(isset($this->columns["manual"]["default"])) {
                return $this->columns["manual"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["manual"] = func_get_arg(0);
        }
    }

    public function requestDatetime()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["requestDatetime"])) {
                return $this->instance["requestDatetime"];
            } elseif(isset($this->columns["requestDatetime"]["default"])) {
                return $this->columns["requestDatetime"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["requestDatetime"] = func_get_arg(0);
        }
    }

    public function responseDatetime()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["responseDatetime"])) {
                return $this->instance["responseDatetime"];
            } elseif(isset($this->columns["responseDatetime"]["default"])) {
                return $this->columns["responseDatetime"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["responseDatetime"] = func_get_arg(0);
        }
    }

    public function seconds()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["seconds"])) {
                return $this->instance["seconds"];
            } elseif(isset($this->columns["seconds"]["default"])) {
                return $this->columns["seconds"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["seconds"] = func_get_arg(0);
        }
    }

    public function commErr()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["commErr"])) {
                return $this->instance["commErr"];
            } elseif(isset($this->columns["commErr"]["default"])) {
                return $this->columns["commErr"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["commErr"] = func_get_arg(0);
        }
    }

    public function httpCode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["httpCode"])) {
                return $this->instance["httpCode"];
            } elseif(isset($this->columns["httpCode"]["default"])) {
                return $this->columns["httpCode"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["httpCode"] = func_get_arg(0);
        }
    }

    public function validResponse()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["validResponse"])) {
                return $this->instance["validResponse"];
            } elseif(isset($this->columns["validResponse"]["default"])) {
                return $this->columns["validResponse"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["validResponse"] = func_get_arg(0);
        }
    }

    public function xResultCode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xResultCode"])) {
                return $this->instance["xResultCode"];
            } elseif(isset($this->columns["xResultCode"]["default"])) {
                return $this->columns["xResultCode"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["xResultCode"] = func_get_arg(0);
        }
    }

    public function xApprovalNumber()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xApprovalNumber"])) {
                return $this->instance["xApprovalNumber"];
            } elseif(isset($this->columns["xApprovalNumber"]["default"])) {
                return $this->columns["xApprovalNumber"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["xApprovalNumber"] = func_get_arg(0);
        }
    }

    public function xResponseCode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xResponseCode"])) {
                return $this->instance["xResponseCode"];
            } elseif(isset($this->columns["xResponseCode"]["default"])) {
                return $this->columns["xResponseCode"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["xResponseCode"] = func_get_arg(0);
        }
    }

    public function xResultMessage()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xResultMessage"])) {
                return $this->instance["xResultMessage"];
            } elseif(isset($this->columns["xResultMessage"]["default"])) {
                return $this->columns["xResultMessage"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["xResultMessage"] = func_get_arg(0);
        }
    }

    public function xTransactionID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xTransactionID"])) {
                return $this->instance["xTransactionID"];
            } elseif(isset($this->columns["xTransactionID"]["default"])) {
                return $this->columns["xTransactionID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["xTransactionID"] = func_get_arg(0);
        }
    }

    public function xBalance()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xBalance"])) {
                return $this->instance["xBalance"];
            } elseif(isset($this->columns["xBalance"]["default"])) {
                return $this->columns["xBalance"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["xBalance"] = func_get_arg(0);
        }
    }

    public function xToken()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xToken"])) {
                return $this->instance["xToken"];
            } elseif(isset($this->columns["xToken"]["default"])) {
                return $this->columns["xToken"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["xToken"] = func_get_arg(0);
        }
    }

    public function xProcessorRef()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xProcessorRef"])) {
                return $this->instance["xProcessorRef"];
            } elseif(isset($this->columns["xProcessorRef"]["default"])) {
                return $this->columns["xProcessorRef"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["xProcessorRef"] = func_get_arg(0);
        }
    }

    public function xAcquirerRef()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xAcquirerRef"])) {
                return $this->instance["xAcquirerRef"];
            } elseif(isset($this->columns["xAcquirerRef"]["default"])) {
                return $this->columns["xAcquirerRef"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["xAcquirerRef"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

