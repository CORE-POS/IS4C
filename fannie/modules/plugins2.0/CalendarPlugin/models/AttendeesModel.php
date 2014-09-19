<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
  @class AttendeesModel
*/
class AttendeesModel extends BasicModel
{

    protected $name = "attendees";

    protected $columns = array(
    'attendeeID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'uid' => array('type'=>'INT'),
    'eventID' => array('type'=>'INT'),
    );

    protected $unique = array('uid', 'eventID');

    /* START ACCESSOR FUNCTIONS */

    public function attendeeID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["attendeeID"])) {
                return $this->instance["attendeeID"];
            } else if (isset($this->columns["attendeeID"]["default"])) {
                return $this->columns["attendeeID"]["default"];
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
                'left' => 'attendeeID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["attendeeID"]) || $this->instance["attendeeID"] != func_get_args(0)) {
                if (!isset($this->columns["attendeeID"]["ignore_updates"]) || $this->columns["attendeeID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["attendeeID"] = func_get_arg(0);
        }
        return $this;
    }

    public function uid()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["uid"])) {
                return $this->instance["uid"];
            } else if (isset($this->columns["uid"]["default"])) {
                return $this->columns["uid"]["default"];
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
                'left' => 'uid',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["uid"]) || $this->instance["uid"] != func_get_args(0)) {
                if (!isset($this->columns["uid"]["ignore_updates"]) || $this->columns["uid"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["uid"] = func_get_arg(0);
        }
        return $this;
    }

    public function eventID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["eventID"])) {
                return $this->instance["eventID"];
            } else if (isset($this->columns["eventID"]["default"])) {
                return $this->columns["eventID"]["default"];
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
                'left' => 'eventID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["eventID"]) || $this->instance["eventID"] != func_get_args(0)) {
                if (!isset($this->columns["eventID"]["ignore_updates"]) || $this->columns["eventID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["eventID"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

