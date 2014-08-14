<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of IT CORE.

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

class DailyDepositModel extends BasicModel {

    protected $name = 'dailyDeposit';

    protected $columns = array(
    'dateStr' => array('type'=>'VARCHAR(21)','primary_key'=>True),
    'rowName' => array('type'=>'VARCHAR(15)','primary_key'=>True),
    'denomination' => array('type'=>'VARCHAR(6)','primary_key'=>True),
    'amt' => array('type'=>'MONEY','default'=>0)
    );

    /* START ACCESSOR FUNCTIONS */

    public function dateStr(){
        if(func_num_args() == 0){
            if(isset($this->instance["dateStr"]))
                return $this->instance["dateStr"];
            elseif(isset($this->columns["dateStr"]["default"]))
                return $this->columns["dateStr"]["default"];
            else return null;
        }
        else{
            $this->instance["dateStr"] = func_get_arg(0);
        }
    }

    public function rowName(){
        if(func_num_args() == 0){
            if(isset($this->instance["rowName"]))
                return $this->instance["rowName"];
            elseif(isset($this->columns["rowName"]["default"]))
                return $this->columns["rowName"]["default"];
            else return null;
        }
        else{
            $this->instance["rowName"] = func_get_arg(0);
        }
    }

    public function denomination(){
        if(func_num_args() == 0){
            if(isset($this->instance["denomination"]))
                return $this->instance["denomination"];
            elseif(isset($this->columns["denomination"]["default"]))
                return $this->columns["denomination"]["default"];
            else return null;
        }
        else{
            $this->instance["denomination"] = func_get_arg(0);
        }
    }

    public function amt(){
        if(func_num_args() == 0){
            if(isset($this->instance["amt"]))
                return $this->instance["amt"];
            elseif(isset($this->columns["amt"]["default"]))
                return $this->columns["amt"]["default"];
            else return null;
        }
        else{
            $this->instance["amt"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}
