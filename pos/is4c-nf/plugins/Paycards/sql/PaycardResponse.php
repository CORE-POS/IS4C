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

    public function __construct($req, $curlResult, $dbTrans)
    {
        $this->request = $req;
        $this->now = date('Y-m-d H:i:s');
        $this->curlTime = $curlResult['curlTime'];
        $this->curlErr = $curlResult['curlErr'];
        $this->curlHttp = $curlResult['curlHTTP'];
        $this->dbTrans = $dbTrans;
    }

    public function saveResponse()
    {
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
        if (!$this->dbTrans->query($finishQ)) {
            throw new Exception('Error updating PaycardTransactions with response data');
        }
    }


    public function setToken($rec, $proc, $acq)
    {
        $this->token['record'] = $rec;
        $this->token['proc'] = $proc;
        $this->token['acq'] = $acq;
    }

    public function setBalance($bal)
    {
        $this->balance = $bal;
    }

    public function setValid($valid)
    {
        $this->validResponse = $valid;
    }

    public function setResponseCode($res)
    {
        $this->responseCode = $res;
    }

    public function setResultCode($res)
    {
        $this->resultCode = $res;
    }

    public function setResultMsg($res)
    {
        $this->resultMsg = substr($res, 0, 100);
    }

    public function setApprovalNum($appr)
    {
        $this->approvalNum = $appr;
    }

    public function setTransactionID($tid)
    {
        $this->transactionID = substr($tid, 0, 12);
    }

    public function setNormalizedCode($norm)
    {
        $this->normalizedCode = $norm;
    }
}

