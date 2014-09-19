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
    'memberNoteID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'cardno' => array('type'=>'INT', 'index'=>true),
    'note' => array('type'=>'TEXT'),
    'stamp' => array('type'=>'DATETIME'),
    'username' => array('type'=>'VARCHAR(50)')
    );

    /* START ACCESSOR FUNCTIONS */

    public function memberNoteID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memberNoteID"])) {
                return $this->instance["memberNoteID"];
            } else if (isset($this->columns["memberNoteID"]["default"])) {
                return $this->columns["memberNoteID"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'memberNoteID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["memberNoteID"]) || $this->instance["memberNoteID"] != func_get_args(0)) {
                if (!isset($this->columns["memberNoteID"]["ignore_updates"]) || $this->columns["memberNoteID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["memberNoteID"] = func_get_arg(0);
        }
        return $this;
    }

    public function cardno()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["cardno"])) {
                return $this->instance["cardno"];
            } else if (isset($this->columns["cardno"]["default"])) {
                return $this->columns["cardno"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'cardno',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["cardno"]) || $this->instance["cardno"] != func_get_args(0)) {
                if (!isset($this->columns["cardno"]["ignore_updates"]) || $this->columns["cardno"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["cardno"] = func_get_arg(0);
        }
        return $this;
    }

    public function note()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["note"])) {
                return $this->instance["note"];
            } else if (isset($this->columns["note"]["default"])) {
                return $this->columns["note"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'note',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["note"]) || $this->instance["note"] != func_get_args(0)) {
                if (!isset($this->columns["note"]["ignore_updates"]) || $this->columns["note"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["note"] = func_get_arg(0);
        }
        return $this;
    }

    public function stamp()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["stamp"])) {
                return $this->instance["stamp"];
            } else if (isset($this->columns["stamp"]["default"])) {
                return $this->columns["stamp"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'stamp',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["stamp"]) || $this->instance["stamp"] != func_get_args(0)) {
                if (!isset($this->columns["stamp"]["ignore_updates"]) || $this->columns["stamp"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["stamp"] = func_get_arg(0);
        }
        return $this;
    }

    public function username()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["username"])) {
                return $this->instance["username"];
            } else if (isset($this->columns["username"]["default"])) {
                return $this->columns["username"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'username',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["username"]) || $this->instance["username"] != func_get_args(0)) {
                if (!isset($this->columns["username"]["ignore_updates"]) || $this->columns["username"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["username"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

