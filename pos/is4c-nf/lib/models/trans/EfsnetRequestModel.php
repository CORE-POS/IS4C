<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

namespace COREPOS\pos\lib\models\trans;
use COREPOS\pos\lib\models\BasicModel;

/**
  @class EfsnetRequestModel
*/
class EfsnetRequestModel extends BasicModel
{

    protected $name = "efsnetRequest";

    protected $preferred_db = 'trans';

    protected $columns = array(
    'date' => array('type'=>'INT'),
    'cashierNo' => array('type'=>'INT'),
    'laneNo' => array('type'=>'INT'),
    'transNo' => array('type'=>'INT'),
    'transID' => array('type'=>'INT'),
    'datetime' => array('type'=>'DATETIME'),
    'refNum' => array('type'=>'VARCHAR(50)'),
    'live' => array('type'=>'TINYINT'),
    'mode' => array('type'=>'VARCHAR(32)'),
    'amount' => array('type'=>'MONEY'),
    'PAN' => array('type'=>'VARCHAR(19)'),
    'issuer' => array('type'=>'VARCHAR(16)'),
    'name' => array('type'=>'VARCHAR(50)'),
    'manual' => array('type'=>'TINYINT'),
    'sentPAN' => array('type'=>'TINYINT'),
    'sentExp' => array('type'=>'TINYINT'),
    'sentTr1' => array('type'=>'TINYINT'),
    'sentTr2' => array('type'=>'TINYINT'),
    'efsnetRequestID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    );

    public function doc()
    {
        return '
Use:
This table logs information that is
sent to a credit-card payment gateway.
All current paycard modules use this table
structure. Future ones don\'t necessarily have
to, but doing so may enable more code re-use.

Some column usage may vary depending on a
given gateway\'s requirements and/or formatting,
but in general:

cashierNo, laneNo, transNo, and transID are
equivalent to emp_no, register_no, trans_no, and
trans_id in dtransactions (respectively).

mode indicates the type of transaction, such as
refund or sale. Exact value can vary from gateway
to gateway.

PAN is the cardnumber - for the love of $deity
only save the last 4 digits here - issuer is
Visa, MC, etc, and name is the cardholder\'s name
(if available).

The sent* columns indicate which information was
sent. Most gateways will accept PAN + expiration
date, or either track. Sending both tracks is
usually fine; I\'ve never seen a system where
you send all 4 pieces of card info.

efsnetRequestID is an incrementing ID columns. This
is unique at a lane level but not an overall system
level since different lanes will increment through
the same ID values. The combination of laneNo and
efsnetRequestID should be unique though.
        ';
    }
}

