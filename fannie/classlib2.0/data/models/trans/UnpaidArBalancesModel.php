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
  @class UnpaidArBalancesModel
*/
class UnpaidArBalancesModel extends ViewModel
{

    protected $name = "unpaid_ar_balances";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'card_no' => array('type'=>'INT'),
    'old_balance' => array('type'=>'MONEY'),
    'recent_payments' => array('type'=>'MONEY'),
    );

    public function definition()
    {
        return '
            SELECT
                card_no,
                SUM(CASE WHEN '.$this->connection->datediff('tdate',$this->connection->now()).' < -20 
                    AND card_no NOT BETWEEN 5000 AND 6099
                    THEN (charges - payments)
                    ELSE 0 END) AS old_balance,
                SUM(CASE WHEN '.$this->connection->datediff('tdate',$this->connection->now()).' >= -20
                    THEN payments ELSE 0 END)   AS recent_payments
            FROM ar_history
            WHERE card_no <> 11
            GROUP by card_no';
    }

    public function doc()
    {
        return '
Depends on:
* ar_history (table)

Depended on by:
* unpaid_ar_today (view)

Use:
This view lists A/R balances older than
20 days and payments made in the last 20 days.

The logic is pretty WFC-specific, but the 
general idea is to notify customers that they
have a balance overdue at checkout
        ';
    }
}

