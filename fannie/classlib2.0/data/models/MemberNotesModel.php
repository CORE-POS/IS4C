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
  @class MemberNotesModel
*/
class MemberNotesModel extends BasicModel 
{

    protected $name = "memberNotes";

    protected $preferred_db = 'op';

    protected $columns = array(
    'cardno' => array('type'=>'INT'),
    'note' => array('type','TEXT'),
    'stamp' => array('type','DATETIME'),
    'username' => array('type','VARCHAR(50)')
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

    public function note()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["note"])) {
                return $this->instance["note"];
            } elseif(isset($this->columns["note"]["default"])) {
                return $this->columns["note"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["note"] = func_get_arg(0);
        }
    }

    public function stamp()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["stamp"])) {
                return $this->instance["stamp"];
            } elseif(isset($this->columns["stamp"]["default"])) {
                return $this->columns["stamp"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["stamp"] = func_get_arg(0);
        }
    }

    public function username()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["username"])) {
                return $this->instance["username"];
            } elseif(isset($this->columns["username"]["default"])) {
                return $this->columns["username"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["username"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

