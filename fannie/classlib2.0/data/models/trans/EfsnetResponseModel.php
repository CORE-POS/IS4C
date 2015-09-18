<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
  @class EfsnetResponseModel
*/
class EfsnetResponseModel extends BasicModel
{

    protected $name = "efsnetResponse";

    protected $preferred_db = 'trans';

    protected $columns = array(
    'date' => array('type'=>'INT'),
    'cashierNo' => array('type'=>'INT'),
    'laneNo' => array('type'=>'INT'),
    'transNo' => array('type'=>'INT'),
    'transID' => array('type'=>'INT'),
    'datetime' => array('type'=>'DATETIME'),
    'refNum' => array('type'=>'VARCHAR(50)'),
    'seconds' => array('type'=>'FLOAT'),
    'commErr' => array('type'=>'INT'),
    'httpCode' => array('type'=>'INT'),
    'validResponse' => array('type'=>'SMALLINT'),
    'xResponseCode' => array('type'=>'VARCHAR(4)'),
    'xResultCode' => array('type'=>'VARCHAR(8)'),
    'xResultMessage' => array('type'=>'VARCHAR(100)'),
    'xTransactionID' => array('type'=>'VARCHAR(12)'),
    'xApprovalNumber' => array('type'=>'VARCHAR(20)'),
    'efsnetRequestID' => array('type'=>'INT', 'index'=>true),
    );

    public function doc()
    {
        return '
Depends on:
* efsnetRequest (table)

Use:
This table logs information that is
returned from a credit-card payment gateway
after sending a [non-void] request.
All current paycard modules use this table
structure. Future ones don\'t necessarily have
to, but doing so may enable more code re-use.

Some column usage may vary depending on a
given gateway\'s requirements and/or formatting,
but in general:

cashierNo, laneNo, transNo, and transID are
equivalent to emp_no, register_no, trans_no, and
trans_id in dtransactions (respectively).

seconds, commErr, and httpCode are curl-related
entries noting how long the network request took
and errors that occurred, if any.

the x* columns vary a lot. What to store here 
depends what the gateway returns.
        ';
    }
}

