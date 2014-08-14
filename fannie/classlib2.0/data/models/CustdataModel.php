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
  @class CustdataModel

*/

if (!class_exists('FannieDB')) {
    include(dirname(__FILE__).'/../FannieDB.php');
}

class CustdataModel extends BasicModel 
{

    protected $name = 'custdata';

    protected $preferred_db = 'op';

    protected $columns = array(
    'CardNo' => array('type'=>'INT','index'=>True),
    'personNum' => array('type'=>'TINYINT'),
    'LastName' => array('type'=>'VARCHAR(30)','index'=>True),
    'FirstName' => array('type'=>'VARCHAR(30)'),
    'CashBack' => array('type'=>'MONEY'),
    'Balance' => array('type'=>'MONEY'),
    'Discount' => array('type'=>'SMALLINT'),
    'MemDiscountLimit' => array('type'=>'MONEY','default'=>0),
    'ChargeLimit' => array('type'=>'MONEY','default'=>0),
    'ChargeOk' => array('type'=>'TINYINT','default'=>0),
    'WriteChecks' => array('type'=>'TINYINT','default'=>1),
    'StoreCoupons' => array('type'=>'TINYINT','default'=>1),
    'Type' => array('type'=>'VARCHAR(10)','default'=>"'PC'"),
    'memType' => array('type'=>'TINYINT'),
    'staff' => array('type'=>'TINYINT','default'=>0),
    'SSI' => array('type'=>'TINYINT','default'=>0),
    'Purchases' => array('type'=>'MONEY','default'=>0),
    'NumberOfChecks' => array('type'=>'SMALLINT','default'=>0),
    'memCoupons' => array('type'=>'INT','default'=>1),
    'blueLine' => array('type'=>'VARCHAR(50)'),
    'Shown' => array('type'=>'TINYINT','default'=>1),
    'LastChange' => array('type'=>'TIMESTAMP'),
    'id' => array('type'=>'INT','primary_key'=>True,'default'=>0,'increment'=>True)
    );

    /**
      Use this instead of primary key for identifying
      records
    */
    protected $unique = array('CardNo','personNum');

    protected $normalize_lanes = true;

    /* START ACCESSOR FUNCTIONS */

    public function CardNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["CardNo"])) {
                return $this->instance["CardNo"];
            } elseif(isset($this->columns["CardNo"]["default"])) {
                return $this->columns["CardNo"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["CardNo"] = func_get_arg(0);
        }
    }

    public function personNum()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["personNum"])) {
                return $this->instance["personNum"];
            } elseif(isset($this->columns["personNum"]["default"])) {
                return $this->columns["personNum"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["personNum"] = func_get_arg(0);
        }
    }

    public function LastName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["LastName"])) {
                return $this->instance["LastName"];
            } elseif(isset($this->columns["LastName"]["default"])) {
                return $this->columns["LastName"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["LastName"] = func_get_arg(0);
        }
    }

    public function FirstName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["FirstName"])) {
                return $this->instance["FirstName"];
            } elseif(isset($this->columns["FirstName"]["default"])) {
                return $this->columns["FirstName"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["FirstName"] = func_get_arg(0);
        }
    }

    public function CashBack()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["CashBack"])) {
                return $this->instance["CashBack"];
            } elseif(isset($this->columns["CashBack"]["default"])) {
                return $this->columns["CashBack"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["CashBack"] = func_get_arg(0);
        }
    }

    public function Balance()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["Balance"])) {
                return $this->instance["Balance"];
            } elseif(isset($this->columns["Balance"]["default"])) {
                return $this->columns["Balance"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["Balance"] = func_get_arg(0);
        }
    }

    public function Discount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["Discount"])) {
                return $this->instance["Discount"];
            } elseif(isset($this->columns["Discount"]["default"])) {
                return $this->columns["Discount"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["Discount"] = func_get_arg(0);
        }
    }

    public function MemDiscountLimit()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["MemDiscountLimit"])) {
                return $this->instance["MemDiscountLimit"];
            } elseif(isset($this->columns["MemDiscountLimit"]["default"])) {
                return $this->columns["MemDiscountLimit"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["MemDiscountLimit"] = func_get_arg(0);
        }
    }

    public function ChargeLimit()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["ChargeLimit"])) {
                return $this->instance["ChargeLimit"];
            } elseif(isset($this->columns["ChargeLimit"]["default"])) {
                return $this->columns["ChargeLimit"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["ChargeLimit"] = func_get_arg(0);
        }
    }

    public function ChargeOk()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["ChargeOk"])) {
                return $this->instance["ChargeOk"];
            } elseif(isset($this->columns["ChargeOk"]["default"])) {
                return $this->columns["ChargeOk"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["ChargeOk"] = func_get_arg(0);
        }
    }

    public function WriteChecks()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["WriteChecks"])) {
                return $this->instance["WriteChecks"];
            } elseif(isset($this->columns["WriteChecks"]["default"])) {
                return $this->columns["WriteChecks"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["WriteChecks"] = func_get_arg(0);
        }
    }

    public function StoreCoupons()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["StoreCoupons"])) {
                return $this->instance["StoreCoupons"];
            } elseif(isset($this->columns["StoreCoupons"]["default"])) {
                return $this->columns["StoreCoupons"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["StoreCoupons"] = func_get_arg(0);
        }
    }

    public function Type()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["Type"])) {
                return $this->instance["Type"];
            } elseif(isset($this->columns["Type"]["default"])) {
                return $this->columns["Type"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["Type"] = func_get_arg(0);
        }
    }

    public function memType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memType"])) {
                return $this->instance["memType"];
            } elseif(isset($this->columns["memType"]["default"])) {
                return $this->columns["memType"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["memType"] = func_get_arg(0);
        }
    }

    public function staff()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["staff"])) {
                return $this->instance["staff"];
            } elseif(isset($this->columns["staff"]["default"])) {
                return $this->columns["staff"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["staff"] = func_get_arg(0);
        }
    }

    public function SSI()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["SSI"])) {
                return $this->instance["SSI"];
            } elseif(isset($this->columns["SSI"]["default"])) {
                return $this->columns["SSI"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["SSI"] = func_get_arg(0);
        }
    }

    public function Purchases()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["Purchases"])) {
                return $this->instance["Purchases"];
            } elseif(isset($this->columns["Purchases"]["default"])) {
                return $this->columns["Purchases"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["Purchases"] = func_get_arg(0);
        }
    }

    public function NumberOfChecks()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["NumberOfChecks"])) {
                return $this->instance["NumberOfChecks"];
            } elseif(isset($this->columns["NumberOfChecks"]["default"])) {
                return $this->columns["NumberOfChecks"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["NumberOfChecks"] = func_get_arg(0);
        }
    }

    public function memCoupons()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memCoupons"])) {
                return $this->instance["memCoupons"];
            } elseif(isset($this->columns["memCoupons"]["default"])) {
                return $this->columns["memCoupons"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["memCoupons"] = func_get_arg(0);
        }
    }

    public function blueLine()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["blueLine"])) {
                return $this->instance["blueLine"];
            } elseif(isset($this->columns["blueLine"]["default"])) {
                return $this->columns["blueLine"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["blueLine"] = func_get_arg(0);
        }
    }

    public function Shown()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["Shown"])) {
                return $this->instance["Shown"];
            } elseif(isset($this->columns["Shown"]["default"])) {
                return $this->columns["Shown"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["Shown"] = func_get_arg(0);
        }
    }

    public function LastChange()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["LastChange"])) {
                return $this->instance["LastChange"];
            } elseif(isset($this->columns["LastChange"]["default"])) {
                return $this->columns["LastChange"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["LastChange"] = func_get_arg(0);
        }
    }

    public function id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["id"])) {
                return $this->instance["id"];
            } elseif(isset($this->columns["id"]["default"])) {
                return $this->columns["id"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["id"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */

    /**
      5Jul13 static stuff is legacy functionality
      that predates the BasicModel class.
      Can be removed when no calls to these functions
      remain in Fannie.
    */
    
    /**
      Update custdata record(s) for an account
      @param $card_no the member number
      @param $fields array of column names and values

      The values for personNum, LastName, and FirstName
      should be arrays. 
    */
    static public function update($card_no,$fields)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $ret = true;

        /** deal with number of names first */
        if (isset($fields['personNum']) && is_array($fields['personNum'])) {

            /** delete secondary members */
            $delQ = "DELETE FROM custdata WHERE personNum > 1 AND CardNo=?";
            $delP = $dbc->prepare_statement($delQ);
            $delR = $dbc->exec_statement($delP,array($card_no));

            /** create records for secondary members
                based on the primary member */
            $insQ = "INSERT INTO custdata (CardNo,personNum,LastName,FirstName,
                CashBack,Balance,Discount,MemDiscountLimit,ChargeOk,
                WriteChecks,StoreCoupons,Type,memType,staff,SSI,Purchases,
                NumberOfChecks,memCoupons,blueLine,Shown)
                SELECT  CardNo,?,?,?,
                CashBack,Balance,Discount,MemDiscountLimit,ChargeOk,
                WriteChecks,StoreCoupons,Type,memType,staff,SSI,Purchases,
                NumberOfChecks,memCoupons,?,Shown
                FROM custdata WHERE personNum=1 AND CardNo=?";
            $insP = $dbc->prepare_statement($insQ);
            for($i=0;$i<count($fields['personNum']);$i++) {
                $pn = $i+1;
                $ln = isset($fields['LastName'][$i])?$fields['LastName'][$i]:'';
                $fn = isset($fields['FirstName'][$i])?$fields['FirstName'][$i]:'';
                if ($pn == 1) {
                    /** update primary member */
                    $upQ = "UPDATE custdata SET LastName=?,FirstName=?,blueLine=?
                        WHERE personNum=1 AND CardNo=?";
                    $upP = $dbc->prepare_statement($upQ);
                    $args = array($ln,$fn,$card_no." ".$ln,$card_no);
                    $upR = $dbc->exec_statement($upP,$args);
                    if ($upR === False) $ret = false;
                } else {
                    /** create/re-create secondary member */
                    $insR = $dbc->exec_statement($insP,
                        array($pn,$ln,$fn,$card_no." ".$ln,$card_no));
                    if ($insR === False) $ret = false;
                }
            }
        }

        /** update all other fields for the account
            The switch statement is to filter out
            bad input */
        $updateQ = "UPDATE custdata SET ";
        $updateArgs = array();
        foreach($fields as $name => $value) {
            switch($name) {
                case 'CashBack':
                case 'Balance':
                case 'Discount':
                case 'MemDiscountLimit':
                case 'ChargeOk':
                case 'WriteChecks':
                case 'StoreCoupons':
                case 'Type':
                case 'memType':
                case 'staff':
                case 'SSI':
                case 'Purchases':
                case 'NumberOfChecks':
                case 'memCoupons':    
                case 'Shown':
                    if ($name === 0 || $name === true) {
                        break; // switch does loose comparison...
                    }
                    $updateQ .= $name." = ?,";
                    $updateArgs[] = $value;
                break;
                default:
                    break;
            }
        }

        /** if only name fields were provided, there's
            nothing to do here */
        if ($updateQ != "UPDATE custdata SET ") {
            $updateQ = rtrim($updateQ,",");
            $updateQ .= " WHERE CardNo=?";
            $updateArgs[] = $card_no;

            $updateP = $dbc->prepare_statement($updateQ);
            $updateR = $dbc->exec_statement($updateP,$updateArgs);
            if ($updateR === false) $ret = false;
        }

        return $ret;
    }

    protected function hookAddColumnChargeLimit()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;

        $sql = 'UPDATE '.$this->connection->identifier_escape($this->name).' SET ChargeLimit=MemDiscountLimit';
        $this->connection->query($sql);

        // revise memChargeBalance view to use new column
        if ($this->currently_normalizing_lane) {
            $viewSQL = 'CREATE VIEW memchargebalance AS
                SELECT 
                c.CardNo AS CardNo,
                c.ChargeLimit - c.Balance AS availBal,  
                c.Balance AS balance
                FROM custdata AS c WHERE personNum = 1';

            $this->connection->query('DROP VIEW memChargeBalance');
            $this->connection->query($viewSQL);

        } else {
            $this->connection->query('USE '.$this->connection->identifier_escape($FANNIE_TRANS_DB)); 

            $viewSQL = 'CREATE VIEW memChargeBalance AS
                SELECT c.CardNo, 
                (CASE WHEN a.balance IS NULL THEN c.ChargeLimit
                    ELSE c.ChargeLimit - a.balance END) AS availBal,
                (CASE WHEN a.balance IS NULL THEN 0 ELSE a.balance END) AS balance,
                CASE WHEN a.mark IS NULL THEN 0 ELSE a.mark END AS mark   
                FROM '.$FANNIE_OP_DB.$this->connection->sep().'custdata AS c 
                LEFT JOIN ar_live_balance AS a ON c.CardNo = a.card_no
                WHERE c.personNum = 1';

            $this->connection->query('DROP VIEW memChargeBalance');
            $this->connection->query($viewSQL);

            $this->connection->query('USE '.$this->connection->identifier_escape($FANNIE_OP_DB)); 
        }
    }
}

