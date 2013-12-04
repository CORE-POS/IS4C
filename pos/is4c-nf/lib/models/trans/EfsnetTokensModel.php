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
  @class EfsnetTokensModel
*/
class EfsnetTokensModel extends BasicModel
{

    protected $name = "efsnetTokens";

    protected $preferred_db = 'trans';

    protected $columns = array(
    'expireDay' => array('type'=>'DATETIME'),
    'refNum' => array('type'=>'VARCHAR(50)', 'primary_key'=>true),
    'token' => array('type'=>'VARCHAR(100)', 'primary_key'=>true),
    'processData' => array('type'=>'VARCHAR(255)'),
    'acqRefData' => array('type'=>'VARCHAR(255)'),
	);

    /* START ACCESSOR FUNCTIONS */

    public function expireDay()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["expireDay"])) {
                return $this->instance["expireDay"];
            } elseif(isset($this->columns["expireDay"]["default"])) {
                return $this->columns["expireDay"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["expireDay"] = func_get_arg(0);
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

    public function token()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["token"])) {
                return $this->instance["token"];
            } elseif(isset($this->columns["token"]["default"])) {
                return $this->columns["token"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["token"] = func_get_arg(0);
        }
    }

    public function processData()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["processData"])) {
                return $this->instance["processData"];
            } elseif(isset($this->columns["processData"]["default"])) {
                return $this->columns["processData"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["processData"] = func_get_arg(0);
        }
    }

    public function acqRefData()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["acqRefData"])) {
                return $this->instance["acqRefData"];
            } elseif(isset($this->columns["acqRefData"]["default"])) {
                return $this->columns["acqRefData"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["acqRefData"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

