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
  @class ValutecResponseModel
*/
class ValutecResponseModel extends BasicModel
{

    protected $name = "valutecResponse";

    protected $preferred_db = 'trans';

    protected $columns = array(
    'date' => array('type'=>'INT'),
    'cashierNo' => array('type'=>'INT'),
    'laneNo' => array('type'=>'INT'),
    'transNo' => array('type'=>'INT'),
    'transID' => array('type'=>'INT'),
    'datetime' => array('type'=>'DATETIME'),
    'identifier' => array('type'=>'VARCHAR(10)'),
    'seconds' => array('type'=>'FLOAT'),
    'commErr' => array('type'=>'INT'),
    'httpCode' => array('type'=>'INT'),
    'validResponse' => array('type'=>'SMALLINT'),
    'xAuthorized' => array('type'=>'VARCHAR(5)'),
    'xAuthorizationCode' => array('type'=>'VARCHAR(9)'),
    'xBalance' => array('type'=>'VARCHAR(8)'),
    'xErrorMsg' => array('type'=>'VARCHAR(100)'),
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

    public function identifier()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["identifier"])) {
                return $this->instance["identifier"];
            } elseif(isset($this->columns["identifier"]["default"])) {
                return $this->columns["identifier"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["identifier"] = func_get_arg(0);
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

    public function xAuthorized()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xAuthorized"])) {
                return $this->instance["xAuthorized"];
            } elseif(isset($this->columns["xAuthorized"]["default"])) {
                return $this->columns["xAuthorized"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["xAuthorized"] = func_get_arg(0);
        }
    }

    public function xAuthorizationCode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xAuthorizationCode"])) {
                return $this->instance["xAuthorizationCode"];
            } elseif(isset($this->columns["xAuthorizationCode"]["default"])) {
                return $this->columns["xAuthorizationCode"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["xAuthorizationCode"] = func_get_arg(0);
        }
    }

    public function xBalance()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xBalance"])) {
                return $this->instance["xBalance"];
            } elseif(isset($this->columns["xBalance"]["default"])) {
                return $this->columns["xBalance"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["xBalance"] = func_get_arg(0);
        }
    }

    public function xErrorMsg()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["xErrorMsg"])) {
                return $this->instance["xErrorMsg"];
            } elseif(isset($this->columns["xErrorMsg"]["default"])) {
                return $this->columns["xErrorMsg"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["xErrorMsg"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

