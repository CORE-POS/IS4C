<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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
  @class EquityPaymentPlansModel
*/
class EquityPaymentPlansModel extends BasicModel
{

    protected $name = "EquityPaymentPlans";
    protected $preferred_db = 'op';

    protected $columns = array(
    'equityPaymentPlanID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'name' => array('type'=>'VARCHAR(100)'),
    'initialPayment' => array('type'=>'MONEY'),
    'recurringPayment' => array('type'=>'MONEY'),
    'finalBalance' => array('type'=>'MONEY'),
    'billingCycle' => array('type'=>'VARCHAR(10)', 'default'=>"'1Y'"),
    'dueDateBasis' => array('type'=>'TINYINT', 'default'=>0),
    'overDueLimit' => array('type'=>'SMALLINT', 'default'=>31),
    'reasonMask' => array('type'=>'INT', 'default'=>1),
    );

    public function doc()
    {
        return '
Use:
Provides a structured way for calculated ownership billing cycles.
* initialPayment is the amount due immediately at sign-up
* recurringPayment is the amount due for each billing cycle. Each
  such payment is expected to be the same.
* finalBalance is the total equity due. Once the owner owns this
  amount of equity billing ceases.
* Billing cycle is a number and letter such as 1Y (payment due every
  one year) or 6M (payment due every 6 months)
* dueDateBasis determines if the next payment due date is calculated
  based on the date the owner originally joined (0) or the date they
  last made an equity payment (1)
* overDueLimit is time, in days, that a payment can be overdue before
  the account should be deactivated
* reasonMask is the reason used when deactivating an account
            ';
    }
}

