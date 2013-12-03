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
  @class WfcHtEvalScoresModel
*/
class WfcHtEvalScoresModel extends BasicModel
{

    protected $name = "evalScores";

    protected $columns = array(
    'id' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'empID' => array('type'=>'INT'),
    'evalType' => array('type'=>'INT'),
    'evalScore' => array('type'=>'INT'),
    'month' => array('type'=>'SMALLINT'),
    'year' => array('type'=>'SMALLINT'),
    'pos' => array('type'=>'VARCHAR(50)'),
    'score2' => array('type'=>'INT'),
	);

    /* START ACCESSOR FUNCTIONS */

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

    public function empID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["empID"])) {
                return $this->instance["empID"];
            } elseif(isset($this->columns["empID"]["default"])) {
                return $this->columns["empID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["empID"] = func_get_arg(0);
        }
    }

    public function evalType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["evalType"])) {
                return $this->instance["evalType"];
            } elseif(isset($this->columns["evalType"]["default"])) {
                return $this->columns["evalType"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["evalType"] = func_get_arg(0);
        }
    }

    public function evalScore()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["evalScore"])) {
                return $this->instance["evalScore"];
            } elseif(isset($this->columns["evalScore"]["default"])) {
                return $this->columns["evalScore"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["evalScore"] = func_get_arg(0);
        }
    }

    public function month()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["month"])) {
                return $this->instance["month"];
            } elseif(isset($this->columns["month"]["default"])) {
                return $this->columns["month"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["month"] = func_get_arg(0);
        }
    }

    public function year()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["year"])) {
                return $this->instance["year"];
            } elseif(isset($this->columns["year"]["default"])) {
                return $this->columns["year"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["year"] = func_get_arg(0);
        }
    }

    public function pos()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["pos"])) {
                return $this->instance["pos"];
            } elseif(isset($this->columns["pos"]["default"])) {
                return $this->columns["pos"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["pos"] = func_get_arg(0);
        }
    }

    public function score2()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["score2"])) {
                return $this->instance["score2"];
            } elseif(isset($this->columns["score2"]["default"])) {
                return $this->columns["score2"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["score2"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

