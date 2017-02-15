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
  @class MemChargeBalanceModel
*/
class MemChargeBalanceModel extends SpanningViewModel
{

    protected $name = "memChargeBalance";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'CardNo' => array('type'=>'INT'),
    'availBal' => array('type'=>'MONEY'),
    'balance' => array('type'=>'MONEY'),
    'mark' => array('type'=>'INT'),
    );

    public function definition()
    {
        $custdata = $this->findExtraTable('custdata');
        if ($custdata === false) {
            return parent::definition();
        }

        return '
        SELECT c.CardNo, 
            CASE 
                WHEN a.balance IS NULL THEN c.ChargeLimit
                ELSE c.ChargeLimit - a.balance END
            AS availBal,
            CASE WHEN a.balance is NULL THEN 0 ELSE a.balance END AS balance,
            CASE WHEN a.mark IS NULL THEN 0 ELSE a.mark END AS mark   
        FROM ' . $custdata  . ' AS c 
            LEFT JOIN ar_live_balance AS a ON c.CardNo = a.card_no
        WHERE c.personNum = 1';
    }
    
    public function doc()
    {
        return '
Depends on:
* core_op.custdata (table)
* ar_live_balance (view of t.dtransactions -> .v.dlog)

Use:
This view lists real-time store charge
 balances by membership.
This view gets pushed to the lanes as a table
 to speed things up
The "mark" column indicates an account
        ';
    }
}

