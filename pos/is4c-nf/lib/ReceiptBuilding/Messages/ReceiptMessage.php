<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of IT CORE.

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
  @class ReceiptMessage
*/
class ReceiptMessage 
{

	/**
	  @return [string] SQL select statement

	  This statement will be slotted into a query
	  like this:
	  
	  SELECT
	  <ReceiptMessage1->select_condition()> as ReceiptMessage1,
	  <ReceiptMessage2->select_condition()> as ReceiptMessage2,
	  <ReceiptMessage3->select_condition()> as ReceiptMessage3
	  FROM localtranstoday 

	  This query should return one row, so your select statement
	  should use an aggregate (SUM, MAX, MIN, etc). If the message
	  depends on certain conditions - sales in a specific department,
	  a particular type of tender, etc - this should determine
	  whether the message is needed. Having every message run
	  its own separate queries checking for data can negatively
	  impact performance.
	*/
	public function select_condition()
    {
		return '1';
	}

	/**
	  Generate the message
	  @param $val the value returned by the object's select_condition()
	  @param $ref a transaction reference (emp-lane-trans)
	  @param $reprint boolean
	  @return [string] message to print on receipt
	*/
	public function message($val, $ref, $reprint=false)
    {
		return '';
	}

	/**
	  This message has to be printed on paper
	*/
	public $paper_only = false;

	/**
	  Message can be printed independently from a regular	
	  receipt. Pass this string to ajax-end.php as URL
	  parameter receiptType to print the standalone receipt.
	*/
	public $standalone_receipt_type = '';

	/**
	  Print message as its own receipt
	  @param $ref a transaction reference (emp-lane-trans)
	  @param $reprint boolean
	  @return [string] message to print 
	*/
	public function standalone_receipt($ref, $reprint=false)
    {
		return '';
	}
}

