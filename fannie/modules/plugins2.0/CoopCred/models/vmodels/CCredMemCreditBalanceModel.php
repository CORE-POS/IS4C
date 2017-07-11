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
  @class CCredMemCreditBalanceModel
*/
class CCredMemCreditBalanceModel extends ViewModel 
{

    // Actual name of view being created.
    protected $name = "CCredMemCreditBalance";
    protected $preferred_db = 'plugin:CoopCredDatabase';

    protected $columns = array(
    'programID' => array('type'=>'INT'),
    'cardNo' => array('type'=>'INT'),
    'availableBalance' => array('type'=>'MONEY'),
    'balance' => array('type'=>'MONEY'),
    'mark' => array('type'=>'INT')
    );

    /*
Columns:
    programID int
    cardNo int
    availableBal[ance] (calculated) 
    balance (calculated)
    mark (calculated)


Depends on:
    CCredMemberships (table)
    CCredLiveBalance (view of t.dtransactions -> .v.dlog)
      so should be created first.

Use:
This view lists real-time Coop Cred
 balances by membership.
The "mark" column indicates an account
 whose balance has changed today
    */

    public function name()
    {
        return $this->name;
    }

    public function definition()
    {

        return "
    SELECT
        m.programID
            AS programID,
        m.cardNo
            AS cardNo, 
        (CASE WHEN a.balance is NULL THEN m.creditLimit
            ELSE m.creditLimit - a.balance END)
            AS availableBalance, 
        (CASE WHEN a.balance is NULL THEN 0 ELSE a.balance END)
            AS balance,
        CASE WHEN a.mark IS NULL THEN 0 ELSE a.mark END
            AS mark
    FROM CCredMemberships AS m
    LEFT JOIN CCredLiveBalance as a
        ON m.cardNo = a.cardNo AND m.programID = a.programID
            ";

    }

}

