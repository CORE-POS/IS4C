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
  @class GumLoanDefaultInterestRatesModel

  This table defines default interest rates for
  loans with a principal amount between the upper
  and lower bounds.
*/
class GumLoanDefaultInterestRatesModel extends BasicModel
{

    protected $name = "GumLoanDefaultInterestRates";
    protected $preferred_db = 'plugin:GiveUsMoneyDB';

    protected $columns = array(
    'gumLoanDefaultInterestRateID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'lowerBound' => array('type'=>'MONEY', 'default'=>0),
    'upperBound' => array('type'=>'MONEY', 'default'=>99999999.99),
    'interestRate' => array('type'=>'DOUBLE'),
    );
}

