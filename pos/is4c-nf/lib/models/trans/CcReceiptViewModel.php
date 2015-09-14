<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
  @class CcReceiptViewModel
*/
class CcReceiptViewModel extends ViewModel
{

    protected $name = "CcReceiptView";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'tranType' => array('type'=>'VARCHAR(255)'),
    'amount' => array('type'=>'MONEY'),
    'PAN' => array('type'=>'VARCHAR(255)'),
    'entryMethod' => array('type'=>'VARCHAR(255)'),
    'issuer' => array('type'=>'VARCHAR(255)'),
    'name' => array('type'=>'VARCHAR(255)'),
    'xResultMessage' => array('type'=>'VARCHAR(255)'),
    'xApprovalNumber' => array('type'=>'VARCHAR(255)'),
    'xTransactionID' => array('type'=>'VARCHAR(255)'),
    'date' => array('type'=>'INT'),
    'cashierNo' => array('type'=>'INT'),
    'laneNo' => array('type'=>'INT'),
    'transNo' => array('type'=>'INT'),
    'transID' => array('type'=>'INT'),
    'datetime' => array('type'=>'DATETIME'),
    'sortorder' => array('type'=>'INT'),
    );

    public function definition()
    {
        return "
            SELECT (case when (r.mode = 'tender') then 'Credit Card Purchase' 
                when (r.mode = 'retail_sale') then 'Credit Card Purchase' 
                when (r.mode = 'Credit_Sale') then 'Credit Card Purchase' 
                when (r.mode = 'retail_credit_alone') then 'Credit Card Refund' 
                when (r.mode = 'Credit_Return') then 'Credit Card Refund' 
                when (r.mode = 'refund') then 'Credit Card Refund' 
                else '' end) AS tranType,
            (case when (r.mode = 'refund' or r.mode='retail_credit_alone') then (-(1) * r.amount) else r.amount end) AS amount,
            r.PAN AS PAN,
            (case when (r.manual = 1) then 'Manual' else 'Swiped' end) AS entryMethod,
            r.issuer AS issuer,
            r.name AS name,
            s.xResultMessage AS xResultMessage,
            s.xApprovalNumber AS xApprovalNumber,
            s.xTransactionID AS xTransactionID,
            r.date AS date,
            r.cashierNo AS cashierNo,
            r.laneNo AS laneNo,
            r.transNo AS transNo,
            r.transID AS transID,
            r.datetime AS datetime,
            0 AS sortorder from (efsnetRequest r left join efsnetResponse s 
            on(((s.date = r.date) and (s.cashierNo = r.cashierNo) 
            and (s.laneNo = r.laneNo) and (s.transNo = r.transNo) 
            and (s.transID = r.transID)))) 
            where ((s.validResponse = 1) and 
            ((s.xResultMessage like '%APPROVE%') or (s.xResultMessage like '%PENDING%'))) 

            union all 
            
            select 
            (case when (r.mode = 'tender') then 'Credit Card Purchase CANCELED' 
            when (r.mode = 'retail_sale') then 'Credit Card Purchase CANCELLED' 
            when (r.mode = 'Credit_Sale') then 'Credit Card Purchase CANCELLED' 
            when (r.mode = 'retail_credit_alone') then 'Credit Card Refund CANCELLED' 
            when (r.mode = 'Credit_Return') then 'Credit Card Refund CANCELLED' 
            when (r.mode = 'refund') then 'Credit Card Refund CANCELED' 
            else '' end) AS tranType,
            (case when (r.mode = 'refund' or r.mode='retail_credit_alone') then r.amount else (-(1) * r.amount) end) AS amount,
            r.PAN AS PAN,
            (case when (r.manual = 1) then 'Manual' else 'Swiped' end) AS entryMethod,
            r.issuer AS issuer,
            r.name AS name,
            s.xResultMessage AS xResultMessage,
            s.xApprovalNumber AS xApprovalNumber,
            s.xTransactionID AS xTransactionID,
            r.date AS date,
            r.cashierNo AS cashierNo,
            r.laneNo AS laneNo,
            r.transNo AS transNo,r.transID AS transID,
            r.datetime AS datetime,
            1 AS sortorder from ((efsnetRequestMod m left join efsnetRequest r 
            on(((r.date = m.date) and (r.cashierNo = m.cashierNo) 
            and (r.laneNo = m.laneNo) and (r.transNo = m.transNo) 
            and (r.transID = m.transID)))) left join efsnetResponse s 
            on(((s.date = r.date) and (s.cashierNo = r.cashierNo) 
            and (s.laneNo = r.laneNo) 
            and (s.transNo = r.transNo) 
            and (s.transID = r.transID)))) 
            where ((s.validResponse = 1) 
            and (s.xResultMessage like '%APPROVE%') and (m.validResponse = 1) 
            and ((m.xResponseCode = 0) or (m.xResultMessage like '%APPROVE%')) 
            and (m.mode = 'void'))
        ";
    }

    public function doc()
    {
        return '
Use:
View of transaction timing to generate
cashier performance reports
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function tranType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tranType"])) {
                return $this->instance["tranType"];
            } elseif(isset($this->columns["tranType"]["default"])) {
                return $this->columns["tranType"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["tranType"] = func_get_arg(0);
        }
    }

    public function amount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["amount"])) {
                return $this->instance["amount"];
            } elseif(isset($this->columns["amount"]["default"])) {
                return $this->columns["amount"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["amount"] = func_get_arg(0);
        }
    }

    public function PAN()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["PAN"])) {
                return $this->instance["PAN"];
            } elseif(isset($this->columns["PAN"]["default"])) {
                return $this->columns["PAN"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["PAN"] = func_get_arg(0);
        }
    }

    public function entryMethod()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["entryMethod"])) {
                return $this->instance["entryMethod"];
            } elseif(isset($this->columns["entryMethod"]["default"])) {
                return $this->columns["entryMethod"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["entryMethod"] = func_get_arg(0);
        }
    }

    public function issuer()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["issuer"])) {
                return $this->instance["issuer"];
            } elseif(isset($this->columns["issuer"]["default"])) {
                return $this->columns["issuer"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["issuer"] = func_get_arg(0);
        }
    }

    public function name()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["name"])) {
                return $this->instance["name"];
            } elseif(isset($this->columns["name"]["default"])) {
                return $this->columns["name"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["name"] = func_get_arg(0);
        }
    }

    public function xResultMessage()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xResultMessage"])) {
                return $this->instance["xResultMessage"];
            } elseif(isset($this->columns["xResultMessage"]["default"])) {
                return $this->columns["xResultMessage"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["xResultMessage"] = func_get_arg(0);
        }
    }

    public function xApprovalNumber()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xApprovalNumber"])) {
                return $this->instance["xApprovalNumber"];
            } elseif(isset($this->columns["xApprovalNumber"]["default"])) {
                return $this->columns["xApprovalNumber"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["xApprovalNumber"] = func_get_arg(0);
        }
    }

    public function xTransactionID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xTransactionID"])) {
                return $this->instance["xTransactionID"];
            } elseif(isset($this->columns["xTransactionID"]["default"])) {
                return $this->columns["xTransactionID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["xTransactionID"] = func_get_arg(0);
        }
    }

    public function date()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["date"])) {
                return $this->instance["date"];
            } elseif(isset($this->columns["date"]["default"])) {
                return $this->columns["date"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["date"] = func_get_arg(0);
        }
    }

    public function cashierNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["cashierNo"])) {
                return $this->instance["cashierNo"];
            } elseif(isset($this->columns["cashierNo"]["default"])) {
                return $this->columns["cashierNo"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["cashierNo"] = func_get_arg(0);
        }
    }

    public function laneNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["laneNo"])) {
                return $this->instance["laneNo"];
            } elseif(isset($this->columns["laneNo"]["default"])) {
                return $this->columns["laneNo"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["laneNo"] = func_get_arg(0);
        }
    }

    public function transNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["transNo"])) {
                return $this->instance["transNo"];
            } elseif(isset($this->columns["transNo"]["default"])) {
                return $this->columns["transNo"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["transNo"] = func_get_arg(0);
        }
    }

    public function transID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["transID"])) {
                return $this->instance["transID"];
            } elseif(isset($this->columns["transID"]["default"])) {
                return $this->columns["transID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["transID"] = func_get_arg(0);
        }
    }

    public function datetime()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["datetime"])) {
                return $this->instance["datetime"];
            } elseif(isset($this->columns["datetime"]["default"])) {
                return $this->columns["datetime"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["datetime"] = func_get_arg(0);
        }
    }

    public function sortorder()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["sortorder"])) {
                return $this->instance["sortorder"];
            } elseif(isset($this->columns["sortorder"]["default"])) {
                return $this->columns["sortorder"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["sortorder"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

