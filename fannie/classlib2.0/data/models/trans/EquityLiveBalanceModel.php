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
  @class EquityLiveBalanceModel
*/
class EquityLiveBalanceModel extends SpanningViewModel 
{

    protected $name = "equity_live_balance";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'memnum' => array('type'=>'INT','primary_key'=>True),
    'payments' => array('type'=>'MONEY'),
    'startdate' => array('type'=>'DATETIME')
    );

    public function definition()
    {
        $meminfo = $this->findExtraTable('meminfo');
        if ($meminfo === false) {
            return parent::definition();
        }

        return '
            SELECT
                m.card_no AS memnum,
                CASE
                    WHEN a.card_no IS NOT NULL AND b.card_no IS NOT NULL
                    THEN a.payments + b.totPayments
                    WHEN a.card_no IS NOT NULL
                    THEN a.payments
                    WHEN b.card_no IS NOT NULL
                    THEN b.totPayments
                END AS payments,
                CASE WHEN a.startdate IS NULL THEN b.startdate
                    ELSE a.startdate END AS startdate
            FROM ' . $meminfo . ' AS m 
                LEFT JOIN equity_history_sum AS a ON a.card_no=m.card_no
                LEFT JOIN stockSumToday AS b ON m.card_no=b.card_no
            WHERE a.card_no IS NOT NULL 
                OR b.card_no IS NOT NULL
        ';
    }

    public function doc()
    {
        return '
Depends on:
* core_op.meminfo (table)
* equity_history_sum (table)
* stockSum_today (view)

Use:
This view lists real-time equity
balances by membership
        ';
    }
}

