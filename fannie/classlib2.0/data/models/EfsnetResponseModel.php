<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
  @class EfsnetResponseModel
*/
class EfsnetResponseModel extends BasicModel
{

    protected $name = "efsnetResponse";

    protected $preferred_db = 'trans';

    protected $columns = array(
    'date' => array('type'=>'INT'),
    'cashierNo' => array('type'=>'INT'),
    'laneNo' => array('type'=>'INT'),
    'transNo' => array('type'=>'INT'),
    'transID' => array('type'=>'INT'),
    'datetime' => array('type'=>'DATETIME'),
    'refNum' => array('type'=>'VARCHAR(50)'),
    'seconds' => array('type'=>'FLOAT'),
    'commErr' => array('type'=>'INT'),
    'httpCode' => array('type'=>'INT'),
    'validResponse' => array('type'=>'SMALLINT'),
    'xResponseCode' => array('type'=>'VARCHAR(4)'),
    'xResultCode' => array('type'=>'VARCHAR(8)'),
    'xResultMessage' => array('type'=>'VARCHAR(100)'),
    'xTransactionID' => array('type'=>'VARCHAR(12)'),
    'xApprovalNumber' => array('type'=>'VARCHAR(20)'),
    'efsnetRequestID' => array('type'=>'INT', 'index'=>true),
    );

    /* START ACCESSOR FUNCTIONS */

    public function date()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["date"])) {
                return $this->instance["date"];
            } else if (isset($this->columns["date"]["default"])) {
                return $this->columns["date"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'date',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["date"]) || $this->instance["date"] != func_get_args(0)) {
                if (!isset($this->columns["date"]["ignore_updates"]) || $this->columns["date"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["date"] = func_get_arg(0);
        }
        return $this;
    }

    public function cashierNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["cashierNo"])) {
                return $this->instance["cashierNo"];
            } else if (isset($this->columns["cashierNo"]["default"])) {
                return $this->columns["cashierNo"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'cashierNo',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["cashierNo"]) || $this->instance["cashierNo"] != func_get_args(0)) {
                if (!isset($this->columns["cashierNo"]["ignore_updates"]) || $this->columns["cashierNo"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["cashierNo"] = func_get_arg(0);
        }
        return $this;
    }

    public function laneNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["laneNo"])) {
                return $this->instance["laneNo"];
            } else if (isset($this->columns["laneNo"]["default"])) {
                return $this->columns["laneNo"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'laneNo',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["laneNo"]) || $this->instance["laneNo"] != func_get_args(0)) {
                if (!isset($this->columns["laneNo"]["ignore_updates"]) || $this->columns["laneNo"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["laneNo"] = func_get_arg(0);
        }
        return $this;
    }

    public function transNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["transNo"])) {
                return $this->instance["transNo"];
            } else if (isset($this->columns["transNo"]["default"])) {
                return $this->columns["transNo"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'transNo',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["transNo"]) || $this->instance["transNo"] != func_get_args(0)) {
                if (!isset($this->columns["transNo"]["ignore_updates"]) || $this->columns["transNo"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["transNo"] = func_get_arg(0);
        }
        return $this;
    }

    public function transID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["transID"])) {
                return $this->instance["transID"];
            } else if (isset($this->columns["transID"]["default"])) {
                return $this->columns["transID"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'transID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["transID"]) || $this->instance["transID"] != func_get_args(0)) {
                if (!isset($this->columns["transID"]["ignore_updates"]) || $this->columns["transID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["transID"] = func_get_arg(0);
        }
        return $this;
    }

    public function datetime()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["datetime"])) {
                return $this->instance["datetime"];
            } else if (isset($this->columns["datetime"]["default"])) {
                return $this->columns["datetime"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'datetime',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["datetime"]) || $this->instance["datetime"] != func_get_args(0)) {
                if (!isset($this->columns["datetime"]["ignore_updates"]) || $this->columns["datetime"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["datetime"] = func_get_arg(0);
        }
        return $this;
    }

    public function refNum()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["refNum"])) {
                return $this->instance["refNum"];
            } else if (isset($this->columns["refNum"]["default"])) {
                return $this->columns["refNum"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'refNum',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["refNum"]) || $this->instance["refNum"] != func_get_args(0)) {
                if (!isset($this->columns["refNum"]["ignore_updates"]) || $this->columns["refNum"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["refNum"] = func_get_arg(0);
        }
        return $this;
    }

    public function seconds()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["seconds"])) {
                return $this->instance["seconds"];
            } else if (isset($this->columns["seconds"]["default"])) {
                return $this->columns["seconds"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'seconds',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["seconds"]) || $this->instance["seconds"] != func_get_args(0)) {
                if (!isset($this->columns["seconds"]["ignore_updates"]) || $this->columns["seconds"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["seconds"] = func_get_arg(0);
        }
        return $this;
    }

    public function commErr()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["commErr"])) {
                return $this->instance["commErr"];
            } else if (isset($this->columns["commErr"]["default"])) {
                return $this->columns["commErr"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'commErr',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["commErr"]) || $this->instance["commErr"] != func_get_args(0)) {
                if (!isset($this->columns["commErr"]["ignore_updates"]) || $this->columns["commErr"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["commErr"] = func_get_arg(0);
        }
        return $this;
    }

    public function httpCode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["httpCode"])) {
                return $this->instance["httpCode"];
            } else if (isset($this->columns["httpCode"]["default"])) {
                return $this->columns["httpCode"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'httpCode',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["httpCode"]) || $this->instance["httpCode"] != func_get_args(0)) {
                if (!isset($this->columns["httpCode"]["ignore_updates"]) || $this->columns["httpCode"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["httpCode"] = func_get_arg(0);
        }
        return $this;
    }

    public function validResponse()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["validResponse"])) {
                return $this->instance["validResponse"];
            } else if (isset($this->columns["validResponse"]["default"])) {
                return $this->columns["validResponse"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'validResponse',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["validResponse"]) || $this->instance["validResponse"] != func_get_args(0)) {
                if (!isset($this->columns["validResponse"]["ignore_updates"]) || $this->columns["validResponse"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["validResponse"] = func_get_arg(0);
        }
        return $this;
    }

    public function xResponseCode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xResponseCode"])) {
                return $this->instance["xResponseCode"];
            } else if (isset($this->columns["xResponseCode"]["default"])) {
                return $this->columns["xResponseCode"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'xResponseCode',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["xResponseCode"]) || $this->instance["xResponseCode"] != func_get_args(0)) {
                if (!isset($this->columns["xResponseCode"]["ignore_updates"]) || $this->columns["xResponseCode"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["xResponseCode"] = func_get_arg(0);
        }
        return $this;
    }

    public function xResultCode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xResultCode"])) {
                return $this->instance["xResultCode"];
            } else if (isset($this->columns["xResultCode"]["default"])) {
                return $this->columns["xResultCode"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'xResultCode',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["xResultCode"]) || $this->instance["xResultCode"] != func_get_args(0)) {
                if (!isset($this->columns["xResultCode"]["ignore_updates"]) || $this->columns["xResultCode"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["xResultCode"] = func_get_arg(0);
        }
        return $this;
    }

    public function xResultMessage()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xResultMessage"])) {
                return $this->instance["xResultMessage"];
            } else if (isset($this->columns["xResultMessage"]["default"])) {
                return $this->columns["xResultMessage"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'xResultMessage',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["xResultMessage"]) || $this->instance["xResultMessage"] != func_get_args(0)) {
                if (!isset($this->columns["xResultMessage"]["ignore_updates"]) || $this->columns["xResultMessage"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["xResultMessage"] = func_get_arg(0);
        }
        return $this;
    }

    public function xTransactionID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xTransactionID"])) {
                return $this->instance["xTransactionID"];
            } else if (isset($this->columns["xTransactionID"]["default"])) {
                return $this->columns["xTransactionID"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'xTransactionID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["xTransactionID"]) || $this->instance["xTransactionID"] != func_get_args(0)) {
                if (!isset($this->columns["xTransactionID"]["ignore_updates"]) || $this->columns["xTransactionID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["xTransactionID"] = func_get_arg(0);
        }
        return $this;
    }

    public function xApprovalNumber()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xApprovalNumber"])) {
                return $this->instance["xApprovalNumber"];
            } else if (isset($this->columns["xApprovalNumber"]["default"])) {
                return $this->columns["xApprovalNumber"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'xApprovalNumber',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["xApprovalNumber"]) || $this->instance["xApprovalNumber"] != func_get_args(0)) {
                if (!isset($this->columns["xApprovalNumber"]["ignore_updates"]) || $this->columns["xApprovalNumber"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["xApprovalNumber"] = func_get_arg(0);
        }
        return $this;
    }

    public function efsnetRequestID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["efsnetRequestID"])) {
                return $this->instance["efsnetRequestID"];
            } else if (isset($this->columns["efsnetRequestID"]["default"])) {
                return $this->columns["efsnetRequestID"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'efsnetRequestID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["efsnetRequestID"]) || $this->instance["efsnetRequestID"] != func_get_args(0)) {
                if (!isset($this->columns["efsnetRequestID"]["ignore_updates"]) || $this->columns["efsnetRequestID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["efsnetRequestID"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

