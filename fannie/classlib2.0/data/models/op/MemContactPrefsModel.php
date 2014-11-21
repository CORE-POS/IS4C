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
  @class MemContactPrefsModel
*/
class MemContactPrefsModel extends BasicModel
{

    protected $name = "memContactPrefs";
    protected $preferred_db = 'op';

    protected $columns = array(
    'pref_id' => array('type'=>'INT', 'primary_key'=>true),
    'pref_description' => array('type'=>'VARCHAR(50)'),
    );

    public function doc()
    {
        return '
Table: memContactPrefs

Columns:
    pref_id int
    pref_description varchar

Depends on:
    none

Use:
List of available member contact preferences
Describes values in memContact.pref
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function pref_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["pref_id"])) {
                return $this->instance["pref_id"];
            } else if (isset($this->columns["pref_id"]["default"])) {
                return $this->columns["pref_id"]["default"];
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
                'left' => 'pref_id',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["pref_id"]) || $this->instance["pref_id"] != func_get_args(0)) {
                if (!isset($this->columns["pref_id"]["ignore_updates"]) || $this->columns["pref_id"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["pref_id"] = func_get_arg(0);
        }
        return $this;
    }

    public function pref_description()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["pref_description"])) {
                return $this->instance["pref_description"];
            } else if (isset($this->columns["pref_description"]["default"])) {
                return $this->columns["pref_description"]["default"];
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
                'left' => 'pref_description',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["pref_description"]) || $this->instance["pref_description"] != func_get_args(0)) {
                if (!isset($this->columns["pref_description"]["ignore_updates"]) || $this->columns["pref_description"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["pref_description"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

