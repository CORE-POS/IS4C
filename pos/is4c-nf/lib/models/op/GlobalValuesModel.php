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
  @class GlobalValuesModel
*/
class GlobalValuesModel extends BasicModel
{

    protected $name = "globalvalues";
    protected $preferred_db = 'op';

    protected $columns = array(
    'CashierNo' => array('type'=>'INT'),
    'Cashier' => array('type'=>'VARCHAR(30)'),
    'LoggedIn' => array('type'=>'TINYINT'),
    'TransNo' => array('type'=>'INT'),
    'TTLFlag' => array('type'=>'TINYINT'),
    'FntlFlag' => array('type'=>'TINYINT'),
    'TaxExempt' => array('type'=>'TINYINT'),
	);

    /* START ACCESSOR FUNCTIONS */

    public function CashierNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["CashierNo"])) {
                return $this->instance["CashierNo"];
            } elseif(isset($this->columns["CashierNo"]["default"])) {
                return $this->columns["CashierNo"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["CashierNo"] = func_get_arg(0);
        }
    }

    public function Cashier()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["Cashier"])) {
                return $this->instance["Cashier"];
            } elseif(isset($this->columns["Cashier"]["default"])) {
                return $this->columns["Cashier"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["Cashier"] = func_get_arg(0);
        }
    }

    public function LoggedIn()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["LoggedIn"])) {
                return $this->instance["LoggedIn"];
            } elseif(isset($this->columns["LoggedIn"]["default"])) {
                return $this->columns["LoggedIn"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["LoggedIn"] = func_get_arg(0);
        }
    }

    public function TransNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["TransNo"])) {
                return $this->instance["TransNo"];
            } elseif(isset($this->columns["TransNo"]["default"])) {
                return $this->columns["TransNo"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["TransNo"] = func_get_arg(0);
        }
    }

    public function TTLFlag()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["TTLFlag"])) {
                return $this->instance["TTLFlag"];
            } elseif(isset($this->columns["TTLFlag"]["default"])) {
                return $this->columns["TTLFlag"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["TTLFlag"] = func_get_arg(0);
        }
    }

    public function FntlFlag()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["FntlFlag"])) {
                return $this->instance["FntlFlag"];
            } elseif(isset($this->columns["FntlFlag"]["default"])) {
                return $this->columns["FntlFlag"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["FntlFlag"] = func_get_arg(0);
        }
    }

    public function TaxExempt()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["TaxExempt"])) {
                return $this->instance["TaxExempt"];
            } elseif(isset($this->columns["TaxExempt"]["default"])) {
                return $this->columns["TaxExempt"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["TaxExempt"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

