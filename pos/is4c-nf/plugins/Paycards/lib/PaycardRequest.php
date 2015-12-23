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

class PaycardRequest
{
    public $issuer = 'UNKNOWN';
    public $sent = array(
        'pan' => 0,
        'exp' => 0,
        'track1' => 0,
        'track2' => 0,
    );
    public $pan = '';

    public function __construct($refNum)
    {
        $this->refNum = $refNum;
        $this->today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
        $this->now = date('Y-m-d H:i:s'); // full timestamp
        $this->cashierNo = CoreLocal::get("CashierNo");
        $this->laneNo = CoreLocal::get("laneno");
        $this->transNo = CoreLocal::get("transno");
        $this->transID = CoreLocal::get("paycard_id");
        $this->setType(CoreLocal::get('CacheCardType'));
        $this->amount = CoreLocal::get("paycard_amount");
        if (($this->type == "Debit" || $this->type == "EBTCASH") && $this->amount > CoreLocal::get("amtdue")) {
            $this->cashback = $this->amount - CoreLocal::get("amtdue");
            $this->amount = CoreLocal::get("amtdue");
        } else {
            $this->cashback = 0;
        }
        $this->mode = (($this->amount < 0) ? 'Return' : 'Sale');
        $this->manual = (CoreLocal::get("paycard_keyed")===True ? 1 : 0);
        $this->live = 1;
        if (CoreLocal::get("training") != 0 || CoreLocal::get("CashierNo") == 9999) {
            $this->live = 0;
        }
        $this->cardholder = 'Cardholder';
    }

    public function formattedAmount()
    {
        return number_format(abs($this->amount), 2, '.', '');
    }

    public function formattedCashBack()
    {
        return number_format(abs($this->cashback), 2, '.', '');
    }

    protected function setType($type)
    {
        if ($type == "CREDIT") $this->type = "Credit";
        elseif ($type == "DEBIT") $this->type = "Debit";
        elseif ($type == "") $this->type = "Credit";
        else $this->type = $type;
    }

    public function setManual($m)
    {
        $this->manual = $m;
    }

    public function setRefNum($ref)
    {
        $this->refNum = $ref;
    }

    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    public function setAmount($a)
    {
        $this->amount = $a;
    }

    public function setCardholder($name)
    {
        $name = str_replace("\\",'',$name);
        $this->cardholder = $name;
    }

    public function setIssuer($issuer)
    {
        $this->issuer = $issuer;
    }

    public function setPAN($pan)
    {
        $this->pan = str_repeat('*', 12) . substr($pan, -4);
    }

    public function setSent($pan, $exp, $tr1, $tr2)
    {
        $this->sent = array(
            'pan' => $pan,
            'exp' => $exp,
            'track1' => $tr1,
            'track2' => $tr2,
        );
    }

    public function setProcessor($proc)
    {
        $this->processor = $proc;
    }

    public function saveRequest()
    {
        $dbTrans = PaycardLib::paycard_db();
        if ($dbTrans->table_exists('efsnetRequest')) {
            $this->legacySave($dbTrans);
        }

        $insQ = '
                INSERT INTO PaycardTransactions (
                    dateID, empNo, registerNo, transNo, transID,
                    processor, refNum, live, cardType, transType,
                    amount, PAN, issuer, name, manual, requestDateTime)
                VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?
                )';
        $ptArgs = array(
            $this->today, $this->cashierNo, $this->laneNo, $this->transNo, $this->transID,
            $this->processor, $this->refNum, $this->live, $this->type, $this->mode,
            ($this->amount+$this->cashback), $this->pan, $this->issuer,
            $this->cardholder, $this->manual, $this->now,
        );
        $insP = $dbTrans->prepare($insQ);
        $insR = $dbTrans->execute($insP, $ptArgs);
        if ($insR) {
            $this->last_paycard_transaction_id = $dbTrans->insertID();
        } else {
            throw new Exception('Error saving PaycardTransactions');
        }
    }

    protected function legacySave($dbTrans)
    {
        $sql = 'INSERT INTO efsnetRequest (' .
                    $dbTrans->identifierEscape('date') . ', cashierNo, laneNo, transNo, transID, ' .
                    $dbTrans->identifierEscape('datetime') . ', refNum, live, mode, amount,
                    PAN, issuer, manual, name,
                    sentPAN, sentExp, sentTr1, sentTr2)
                VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, 
                    ?, ?, ?, ?)'; 
        $efsArgs = array(
            $this->today,
            $this->cashierNo,
            $this->laneNo,
            $this->transNo,
            $this->transID,
            $this->now,
            $this->refNum,
            $this->live,
            $this->type . '_' . $this->mode,
            $this->amount + $this->cashback,
            $this->pan,
            $this->issuer,
            $this->manual,
            $this->cardholder,
            $this->sent['pan'],
            $this->sent['exp'],
            $this->sent['track1'],
            $this->sent['track2'],
        );
        $prep = $dbTrans->prepare($sql);
        if (!$dbTrans->execute($prep, $efsArgs)){
            throw new Exception('Error saving efsnetRequest');
        }
        $this->last_req_id = $dbTrans->insertID();
    }
    
    public function changeAmount($amt)
    {
        $this->amount = $amt;
        $dbTrans = PaycardLib::paycard_db();
        $upQ = sprintf('UPDATE PaycardTransactions
                        SET amount=%.2f
                        WHERE paycardTransactionID=%d',
                        $amt, $this->last_paycard_transaction_id);
        $dbTrans->query($upQ);

        $sql = sprintf("UPDATE efsnetRequest SET amount=%f WHERE "
            .$dbTrans->identifierEscape('date')."=%d 
            AND cashierNo=%d AND laneNo=%d AND transNo=%d
            AND transID=%d",
            $amt,$this->today, $this->cashierNo, $this->laneNo, $this->transNo, $this->transID);
        if ($dbTrans->table_exists('efsnetRequest')) {
            PaycardLib::paycard_db_query($sql, $dbTrans);
        }
    }

    public function updateCardInfo($pan, $name, $issuer)
    {
        $this->setPAN($pan);
        $this->cardholder = $name;
        $this->issuer = $issuer;
        $dbTrans = PaycardLib::paycard_db();
        $upP = $dbTrans->prepare('
            UPDATE PaycardTransactions
            SET PAN=?,
                issuer=?,
                name=?
            WHERE paycardTransactionID=?
        ');
        $dbTrans->execute($upP, array(
            $this->pan,
            $this->issuer,
            $this->cardholder,
            $this->last_paycard_transaction_id,
        ));
    }
}

