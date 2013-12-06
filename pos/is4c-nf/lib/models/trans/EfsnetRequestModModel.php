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
  @class EfsnetRequestModModel
*/
class EfsnetRequestModModel extends BasicModel
{

    protected $name = "efsnetRequestMod";

    protected $preferred_db = 'trans';

    protected $columns = array(
    'date' => array('type'=>'INT'),
    'cashierNo' => array('type'=>'INT'),
    'laneNo' => array('type'=>'INT'),
    'transNo' => array('type'=>'INT'),
    'transID' => array('type'=>'INT'),
    'datetime' => array('type'=>'DATETIME'),
    'origRefNum' => array('type'=>'VARCHAR(50)'),
    'origAmount' => array('type'=>'MONEY'),
    'origTransactionID' => array('type'=>'VARCHAR(12)'),
    'mode' => array('type'=>'VARCHAR(32)'),
    'altRoute' => array('type'=>'TINYINT'),
    'seconds' => array('type'=>'FLOAT'),
    'commErr' => array('type'=>'INT'),
    'httpCode' => array('type'=>'INT'),
    'validResponse' => array('type'=>'SMALLINT'),
    'xResponseCode' => array('type'=>'VARCHAR(4)'),
    'xResultCode' => array('type'=>'VARCHAR(4)'),
    'xResultMessage' => array('type'=>'VARCHAR(100)'),
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

    public function origRefNum()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["origRefNum"])) {
                return $this->instance["origRefNum"];
            } elseif(isset($this->columns["origRefNum"]["default"])) {
                return $this->columns["origRefNum"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["origRefNum"] = func_get_arg(0);
        }
    }

    public function origAmount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["origAmount"])) {
                return $this->instance["origAmount"];
            } elseif(isset($this->columns["origAmount"]["default"])) {
                return $this->columns["origAmount"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["origAmount"] = func_get_arg(0);
        }
    }

    public function origTransactionID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["origTransactionID"])) {
                return $this->instance["origTransactionID"];
            } elseif(isset($this->columns["origTransactionID"]["default"])) {
                return $this->columns["origTransactionID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["origTransactionID"] = func_get_arg(0);
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

    public function seconds()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["seconds"])) {
                return $this->instance["seconds"];
            } elseif(isset($this->columns["seconds"]["default"])) {
                return $this->columns["seconds"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["seconds"] = func_get_arg(0);
        }
    }

    public function commErr()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["commErr"])) {
                return $this->instance["commErr"];
            } elseif(isset($this->columns["commErr"]["default"])) {
                return $this->columns["commErr"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["commErr"] = func_get_arg(0);
        }
    }

    public function httpCode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["httpCode"])) {
                return $this->instance["httpCode"];
            } elseif(isset($this->columns["httpCode"]["default"])) {
                return $this->columns["httpCode"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["httpCode"] = func_get_arg(0);
        }
    }

    public function validResponse()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["validResponse"])) {
                return $this->instance["validResponse"];
            } elseif(isset($this->columns["validResponse"]["default"])) {
                return $this->columns["validResponse"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["validResponse"] = func_get_arg(0);
        }
    }

    public function xResponseCode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xResponseCode"])) {
                return $this->instance["xResponseCode"];
            } elseif(isset($this->columns["xResponseCode"]["default"])) {
                return $this->columns["xResponseCode"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["xResponseCode"] = func_get_arg(0);
        }
    }

    public function xResultCode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xResultCode"])) {
                return $this->instance["xResultCode"];
            } elseif(isset($this->columns["xResultCode"]["default"])) {
                return $this->columns["xResultCode"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["xResultCode"] = func_get_arg(0);
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
    /* END ACCESSOR FUNCTIONS */
}

