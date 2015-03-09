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
            } else if (isset($this->columns["paycardTransactionID"]["default"])) {
                return $this->columns["paycardTransactionID"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'paycardTransactionID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["paycardTransactionID"]) || $this->instance["paycardTransactionID"] != func_get_args(0)) {
                if (!isset($this->columns["paycardTransactionID"]["ignore_updates"]) || $this->columns["paycardTransactionID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["paycardTransactionID"] = func_get_arg(0);
        }
        return $this;
    }

    public function dateID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dateID"])) {
                return $this->instance["dateID"];
            } else if (isset($this->columns["dateID"]["default"])) {
                return $this->columns["dateID"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'dateID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dateID"]) || $this->instance["dateID"] != func_get_args(0)) {
                if (!isset($this->columns["dateID"]["ignore_updates"]) || $this->columns["dateID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dateID"] = func_get_arg(0);
        }
        return $this;
    }

    public function empNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["empNo"])) {
                return $this->instance["empNo"];
            } else if (isset($this->columns["empNo"]["default"])) {
                return $this->columns["empNo"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'empNo',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["empNo"]) || $this->instance["empNo"] != func_get_args(0)) {
                if (!isset($this->columns["empNo"]["ignore_updates"]) || $this->columns["empNo"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["empNo"] = func_get_arg(0);
        }
        return $this;
    }

    public function registerNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["registerNo"])) {
                return $this->instance["registerNo"];
            } else if (isset($this->columns["registerNo"]["default"])) {
                return $this->columns["registerNo"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'registerNo',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["registerNo"]) || $this->instance["registerNo"] != func_get_args(0)) {
                if (!isset($this->columns["registerNo"]["ignore_updates"]) || $this->columns["registerNo"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["registerNo"] = func_get_arg(0);
        }
        return $this;
    }

    public function transNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["transNo"])) {
                return $this->instance["transNo"];
            } else if (isset($this->columns["transNo"]["default"])) {
                return $this->columns["transNo"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'transNo',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["transNo"]) || $this->instance["transNo"] != func_get_args(0)) {
                if (!isset($this->columns["transNo"]["ignore_updates"]) || $this->columns["transNo"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["transNo"] = func_get_arg(0);
        }
        return $this;
    }

    public function transID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["transID"])) {
                return $this->instance["transID"];
            } else if (isset($this->columns["transID"]["default"])) {
                return $this->columns["transID"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'transID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["transID"]) || $this->instance["transID"] != func_get_args(0)) {
                if (!isset($this->columns["transID"]["ignore_updates"]) || $this->columns["transID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["transID"] = func_get_arg(0);
        }
        return $this;
    }

    public function previousPaycardTransactionID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["previousPaycardTransactionID"])) {
                return $this->instance["previousPaycardTransactionID"];
            } else if (isset($this->columns["previousPaycardTransactionID"]["default"])) {
                return $this->columns["previousPaycardTransactionID"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'previousPaycardTransactionID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["previousPaycardTransactionID"]) || $this->instance["previousPaycardTransactionID"] != func_get_args(0)) {
                if (!isset($this->columns["previousPaycardTransactionID"]["ignore_updates"]) || $this->columns["previousPaycardTransactionID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["previousPaycardTransactionID"] = func_get_arg(0);
        }
        return $this;
    }

    public function processor()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["processor"])) {
                return $this->instance["processor"];
            } else if (isset($this->columns["processor"]["default"])) {
                return $this->columns["processor"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'processor',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["processor"]) || $this->instance["processor"] != func_get_args(0)) {
                if (!isset($this->columns["processor"]["ignore_updates"]) || $this->columns["processor"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["processor"] = func_get_arg(0);
        }
        return $this;
    }

    public function refNum()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["refNum"])) {
                return $this->instance["refNum"];
            } else if (isset($this->columns["refNum"]["default"])) {
                return $this->columns["refNum"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'refNum',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["refNum"]) || $this->instance["refNum"] != func_get_args(0)) {
                if (!isset($this->columns["refNum"]["ignore_updates"]) || $this->columns["refNum"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["refNum"] = func_get_arg(0);
        }
        return $this;
    }

    public function live()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["live"])) {
                return $this->instance["live"];
            } else if (isset($this->columns["live"]["default"])) {
                return $this->columns["live"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'live',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["live"]) || $this->instance["live"] != func_get_args(0)) {
                if (!isset($this->columns["live"]["ignore_updates"]) || $this->columns["live"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["live"] = func_get_arg(0);
        }
        return $this;
    }

    public function cardType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["cardType"])) {
                return $this->instance["cardType"];
            } else if (isset($this->columns["cardType"]["default"])) {
                return $this->columns["cardType"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'cardType',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["cardType"]) || $this->instance["cardType"] != func_get_args(0)) {
                if (!isset($this->columns["cardType"]["ignore_updates"]) || $this->columns["cardType"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["cardType"] = func_get_arg(0);
        }
        return $this;
    }

    public function transType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["transType"])) {
                return $this->instance["transType"];
            } else if (isset($this->columns["transType"]["default"])) {
                return $this->columns["transType"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'transType',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["transType"]) || $this->instance["transType"] != func_get_args(0)) {
                if (!isset($this->columns["transType"]["ignore_updates"]) || $this->columns["transType"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["transType"] = func_get_arg(0);
        }
        return $this;
    }

    public function amount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["amount"])) {
                return $this->instance["amount"];
            } else if (isset($this->columns["amount"]["default"])) {
                return $this->columns["amount"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'amount',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["amount"]) || $this->instance["amount"] != func_get_args(0)) {
                if (!isset($this->columns["amount"]["ignore_updates"]) || $this->columns["amount"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["amount"] = func_get_arg(0);
        }
        return $this;
    }

    public function PAN()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["PAN"])) {
                return $this->instance["PAN"];
            } else if (isset($this->columns["PAN"]["default"])) {
                return $this->columns["PAN"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'PAN',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["PAN"]) || $this->instance["PAN"] != func_get_args(0)) {
                if (!isset($this->columns["PAN"]["ignore_updates"]) || $this->columns["PAN"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["PAN"] = func_get_arg(0);
        }
        return $this;
    }

    public function issuer()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["issuer"])) {
                return $this->instance["issuer"];
            } else if (isset($this->columns["issuer"]["default"])) {
                return $this->columns["issuer"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'issuer',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["issuer"]) || $this->instance["issuer"] != func_get_args(0)) {
                if (!isset($this->columns["issuer"]["ignore_updates"]) || $this->columns["issuer"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["issuer"] = func_get_arg(0);
        }
        return $this;
    }

    public function name()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["name"])) {
                return $this->instance["name"];
            } else if (isset($this->columns["name"]["default"])) {
                return $this->columns["name"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'name',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["name"]) || $this->instance["name"] != func_get_args(0)) {
                if (!isset($this->columns["name"]["ignore_updates"]) || $this->columns["name"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["name"] = func_get_arg(0);
        }
        return $this;
    }

    public function manual()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["manual"])) {
                return $this->instance["manual"];
            } else if (isset($this->columns["manual"]["default"])) {
                return $this->columns["manual"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'manual',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["manual"]) || $this->instance["manual"] != func_get_args(0)) {
                if (!isset($this->columns["manual"]["ignore_updates"]) || $this->columns["manual"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["manual"] = func_get_arg(0);
        }
        return $this;
    }

    public function requestDatetime()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["requestDatetime"])) {
                return $this->instance["requestDatetime"];
            } else if (isset($this->columns["requestDatetime"]["default"])) {
                return $this->columns["requestDatetime"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'requestDatetime',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["requestDatetime"]) || $this->instance["requestDatetime"] != func_get_args(0)) {
                if (!isset($this->columns["requestDatetime"]["ignore_updates"]) || $this->columns["requestDatetime"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["requestDatetime"] = func_get_arg(0);
        }
        return $this;
    }

    public function responseDatetime()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["responseDatetime"])) {
                return $this->instance["responseDatetime"];
            } else if (isset($this->columns["responseDatetime"]["default"])) {
                return $this->columns["responseDatetime"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'responseDatetime',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["responseDatetime"]) || $this->instance["responseDatetime"] != func_get_args(0)) {
                if (!isset($this->columns["responseDatetime"]["ignore_updates"]) || $this->columns["responseDatetime"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["responseDatetime"] = func_get_arg(0);
        }
        return $this;
    }

    public function seconds()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["seconds"])) {
                return $this->instance["seconds"];
            } else if (isset($this->columns["seconds"]["default"])) {
                return $this->columns["seconds"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'seconds',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["seconds"]) || $this->instance["seconds"] != func_get_args(0)) {
                if (!isset($this->columns["seconds"]["ignore_updates"]) || $this->columns["seconds"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["seconds"] = func_get_arg(0);
        }
        return $this;
    }

    public function commErr()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["commErr"])) {
                return $this->instance["commErr"];
            } else if (isset($this->columns["commErr"]["default"])) {
                return $this->columns["commErr"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'commErr',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["commErr"]) || $this->instance["commErr"] != func_get_args(0)) {
                if (!isset($this->columns["commErr"]["ignore_updates"]) || $this->columns["commErr"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["commErr"] = func_get_arg(0);
        }
        return $this;
    }

    public function httpCode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["httpCode"])) {
                return $this->instance["httpCode"];
            } else if (isset($this->columns["httpCode"]["default"])) {
                return $this->columns["httpCode"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'httpCode',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["httpCode"]) || $this->instance["httpCode"] != func_get_args(0)) {
                if (!isset($this->columns["httpCode"]["ignore_updates"]) || $this->columns["httpCode"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["httpCode"] = func_get_arg(0);
        }
        return $this;
    }

    public function validResponse()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["validResponse"])) {
                return $this->instance["validResponse"];
            } else if (isset($this->columns["validResponse"]["default"])) {
                return $this->columns["validResponse"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'validResponse',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["validResponse"]) || $this->instance["validResponse"] != func_get_args(0)) {
                if (!isset($this->columns["validResponse"]["ignore_updates"]) || $this->columns["validResponse"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["validResponse"] = func_get_arg(0);
        }
        return $this;
    }

    public function xResultCode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xResultCode"])) {
                return $this->instance["xResultCode"];
            } else if (isset($this->columns["xResultCode"]["default"])) {
                return $this->columns["xResultCode"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'xResultCode',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["xResultCode"]) || $this->instance["xResultCode"] != func_get_args(0)) {
                if (!isset($this->columns["xResultCode"]["ignore_updates"]) || $this->columns["xResultCode"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["xResultCode"] = func_get_arg(0);
        }
        return $this;
    }

    public function xApprovalNumber()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xApprovalNumber"])) {
                return $this->instance["xApprovalNumber"];
            } else if (isset($this->columns["xApprovalNumber"]["default"])) {
                return $this->columns["xApprovalNumber"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'xApprovalNumber',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["xApprovalNumber"]) || $this->instance["xApprovalNumber"] != func_get_args(0)) {
                if (!isset($this->columns["xApprovalNumber"]["ignore_updates"]) || $this->columns["xApprovalNumber"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["xApprovalNumber"] = func_get_arg(0);
        }
        return $this;
    }

    public function xResponseCode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xResponseCode"])) {
                return $this->instance["xResponseCode"];
            } else if (isset($this->columns["xResponseCode"]["default"])) {
                return $this->columns["xResponseCode"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'xResponseCode',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["xResponseCode"]) || $this->instance["xResponseCode"] != func_get_args(0)) {
                if (!isset($this->columns["xResponseCode"]["ignore_updates"]) || $this->columns["xResponseCode"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["xResponseCode"] = func_get_arg(0);
        }
        return $this;
    }

    public function xResultMessage()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xResultMessage"])) {
                return $this->instance["xResultMessage"];
            } else if (isset($this->columns["xResultMessage"]["default"])) {
                return $this->columns["xResultMessage"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'xResultMessage',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["xResultMessage"]) || $this->instance["xResultMessage"] != func_get_args(0)) {
                if (!isset($this->columns["xResultMessage"]["ignore_updates"]) || $this->columns["xResultMessage"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["xResultMessage"] = func_get_arg(0);
        }
        return $this;
    }

    public function xTransactionID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xTransactionID"])) {
                return $this->instance["xTransactionID"];
            } else if (isset($this->columns["xTransactionID"]["default"])) {
                return $this->columns["xTransactionID"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'xTransactionID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["xTransactionID"]) || $this->instance["xTransactionID"] != func_get_args(0)) {
                if (!isset($this->columns["xTransactionID"]["ignore_updates"]) || $this->columns["xTransactionID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["xTransactionID"] = func_get_arg(0);
        }
        return $this;
    }

    public function xBalance()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xBalance"])) {
                return $this->instance["xBalance"];
            } else if (isset($this->columns["xBalance"]["default"])) {
                return $this->columns["xBalance"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'xBalance',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["xBalance"]) || $this->instance["xBalance"] != func_get_args(0)) {
                if (!isset($this->columns["xBalance"]["ignore_updates"]) || $this->columns["xBalance"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["xBalance"] = func_get_arg(0);
        }
        return $this;
    }

    public function xToken()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xToken"])) {
                return $this->instance["xToken"];
            } else if (isset($this->columns["xToken"]["default"])) {
                return $this->columns["xToken"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'xToken',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["xToken"]) || $this->instance["xToken"] != func_get_args(0)) {
                if (!isset($this->columns["xToken"]["ignore_updates"]) || $this->columns["xToken"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["xToken"] = func_get_arg(0);
        }
        return $this;
    }

    public function xProcessorRef()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xProcessorRef"])) {
                return $this->instance["xProcessorRef"];
            } else if (isset($this->columns["xProcessorRef"]["default"])) {
                return $this->columns["xProcessorRef"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'xProcessorRef',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["xProcessorRef"]) || $this->instance["xProcessorRef"] != func_get_args(0)) {
                if (!isset($this->columns["xProcessorRef"]["ignore_updates"]) || $this->columns["xProcessorRef"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["xProcessorRef"] = func_get_arg(0);
        }
        return $this;
    }

    public function xAcquirerRef()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xAcquirerRef"])) {
                return $this->instance["xAcquirerRef"];
            } else if (isset($this->columns["xAcquirerRef"]["default"])) {
                return $this->columns["xAcquirerRef"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'xAcquirerRef',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["xAcquirerRef"]) || $this->instance["xAcquirerRef"] != func_get_args(0)) {
                if (!isset($this->columns["xAcquirerRef"]["ignore_updates"]) || $this->columns["xAcquirerRef"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["xAcquirerRef"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

