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
        } else {
            if (!isset($this->instance["date"]) || $this->instance["date"] != func_get_args(0)) {
                if (!isset($this->columns["date"]["ignore_updates"]) || $this->columns["date"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["date"] = func_get_arg(0);
        }
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
        } else {
            if (!isset($this->instance["cashierNo"]) || $this->instance["cashierNo"] != func_get_args(0)) {
                if (!isset($this->columns["cashierNo"]["ignore_updates"]) || $this->columns["cashierNo"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["cashierNo"] = func_get_arg(0);
        }
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
        } else {
            if (!isset($this->instance["laneNo"]) || $this->instance["laneNo"] != func_get_args(0)) {
                if (!isset($this->columns["laneNo"]["ignore_updates"]) || $this->columns["laneNo"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["laneNo"] = func_get_arg(0);
        }
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
        } else {
            if (!isset($this->instance["transNo"]) || $this->instance["transNo"] != func_get_args(0)) {
                if (!isset($this->columns["transNo"]["ignore_updates"]) || $this->columns["transNo"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["transNo"] = func_get_arg(0);
        }
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
        } else {
            if (!isset($this->instance["transID"]) || $this->instance["transID"] != func_get_args(0)) {
                if (!isset($this->columns["transID"]["ignore_updates"]) || $this->columns["transID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["transID"] = func_get_arg(0);
        }
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
        } else {
            if (!isset($this->instance["datetime"]) || $this->instance["datetime"] != func_get_args(0)) {
                if (!isset($this->columns["datetime"]["ignore_updates"]) || $this->columns["datetime"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["datetime"] = func_get_arg(0);
        }
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
        } else {
            if (!isset($this->instance["refNum"]) || $this->instance["refNum"] != func_get_args(0)) {
                if (!isset($this->columns["refNum"]["ignore_updates"]) || $this->columns["refNum"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["refNum"] = func_get_arg(0);
        }
    }

    public function live()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["live"])) {
                return $this->instance["live"];
            } else if (isset($this->columns["live"]["default"])) {
                return $this->columns["live"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["live"]) || $this->instance["live"] != func_get_args(0)) {
                if (!isset($this->columns["live"]["ignore_updates"]) || $this->columns["live"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["live"] = func_get_arg(0);
        }
    }

    public function mode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["mode"])) {
                return $this->instance["mode"];
            } else if (isset($this->columns["mode"]["default"])) {
                return $this->columns["mode"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["mode"]) || $this->instance["mode"] != func_get_args(0)) {
                if (!isset($this->columns["mode"]["ignore_updates"]) || $this->columns["mode"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["mode"] = func_get_arg(0);
        }
    }

    public function amount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["amount"])) {
                return $this->instance["amount"];
            } else if (isset($this->columns["amount"]["default"])) {
                return $this->columns["amount"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["amount"]) || $this->instance["amount"] != func_get_args(0)) {
                if (!isset($this->columns["amount"]["ignore_updates"]) || $this->columns["amount"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["amount"] = func_get_arg(0);
        }
    }

    public function PAN()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["PAN"])) {
                return $this->instance["PAN"];
            } else if (isset($this->columns["PAN"]["default"])) {
                return $this->columns["PAN"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["PAN"]) || $this->instance["PAN"] != func_get_args(0)) {
                if (!isset($this->columns["PAN"]["ignore_updates"]) || $this->columns["PAN"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["PAN"] = func_get_arg(0);
        }
    }

    public function issuer()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["issuer"])) {
                return $this->instance["issuer"];
            } else if (isset($this->columns["issuer"]["default"])) {
                return $this->columns["issuer"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["issuer"]) || $this->instance["issuer"] != func_get_args(0)) {
                if (!isset($this->columns["issuer"]["ignore_updates"]) || $this->columns["issuer"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["issuer"] = func_get_arg(0);
        }
    }

    public function name()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["name"])) {
                return $this->instance["name"];
            } else if (isset($this->columns["name"]["default"])) {
                return $this->columns["name"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["name"]) || $this->instance["name"] != func_get_args(0)) {
                if (!isset($this->columns["name"]["ignore_updates"]) || $this->columns["name"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["name"] = func_get_arg(0);
        }
    }

    public function manual()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["manual"])) {
                return $this->instance["manual"];
            } else if (isset($this->columns["manual"]["default"])) {
                return $this->columns["manual"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["manual"]) || $this->instance["manual"] != func_get_args(0)) {
                if (!isset($this->columns["manual"]["ignore_updates"]) || $this->columns["manual"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["manual"] = func_get_arg(0);
        }
    }

    public function sentPAN()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["sentPAN"])) {
                return $this->instance["sentPAN"];
            } else if (isset($this->columns["sentPAN"]["default"])) {
                return $this->columns["sentPAN"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["sentPAN"]) || $this->instance["sentPAN"] != func_get_args(0)) {
                if (!isset($this->columns["sentPAN"]["ignore_updates"]) || $this->columns["sentPAN"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["sentPAN"] = func_get_arg(0);
        }
    }

    public function sentExp()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["sentExp"])) {
                return $this->instance["sentExp"];
            } else if (isset($this->columns["sentExp"]["default"])) {
                return $this->columns["sentExp"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["sentExp"]) || $this->instance["sentExp"] != func_get_args(0)) {
                if (!isset($this->columns["sentExp"]["ignore_updates"]) || $this->columns["sentExp"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["sentExp"] = func_get_arg(0);
        }
    }

    public function sentTr1()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["sentTr1"])) {
                return $this->instance["sentTr1"];
            } else if (isset($this->columns["sentTr1"]["default"])) {
                return $this->columns["sentTr1"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["sentTr1"]) || $this->instance["sentTr1"] != func_get_args(0)) {
                if (!isset($this->columns["sentTr1"]["ignore_updates"]) || $this->columns["sentTr1"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["sentTr1"] = func_get_arg(0);
        }
    }

    public function sentTr2()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["sentTr2"])) {
                return $this->instance["sentTr2"];
            } else if (isset($this->columns["sentTr2"]["default"])) {
                return $this->columns["sentTr2"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["sentTr2"]) || $this->instance["sentTr2"] != func_get_args(0)) {
                if (!isset($this->columns["sentTr2"]["ignore_updates"]) || $this->columns["sentTr2"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["sentTr2"] = func_get_arg(0);
        }
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
        } else {
            if (!isset($this->instance["efsnetRequestID"]) || $this->instance["efsnetRequestID"] != func_get_args(0)) {
                if (!isset($this->columns["efsnetRequestID"]["ignore_updates"]) || $this->columns["efsnetRequestID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["efsnetRequestID"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

