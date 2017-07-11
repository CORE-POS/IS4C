<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op
    Copyright 2014 West End Food Co-op, Toronto

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

/**
  @class CCredHistoryTodaySumModel
*/
class CCredLiveBalanceModel extends ViewModel 
{

    // Actual name of view being created.
    protected $name = "CCredLiveBalance";
    protected $preferred_db = 'plugin:CoopCredDatabase';

    protected $columns = array(
    'programID' => array('type'=>'INT'),
    'cardNo' => array('type'=>'INT'),
    'totcharges' => array('type'=>'MONEY'),
    'totpayments' => array('type'=>'MONEY'),
    'balance' => array('type'=>'MONEY'),
    'mark' => array('type'=>'INT')
    );

    /*
Columns:
    [programID int]
    cardNo int
    totcharges (calculated)
    totpayments (calculated)
    balance (calculated)
    mark (calculated)

Depends on:
    CCredMembershipships (table)
    CCredHistorySum (table)
    CCredHistoryTodaySum (view)
Use:
This view lists real-time Coop Cred
balances by membership.
The column "mark" indicates the balance
changed today
    */

    public function name()
    {
        return $this->name;
    }

    public function definition()
    {
        //global $FANNIE_TRANS_DB;

        return "
SELECT
m.programID
    AS programID,
m.CardNo
    AS cardNo,
(CASE WHEN a.charges IS NULL THEN 0 ELSE a.charges END)
+ (CASE WHEN t.charges IS NULL THEN 0 ELSE t.charges END)
    AS totcharges,
(CASE WHEN a.payments IS NULL THEN 0 ELSE a.payments END)
+ (CASE WHEN t.payments IS NULL THEN 0 ELSE t.payments END)
    AS totpayments,
(CASE WHEN a.balance IS NULL THEN 0 ELSE a.balance END)
+ (CASE WHEN t.balance IS NULL THEN 0 ELSE t.balance END)
    AS balance,
(CASE WHEN t.cardNo IS NULL THEN 0 ELSE 1 END)
    AS mark
    FROM CCredMemberships AS m
    LEFT JOIN CCredHistorySum AS a
        ON m.cardNo=a.cardNo and m.programID = a.programID
    LEFT JOIN CCredHistoryTodaySum AS t
        ON m.cardNo = t.cardNo and m.programID = t.programID
ORDER BY m.programID, m.cardNo
            ";

    }
}

