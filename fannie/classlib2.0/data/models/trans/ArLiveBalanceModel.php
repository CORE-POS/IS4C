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
  @class ArLiveBalanceModel
*/
class ArLiveBalanceModel extends SpanningViewModel 
{

    protected $name = "ar_live_balance";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'card_no' => array('type'=>'INT','primary_key'=>True),
    'totcharges' => array('type'=>'MONEY'),
    'totpayments' => array('type'=>'MONEY'),
    'balance' => array('type'=>'MONEY'),
    'mark' => array('type'=>'TINYINT')
    );

    public function definition()
    {
        $custdata = $this->findExtraTable('custdata');
        if ($custdata === false) {
            return parent::definition();
        }

        return '
            SELECT   
                c.CardNo AS card_no,
                (CASE WHEN a.charges IS NULL THEN 0 ELSE a.charges END)
                    + (CASE WHEN t.charges IS NULL THEN 0 ELSE t.charges END)
                    AS totcharges,
                (CASE WHEN a.payments IS NULL THEN 0 ELSE a.payments END)
                    + (CASE WHEN t.payments IS NULL THEN 0 ELSE t.payments END)
                    AS totpayments,
                (CASE WHEN a.balance IS NULL THEN 0 ELSE a.balance END)
                    + (CASE WHEN t.balance IS NULL THEN 0 ELSE t.balance END)
                    AS balance,
                (CASE WHEN t.card_no IS NULL THEN 0 ELSE 1 END) AS mark
            FROM ' . $custdata . ' AS c
                LEFT JOIN ar_history_sum AS a ON c.CardNo=a.card_no AND c.personNum=1
                LEFT JOIN ar_history_today_sum AS t ON c.CardNo = t.card_no AND c.personNum=1
            WHERE c.personNum=1
        ';
    }

    public function doc()
    {
        return '
Depends on:
* core_op.custdata (table)
* ar_history_sum (table)
* ar_history_today_sum (view)

Use:
This view lists real-time store charge
balances by membership. The column "mark"
indicates the balance changed today
        ';
    }
}

