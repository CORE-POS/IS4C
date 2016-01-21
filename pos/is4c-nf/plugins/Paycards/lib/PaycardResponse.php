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

class PaycardResponse
{
    public $validResponse = 1;
    public $responseCode = 0;
    public $resultCode = 0;
    public $resultMsg = '';
    public $approvalNum = '';
    public $transactionID = '';
    public $normalizedCode = 0;
    public $balance = 0;
    public $token = array(
        'record' => '',
        'proc' => '',
        'acq' => '',
    );


    public function __construct($req, $curlResult)
    {
        $this->request = $req;
        $this->now = date('Y-m-d H:i:s');
        $this->curlTime = $curlResult['curlTime'];
        $this->curlErr = $curlResult['curlErr'];
        $this->curlHttp = $curlResult['curlHTTP'];
    }

    public function saveResponse()
    {
        $dbTrans = PaycardLib::paycard_db();

        $finishQ = sprintf("UPDATE PaycardTransactions
                            SET responseDatetime='%s',
                                seconds=%f,
                                commErr=%d,
                                httpCode=%d,
                                validResponse=%d,
                                xResultCode=%d,
                                xApprovalNumber='%s',
                                xResponseCode=%d,
                                xResultMessage='%s',
                                xTransactionID='%s',
                                xBalance='%s',
                                xToken='%s',
                                xProcessorRef='%s',
                                xAcquirerRef='%s'
                            WHERE paycardTransactionID=%d",
                            $this->now,
                            $this->curlTime,
                            $this->curlErr,
                            $this->curlHttp,
                            $this->normalizedCode,
                            $this->responseCode,
                            $this->approvalNum,
                            $this->resultCode,
                            $this->resultMsg,
                            $this->transactionID,
                            $this->balance,
                            $this->token['record'],
                            $this->token['proc'],
                            $this->token['acq'],
                            $this->request->last_paycard_transaction_id
        );
        $throw = false;
        if (!$dbTrans->query($finishQ)) {
            throw new Exception('Error updating PaycardTransactions with response data');
        }
    }


    public function setToken($r, $p, $a)
    {
        $this->token['record'] = $r;
        $this->token['proc'] = $p;
        $this->token['acq'] = $a;
    }

    public function setBalance($b)
    {
        $this->balance = $b;
    }

    public function setValid($v)
    {
        $this->validResponse = $v;
    }

    public function setResponseCode($r)
    {
        $this->responseCode = $r;
    }

    public function setResultCode($r)
    {
        $this->resultCode = $r;
    }

    public function setResultMsg($r)
    {
        $this->resultMsg = substr($r, 0, 100);
    }

    public function setApprovalNum($a)
    {
        $this->approvalNum = $a;
    }

    public function setTransactionID($t)
    {
        $this->transactionID = substr($t, 0, 12);
    }

    public function setNormalizedCode($n)
    {
        $this->normalizedCode = $n;
    }
}

