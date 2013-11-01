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
  @class PatronageModel
*/
class PatronageModel extends BasicModel 
{

    protected $name = "patronage";

    protected $preferred_db = 'op';

    protected $columns = array(
    'cardno' => array('type'=>'INT','primary_key'=>True,'default'=>0),
    'purchase' => array('type'=>'MONEY'),
    'discounts' => array('type'=>'MONEY'),
    'rewards' => array('type'=>'MONEY'),
    'net_purch' => array('type'=>'MONEY'),
    'tot_pat' => array('type'=>'MONEY'),
    'cash_pat' => array('type'=>'MONEY'),
    'equit_pat' => array('type'=>'MONEY'),
    'FY' => array('type'=>'SMALLINT','primary_key'=>True,'default'=>0)
    );

    /* START ACCESSOR FUNCTIONS */

    public function cardno()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["cardno"])) {
                return $this->instance["cardno"];
            } elseif(isset($this->columns["cardno"]["default"])) {
                return $this->columns["cardno"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["cardno"] = func_get_arg(0);
        }
    }

    public function purchase()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["purchase"])) {
                return $this->instance["purchase"];
            } elseif(isset($this->columns["purchase"]["default"])) {
                return $this->columns["purchase"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["purchase"] = func_get_arg(0);
        }
    }

    public function discounts()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discounts"])) {
                return $this->instance["discounts"];
            } elseif(isset($this->columns["discounts"]["default"])) {
                return $this->columns["discounts"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["discounts"] = func_get_arg(0);
        }
    }

    public function rewards()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["rewards"])) {
                return $this->instance["rewards"];
            } elseif(isset($this->columns["rewards"]["default"])) {
                return $this->columns["rewards"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["rewards"] = func_get_arg(0);
        }
    }

    public function net_purch()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["net_purch"])) {
                return $this->instance["net_purch"];
            } elseif(isset($this->columns["net_purch"]["default"])) {
                return $this->columns["net_purch"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["net_purch"] = func_get_arg(0);
        }
    }

    public function tot_pat()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tot_pat"])) {
                return $this->instance["tot_pat"];
            } elseif(isset($this->columns["tot_pat"]["default"])) {
                return $this->columns["tot_pat"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["tot_pat"] = func_get_arg(0);
        }
    }

    public function cash_pat()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["cash_pat"])) {
                return $this->instance["cash_pat"];
            } elseif(isset($this->columns["cash_pat"]["default"])) {
                return $this->columns["cash_pat"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["cash_pat"] = func_get_arg(0);
        }
    }

    public function equit_pat()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["equit_pat"])) {
                return $this->instance["equit_pat"];
            } elseif(isset($this->columns["equit_pat"]["default"])) {
                return $this->columns["equit_pat"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["equit_pat"] = func_get_arg(0);
        }
    }

    public function FY()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["FY"])) {
                return $this->instance["FY"];
            } elseif(isset($this->columns["FY"]["default"])) {
                return $this->columns["FY"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["FY"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

