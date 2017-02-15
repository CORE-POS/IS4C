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

namespace COREPOS\pos\plugins\Paycards\sql;
use \Exception;
use \PaycardConf;

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

    public function __construct($refNum, $dbTrans)
    {
        $this->conf = new PaycardConf();
        $this->dbTrans = $dbTrans;
        $this->refNum = $refNum;
        $this->today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
        $this->now = date('Y-m-d H:i:s'); // full timestamp
        $this->cashierNo = $this->conf->get("CashierNo");
        $this->laneNo = $this->conf->get("laneno");
        $this->transNo = $this->conf->get("transno");
        $this->transID = $this->conf->get("paycard_id");
        $this->setType($this->conf->get('CacheCardType'));
        list($this->amount, $this->cashback) = $this->initAmounts();
        $this->mode = (($this->amount < 0) ? 'Return' : 'Sale');
        $this->manual = ($this->conf->get("paycard_keyed")===True ? 1 : 0);
        $this->live = $this->isLive();
        $this->cardholder = 'Cardholder';
    }

    private function initAmounts()
    {
        $amount = $this->conf->get("paycard_amount");
        if (($this->type == "Debit" || $this->type == "EBTCASH") && $amount > $this->conf->get("amtdue")) {
            $cashback = $amount - $this->conf->get("amtdue");
            $amount = $this->conf->get("amtdue");
            return array($amount, $cashback);
        }

        return array($amount, 0);
    }

    private function isLive()
    {
        return ($this->conf->get("training") != 0 || $this->conf->get("CashierNo") == 9999) ? 1 : 0;
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

    public function setManual($man)
    {
        $this->manual = $man;
    }

    public function setRefNum($ref)
    {
        $this->refNum = $ref;
    }

    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    public function setAmount($amt)
    {
        $this->amount = $amt;
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
        $insP = $this->dbTrans->prepare($insQ);
        $insR = $this->dbTrans->execute($insP, $ptArgs);
        if ($insR === false) {
            throw new Exception('Error saving PaycardTransactions');
        }
        $this->last_paycard_transaction_id = $this->dbTrans->insertID();
    }

    public function changeAmount($amt)
    {
        $this->amount = $amt;
        $upQ = sprintf('UPDATE PaycardTransactions
                        SET amount=%.2f
                        WHERE paycardTransactionID=%d',
                        $amt, $this->last_paycard_transaction_id);
        $this->dbTrans->query($upQ);
    }

    public function updateCardInfo($pan, $name, $issuer)
    {
        $this->setPAN($pan);
        $this->cardholder = $name;
        $this->issuer = $issuer;
        $upP = $this->dbTrans->prepare('
            UPDATE PaycardTransactions
            SET PAN=?,
                issuer=?,
                name=?
            WHERE paycardTransactionID=?
        ');
        $this->dbTrans->execute($upP, array(
            $this->pan,
            $this->issuer,
            $this->cardholder,
            $this->last_paycard_transaction_id,
        ));
    }
}

