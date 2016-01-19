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
            $throw = new Exception('Error updating PaycardTransactions with response data');
        }

        if ($dbTrans->table_exists('efsnetRequest')) {
            try {
                if (!empty($this->token['record'])) {
                    $this->legacyToken($dbTrans);
                }
                if ($this->request instanceof PaycardVoidRequest) {
                    $this->legacyVoid($dbTrans);
                } elseif ($this->request instanceof PaycardGiftRequest) {
                    // pass; no legacy table
                } else {
                    $this->legacySave($dbTrans);
                }
            } catch (Exception $ex) {
                if ($throw === false) {
                    $throw = $ex;
                }
            }
        }

        // delay throwing until both saves have been attempted
        if ($throw !== false) {
            throw $throw;
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

    private function legacyToken($dbTrans)
    {
        $tokenSql = sprintf("INSERT INTO efsnetTokens (expireDay, refNum, token, processData, acqRefData) 
                VALUES ('%s','%s','%s','%s','%s')",
            $this->now,
            $this->request->refNum, 
            $this->token['record'],
            $this->token['proc'],
            $this->token['acq']
        );
        $dbTrans->query($tokenSql);
    }

    private function legacySave($dbTrans)
    {
        $sqlColumns =
            $dbTrans->identifierEscape('date').",cashierNo,laneNo,transNo,transID," .
            $dbTrans->identifierEscape('datetime').",refNum," .
            "seconds,commErr,httpCode";
        $sqlValues =
            sprintf("%d,%d,%d,%d,%d,",  $this->request->today, $this->request->cashierNo, $this->request->laneNo, $this->request->transNo, $this->request->transID) .
            sprintf("'%s','%s',",            $this->now, $this->request->refNum ) .
            sprintf("%f,%d,%d",         $this->curlTime, $this->curlErr, $this->curlHttp);
        $sqlColumns .= ",xResponseCode";
        $sqlValues .= sprintf(",%d",$this->responseCode);
        $sqlColumns .= ",xResultCode";
        $sqlValues .= sprintf(",%d",$this->resultCode);
        $sqlColumns .= ",xResultMessage";
        $sqlValues .= sprintf(",'%s'",$this->resultMsg);
        $sqlColumns .= ",xApprovalNumber";
        $sqlValues .= sprintf(",'%s'",$this->approvalNum);
        $sqlColumns .= ",validResponse";
        $sqlValues .= sprintf(",%d",$this->validResponse);
        $sqlColumns .= ",xTransactionID";
        $sqlValues .= sprintf(",'%s'", $this->transactionID);
        $sqlColumns .= ', efsnetRequestID';
        $sqlValues .= sprintf(', %d', $this->request->last_req_id);
        $sql = "INSERT INTO efsnetResponse (" . $sqlColumns . ") VALUES (" . $sqlValues . ")";

        if (!$dbTrans->query($sql)) {
            throw new Exception('Error saving efsnetResponse');
        }
    }

    private function legacyVoid($dbTrans)
    {
        // prepare some fields to store the request and the parsed response; we'll add more as we verify it
        $sqlColumns =
            $dbTrans->identifierEscape('date').",cashierNo,laneNo,transNo,transID,".
            $dbTrans->identifierEscape('datetime').
            ",origAmount,mode,altRoute," .
            "seconds,commErr,httpCode";
        $sqlValues =
            sprintf("%d,%d,%d,%d,%d,'%s',",  $this->request->today, $this->request->cashierNo, $this->request->laneNo, $this->request->transNo, $this->request->transID, $this->now) .
            sprintf("%s,'%s',%d,",  $this->request->formattedAmount(), "VOID", 0) .
            sprintf("%f,%d,%d", $this->curlTime, $this->curlErr, $this->curlHttp);
        $sqlColumns .= ",xResponseCode";
        $sqlValues .= sprintf(",%d",$this->responseCode);
        $sqlColumns .= ",xResultCode";
        $sqlValues .= sprintf(",%d",$this->resultCode);
        $sqlColumns .= ",xResultMessage";
        $sqlValues .= sprintf(",'%s'",$this->resultMsg);
        $sqlColumns .= ",origTransactionID";
        $sqlValues .= sprintf(",'%s'", $this->request->transID);
        $sqlColumns .= ",origRefNum";
        $sqlValues .= sprintf(",'%d-%d-%d'",$this->request->original[0], $this->request->original[1], $this->request->original[2]);
        $sqlColumns .= ",validResponse";
        $sqlValues .= sprintf(",%d",$this->validResponse);
        $sql = "INSERT INTO efsnetRequestMod (" . $sqlColumns . ") VALUES (" . $sqlValues . ")";

        if (!$dbTrans->query($sql)) {
            throw new Exception('Error saving efsnetRequestMod');
        }
    }
}

