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
  @class EfsnetRequestModel
*/
class EfsnetRequestModel extends BasicModel
{

    protected $name = "efsnetRequest";

    protected $preferred_db = 'trans';

    protected $columns = array(
    'date' => array('type'=>'INT'),
    'cashierNo' => array('type'=>'INT'),
    'laneNo' => array('type'=>'INT'),
    'transNo' => array('type'=>'INT'),
    'transID' => array('type'=>'INT'),
    'datetime' => array('type'=>'DATETIME'),
    'refNum' => array('type'=>'VARCHAR(50)'),
    'live' => array('type'=>'TINYINT'),
    'mode' => array('type'=>'VARCHAR(32)'),
    'amount' => array('type'=>'MONEY'),
    'PAN' => array('type'=>'VARCHAR(19)'),
    'issuer' => array('type'=>'VARCHAR(16)'),
    'name' => array('type'=>'VARCHAR(50)'),
    'manual' => array('type'=>'TINYINT'),
    'sentPAN' => array('type'=>'TINYINT'),
    'sentExp' => array('type'=>'TINYINT'),
    'sentTr1' => array('type'=>'TINYINT'),
    'sentTr2' => array('type'=>'TINYINT'),
	);

    /* START ACCESSOR FUNCTIONS */

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

    public function refNum()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["refNum"])) {
                return $this->instance["refNum"];
            } elseif(isset($this->columns["refNum"]["default"])) {
                return $this->columns["refNum"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["refNum"] = func_get_arg(0);
        }
    }

    public function live()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["live"])) {
                return $this->instance["live"];
            } elseif(isset($this->columns["live"]["default"])) {
                return $this->columns["live"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["live"] = func_get_arg(0);
        }
    }

    public function mode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["mode"])) {
                return $this->instance["mode"];
            } elseif(isset($this->columns["mode"]["default"])) {
                return $this->columns["mode"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["mode"] = func_get_arg(0);
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

    public function manual()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["manual"])) {
                return $this->instance["manual"];
            } elseif(isset($this->columns["manual"]["default"])) {
                return $this->columns["manual"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["manual"] = func_get_arg(0);
        }
    }

    public function sentPAN()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["sentPAN"])) {
                return $this->instance["sentPAN"];
            } elseif(isset($this->columns["sentPAN"]["default"])) {
                return $this->columns["sentPAN"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["sentPAN"] = func_get_arg(0);
        }
    }

    public function sentExp()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["sentExp"])) {
                return $this->instance["sentExp"];
            } elseif(isset($this->columns["sentExp"]["default"])) {
                return $this->columns["sentExp"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["sentExp"] = func_get_arg(0);
        }
    }

    public function sentTr1()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["sentTr1"])) {
                return $this->instance["sentTr1"];
            } elseif(isset($this->columns["sentTr1"]["default"])) {
                return $this->columns["sentTr1"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["sentTr1"] = func_get_arg(0);
        }
    }

    public function sentTr2()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["sentTr2"])) {
                return $this->instance["sentTr2"];
            } elseif(isset($this->columns["sentTr2"]["default"])) {
                return $this->columns["sentTr2"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["sentTr2"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

