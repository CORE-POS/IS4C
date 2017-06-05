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
  @class GumLoanAccountsModel

  This table stores member loans/bonds. The
  fields are pretty straightforward. Note that
  a given member may have multiple loan accounts
  so card_no is not necessarily unique; gumLoanAccountID
  and accountNumber are both unique.

  When loans are paid back, an entry for that check
  is created in GumPayoffs. That table can be joined 
  to this table via GumLoanPayoffMap.
*/
class GumLoanAccountsModel extends BasicModel
{

    protected $name = "GumLoanAccounts";
    protected $preferred_db = 'plugin:GiveUsMoneyDB';

    protected $columns = array(
    'gumLoanAccountID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'card_no' => array('type'=>'INT', 'index'=>true),
    'accountNumber' => array('type'=>'VARCHAR(25)', 'index'=>true),
    'loanDate' => array('type'=>'datetime'),
    'principal' => array('type'=>'MONEY'),
    'termInMonths' => array('type'=>'INT'),
    'interestRate' => array('type'=>'DOUBLE'),
    );

    protected $unique = array('accountNumber');
}

