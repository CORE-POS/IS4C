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

class OverShortsLogModel extends BasicModel {

    protected $name = 'overshortsLog';

    protected $columns = array(
    'date' => array('type'=>'VARCHAR(10)','primary_key'=>True),
    'username' => array('type'=>'VARCHAR(25)'),
    'resolved' => array('type'=>'TINYINT','default'=>0)
    );

    /* START ACCESSOR FUNCTIONS */

    public function date(){
        if(func_num_args() == 0){
            if(isset($this->instance["date"]))
                return $this->instance["date"];
            elseif(isset($this->columns["date"]["default"]))
                return $this->columns["date"]["default"];
            else return null;
        }
        else{
            $this->instance["date"] = func_get_arg(0);
        }
    }

    public function username(){
        if(func_num_args() == 0){
            if(isset($this->instance["username"]))
                return $this->instance["username"];
            elseif(isset($this->columns["username"]["default"]))
                return $this->columns["username"]["default"];
            else return null;
        }
        else{
            $this->instance["username"] = func_get_arg(0);
        }
    }

    public function resolved(){
        if(func_num_args() == 0){
            if(isset($this->instance["resolved"]))
                return $this->instance["resolved"];
            elseif(isset($this->columns["resolved"]["default"]))
                return $this->columns["resolved"]["default"];
            else return null;
        }
        else{
            $this->instance["resolved"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}
