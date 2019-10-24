<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of IT CORE.

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
use COREPOS\pos\parser\Parser;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\Database;

class EquityInfoParser extends Parser 
{
    public function check($str)
    {
        return $str == 'MS' ? true : false;
    }

    public function parse($str)
    {
        $ret = $this->default_json();
        if (CoreLocal::get('memberID') == 0) {
            $ret['output'] = DisplayLib::boxMsg('enter member number first');
            return $ret;
        }
        $dbc = Database::mDataConnect();
        $prefix = CoreLocal::get('ServerPaymentPlanDB') . $dbc->sep();
        $query = sprintf("
            SELECT c.CardNo,
                c.FirstName,
                c.LastName,
                a.nextPaymentDate,
                p.name,
                a.nextPaymentAmount,
                h.payments,
                p.finalBalance
            FROM {$prefix}custdata AS c
                LEFT JOIN {$prefix}EquityPaymentPlanAccounts AS a ON c.CardNo=a.cardNo
                LEFT JOIN {$prefix}EquityPaymentPlans AS p ON a.equityPaymentPlanID=p.equityPaymentPlanID
                LEFT JOIN core_trans.equity_history_sum AS h ON c.CardNo = h.card_no
            WHERE c.CardNo=%d
                AND c.personNum=1",
            CoreLocal::get('memberID')
        );
        $result = $dbc->query($query);
        if (!$result || $dbc->numRows($result) == 0) {
            $ret['output'] = DisplayLib::boxMsg('member not found');
            return $ret;
        }
        $row = $dbc->fetchRow($result);

        $nextPay = ($row['nextPaymentDate']) ? date('F j, Y',strtotime($row['nextPaymentDate'])) : 'n/a';
    
        $msg = sprintf('
            Member #%d<br />
            Name: %s<br />
            Next Payment Due: %s<br />
            Payment Plan: %s<br />
            Next Payment Amount: $%.2f<br />
            Amount Remaining: $%.2f<br />',
            $row['CardNo'],
            $row['FirstName'] . ' ' . $row['LastName'],
            $nextPay,
            $row['name'],
            $row['nextPaymentAmount'],
            $row['finalBalance'] - $row['payments']
        );
        $ret['output'] = DisplayLib::boxMsg($msg, 'Member Information', true);

        return $ret;
    }
}

