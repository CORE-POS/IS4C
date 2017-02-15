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

use COREPOS\pos\plugins\Paycards\card\CardReader;
use COREPOS\pos\plugins\Paycards\card\CardValidator;

class PaycardDialogs
{
    public function __construct()
    {
        $this->conf = new PaycardConf();
        $this->reader = new CardReader();
        $this->dbTrans = PaycardLib::paycard_db();
    }

    public function enabledCheck()
    {
        if ($this->conf->get('CCintegrate') != 1) {
            $this->conf->reset();
            throw new Exception(PaycardLib::paycardErrBox("Card Integration Disabled",
                                             "Please process credit cards in standalone",
                                             "[clear] to cancel"
            ));
        }

        return true;
    }

    public function validateCard($pan, $expirable=true, $luhn=true)
    {
        $validator = new CardValidator();
        if ($luhn && $validator->validNumber($pan) != 1) {
            $this->conf->reset();
            throw new Exception(PaycardLib::paycardErrBox("Invalid Card Number",
                "Swipe again or type in manually",
                "[clear] to cancel"));
        } elseif (!$this->reader->accepted($pan)) {
            $this->conf->reset();
            throw new Exception(PaycardLib::paycardMsgBox("Unsupported Card Type",
                "We cannot process " . $this->conf->get("paycard_issuer") . " cards",
                "[clear] to cancel"));
        } elseif ($expirable && $validator->validExpiration($this->conf->get("paycard_exp")) != 1) {
            $this->conf->reset();
            throw new Exception(PaycardLib::paycardErrBox("Invalid Expiration Date",
                "The expiration date has passed or was not recognized",
                "[clear] to cancel"));
        }

        return true;
    }

    public function voidableCheck($pan4, $trans)
    {
        $today = date('Ymd');
        $sql = 'SELECT transID
                FROM PaycardTransactions
                WHERE dateID=' . $today . '
                    AND empNo=' . $trans[0] . '
                    AND registerNo=' . $trans[1] . '
                    AND transNo=' . $trans[2] . '
                    AND PAN LIKE \'%' . $pan4 . '\'';
        $search = $this->dbTrans->query($sql);
        $num = $this->dbTrans->numRows($search);
        if ($num < 1) {
            $this->conf->reset();
            throw new Exception(PaycardLib::paycardMsgBox("Card Not Used",
                                                         "That card number was not used in this transaction",
                                                         "[clear] to cancel"
            ));
        } elseif ($num > 1) {
            $this->conf->reset();
            throw new Exception(PaycardLib::paycardMsgBox("Multiple Uses",
                                                         "That card number was used more than once in this transaction; select the payment and press VOID",
                                                         "[clear] to cancel"
            ));
        }
        $payment = $this->dbTrans->fetchRow($search);
        return $payment['transID'];
    }

    public function invalidMode()
    {
        return PaycardLib::paycardErrBox("Invalid Mode",
                                         "This card type does not support that processing mode",
                                         "[clear] to cancel"
        );
    }

    public function getRequest($trans, $transID)
    {
        $today = date('Ymd');
        // look up the request using transID (within this transaction)
        $sql = "SELECT live,
                    PAN,
                    transType AS mode,
                    amount,
                    name
                FROM PaycardTransactions
                WHERE dateID=" . $today . "
                    AND empNo=" . $trans[0] . "
                    AND registerNo=" . $trans[1] . "
                    AND transNo=" . $trans[2] . " 
                    AND transID=" . $transID;
        $search = $this->dbTrans->query($sql);
        $num = $this->dbTrans->numRows($search);
        if ($num < 1) {
            $this->conf->reset();
            throw new Exception(PaycardLib::paycardErrBox("Internal Error",
                                                         "Card request not found, unable to void",
                                                         "[clear] to cancel"
            ));
        } elseif ($num > 1) {
            $this->conf->reset();
            throw new Exception(PaycardLib::paycardErrBox("Internal Error",
                                                          "Card request not distinct, unable to void",
                                                          "[clear] to cancel"
            ));
        }
        $request = $this->dbTrans->fetchRow($search);

        return $request;
    }

    public function getResponse($trans, $transID)
    {
        $today = date('Ymd');
        $sql = "SELECT commErr,
                    httpCode,
                    validResponse,
                    xResultCode AS xResponseCode,
                    xTransactionID
                FROM PaycardTransactions 
                WHERE dateID=" . $today . " 
                    AND empNo=" . $trans[0] . "
                    AND registerNo=" . $trans[1] ."
                    AND transNo=" . $trans[2] . "
                    AND transID=" . $transID;
        $search = $this->dbTrans->query($sql);
        $num = $this->dbTrans->numRows($search);

        if ($num < 1) {
            $this->conf->reset();
            throw new Exception(PaycardLib::paycardErrBox("Internal Error",
                                                         "Card response not found, unable to void",
                                                         "[clear] to cancel"
            ));
        } elseif ($num > 1) {
            $this->conf->reset();
            throw new Exception(PaycardLib::paycardErrBox("Internal Error",
                                                         "Card response not distinct, unable to void",
                                                         "[clear] to cancel"
            ));
        }
        $response = $this->dbTrans->fetchRow($search);
        return $response;
    }

    public function getTenderLine($trans, $transID)
    {
        // look up the transaction tender line-item
        $sql = "SELECT trans_type,
                    trans_subtype,
                    trans_status,
                    voided
                FROM localtemptrans 
                WHERE trans_id=" . $transID;
        $search = $this->dbTrans->query($sql);
        $num = $this->dbTrans->numRows($search);
        if ($num < 1) {
            $sql = "SELECT * FROM localtranstoday WHERE trans_id=".$transID." and emp_no=".$trans[0]
                ." and register_no=".$trans[1]." and trans_no=".$trans[2]
                ." AND datetime >= " . $this->dbTrans->curdate();
            $search = $this->dbTrans->query($sql);
            $num = $this->dbTrans->numRows($search);
            if ($num != 1) {
                $this->conf->reset();
                throw new Exception(PaycardLib::paycardErrBox("Internal Error",
                                                             "Transaction item not found, unable to void",
                                                             "[clear] to cancel"
                ));
            }
        } elseif ($num > 1) {
            $this->conf->reset();
            throw new Exception(PaycardLib::paycardErrBox("Internal Error",
                                                         "Transaction item not distinct, unable to void",
                                                         "[clear] to cancel"
            ));
        }
        $lineitem = $this->dbTrans->fetchRow($search);
        return $lineitem;
    }

    public function notVoided($trans, $transID)
    {
        $today = date('Ymd');
        $sql = "SELECT transID 
                FROM PaycardTransactions 
                WHERE dateID=" . $today . "
                    AND empNo=" . $trans[0] . "
                    AND registerNo=" . $trans[1] . "
                    AND transNo=" . $trans[2] . "
                    AND transID=" . $transID . "
                    AND transType='VOID'
                    AND xResultCode=1";
        $search = $this->dbTrans->query($sql);
        $voided = $this->dbTrans->numRows($search);
        if ($voided > 0) {
            $this->conf->reset();
            throw new Exception(PaycardLib::paycardErrBox("Unable to Void",
                                                         "Card transaction already voided",
                                                         "[clear] to cancel"
            ));
        }

        return true;
    }

    private function voidReqResp($request, $response)
    {
        $error = false;
        if ($response['commErr'] != 0 || $response['httpCode'] != 200 || $response['validResponse'] != 1) {
            $error = _("Card transaction not successful");
        } elseif ($request['live'] != $this->paycardLive(PaycardLib::PAYCARD_TYPE_CREDIT)) {
            // this means the transaction was submitted to the test platform, but we now think we're in live mode, or vice-versa
            // I can't imagine how this could happen (short of serious $_SESSION corruption), but worth a check anyway.. --atf 7/26/07
            $error = _("Processor platform mismatch");
        } elseif( $response['xResponseCode'] != 1) {
            $error = _("Card transaction not approved");
        } elseif( $response['xTransactionID'] < 1) {
            $error = _("Invalid reference number");
        }

        return $error;
    }

    private function voidLineItem($lineitem)
    {
        $error = false;
        if ($lineitem['trans_type'] != "T" || ($lineitem['trans_subtype'] != "CC" && $lineitem['trans_subtype'] != 'DC'
            && $lineitem['trans_subtype'] != 'EF' && $lineitem['trans_subtype'] != 'EC' && $lineitem['trans_subtype'] != 'AX') ) {
            $error = _("Authorization and tender records do not match ");
        } elseif ($lineitem['trans_status'] == "V" || $lineitem['voided'] != 0) {
            $error = _("Void records do not match");
        }

        return $error;
    }

    public function validateVoid($request, $response, $lineitem)
    {
        // make sure the payment is applicable to void
        $errHeader = _('Unable to Void');
        $buttons = _('[clear] to cancel');
        $error = $this->voidReqResp($request, $response);
        if ($error === false) {
            $error = $this->voidLineItem($lineitem);
        }

        if ($error !== false) {
            throw new Exception(PaycardLib::paycardErrBox($errHeader, $error, $buttons));
        }

        return true;
    }

    /**
      Check whether paycards of a given type are enabled
      @param $type is a paycard type constant
      @return
       - 1 if type is enabled
       - 0 if type is disabled
    */
    public function paycardLive($type = PaycardLib::PAYCARD_TYPE_UNKNOWN) 
    {
        // these session vars require training mode no matter what card type
        if ($this->conf->get("training") != 0 || $this->conf->get("CashierNo") == 9999)
            return 0;

        // special session vars for each card type
        if ($type === PaycardLib::PAYCARD_TYPE_CREDIT && $this->conf->get('CCintegrate') != 1) {
            return 0;
        }

        return 1;
    }

}

