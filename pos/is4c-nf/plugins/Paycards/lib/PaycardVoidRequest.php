<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

class PaycardVoidRequest extends PaycardRequest
{
    public function __construct($refnum)
    {
        parent::__construct($refnum);
        $original = CoreLocal::get('paycard_trans');
        $this->original = explode('-', $original);
    }

    public function saveRequest()
    {
        $dbTrans = PaycardLib::paycard_db();
        /**
          populate a void record in PaycardTransactions
        */
        $initQ = "INSERT INTO PaycardTransactions (
                    dateID, empNo, registerNo, transNo, transID,
                    previousPaycardTransactionID, processor, refNum,
                    live, cardType, transType, amount, PAN, issuer,
                    name, manual, requestDateTime)
                  SELECT dateID, empNo, registerNo, transNo, transID,
                    paycardTransactionID, processor, refNum,
                    live, cardType, 'VOID', amount, PAN, issuer,
                    name, manual, " . $dbTrans->now() . "
                  FROM PaycardTransactions
                  WHERE
                    dateID=" . $this->today . "
                    AND empNo=" . $this->cashierNo . "
                    AND registerNo=" . $this->original[1] . "
                    AND transNo=" . $this->original[2] . "
                    AND transID=" . $this->transID;
        $initR = $dbTrans->query($initQ);
        if ($initR) {
            $this->last_paycard_transaction_id = $dbTrans->insertID();
        } else {
            throw new Exception('Error saving void request in PaycardTransactions');
        }
    }

    public function findOriginal()
    {
        $dbTrans = PaycardLib::paycard_db();
        $sql = 'SELECT refNum,
                    xTransactionID,
                    amount,
                    xToken as token,
                    xProcessorRef as processData,
                    xAcquirerRef AS acqRefData,
                    xApprovalNumber,
                    transType AS mode,
                    cardType
                FROM PaycardTransactions
                WHERE dateID=' . $this->today . '
                    AND empNo=' . $this->cashierNo . '
                    AND registerNo=' . $this->original[1] . '
                    AND transNo=' . $this->original[2] . '
                    AND transID=' . $this->transID;
        $res = $dbTrans->query($sql);
        if ($res === false || $dbTrans->numRows($res) != 1) {
            throw new Exception('Could not locate original transaction');
        }

        return $dbTrans->fetchRow($res);
    }
}

