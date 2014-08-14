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
  @class EquityLiveBalanceModel
*/
class EquityLiveBalanceModel extends ViewModel 
{

    protected $name = "equity_live_balance";

    protected $columns = array(
    'memnum' => array('type'=>'INT','primary_key'=>True),
    'payments' => array('type','MONEY'),
    'startdate' => array('type','DATETIME')
    );

    public function definition()
    {
        global $FANNIE_OP_DB;
        return '
            SELECT
                m.card_no AS memnum,
                CASE
                    WHEN a.card_no IS NOT NULL AND b.card_no IS NOT NULL
                    THEN a.payments + b.totPayments
                    WHEN a.card_no IS NOT NULL
                    THEN a.payments
                    WHEN b.card_no IS NOT NULL
                    THEN b.totPayments
                    END AS payments,
                CASE WHEN a.startdate IS NULL THEN b.startdate
                    ELSE a.startdate END AS startdate
            FROM ' . $FANNIE_OP_DB . $this->connection->sep() . 'meminfo AS m 
                LEFT JOIN equity_history_sum AS a ON a.card_no=m.card_no
                LEFT JOIN stockSumToday AS b ON m.card_no=b.card_no
            WHERE a.card_no IS NOT NULL 
                OR b.card_no IS NOT NULL
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function memnum()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memnum"])) {
                return $this->instance["memnum"];
            } elseif(isset($this->columns["memnum"]["default"])) {
                return $this->columns["memnum"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["memnum"] = func_get_arg(0);
        }
    }

    public function payments()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["payments"])) {
                return $this->instance["payments"];
            } elseif(isset($this->columns["payments"]["default"])) {
                return $this->columns["payments"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["payments"] = func_get_arg(0);
        }
    }

    public function startdate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["startdate"])) {
                return $this->instance["startdate"];
            } elseif(isset($this->columns["startdate"]["default"])) {
                return $this->columns["startdate"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["startdate"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

