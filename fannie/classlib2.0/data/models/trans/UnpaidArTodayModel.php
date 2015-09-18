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
  @class UnpaidArTodayModel
*/
class UnpaidArTodayModel extends ViewModel
{

    protected $name = "unpaid_ar_today";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'card_no' => array('type'=>'INT'),
    'old_balance' => array('type'=>'MONEY'),
    'recent_payments' => array('type'=>'MONEY'),
    'mark' => array('type'=>'INT'),
    );

    public function definition()
    {
        return '
            SELECT u.card_no,
                u.old_balance,
                CASE 
                    WHEN m.card_no IS NULL THEN u.recent_payments 
                    ELSE m.payments+u.recent_payments
                END AS recent_payments,
                CASE 
                    WHEN m.card_no IS NULL THEN 0 
                    ELSE 1 
                END AS mark
            FROM unpaid_ar_balances AS u
                LEFT JOIN ar_history_today_sum AS m ON u.card_no=m.card_no';
    }

    public function doc()
    {
        return '
Depends on:
* ar_history (table)
* unpaid_ar_balances (view of t.ar_history)
* ar_history_today_sum (view of t.dtransactions via v.dlog)

Depended on by:
* cron/LanePush/UpdateUnpaidAR.php
   to update each lane opdata.unpaid_ar_today.recent_payments

Use:
This view adds payments from the current
day to the view unpaid_ar_balances

The logic is pretty WFC-specific, but the 
general idea is to notify customers that they
have a balance overdue at checkout
        ';
    }
}

