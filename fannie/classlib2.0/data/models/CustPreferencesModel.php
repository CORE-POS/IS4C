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
  @class CustPreferencesModel
*/
class CustPreferencesModel extends BasicModel
{

    protected $name = "custPreferences";

    protected $columns = array(
    'custPrefenceID' => array('type'=>'INT', 'increment'=>true),
    'card_no' => array('type'=>'INT', 'primary_key'=>true),
    'custAvailablePrefID' => array('type'=>'INT'),
    'pref_key' => array('type'=>'VARCHAR(50)', 'primary_key'=>true),
    'pref_value' => array('type'=>'VARCHAR(100'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function custPrefenceID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["custPrefenceID"])) {
                return $this->instance["custPrefenceID"];
            } else if (isset($this->columns["custPrefenceID"]["default"])) {
                return $this->columns["custPrefenceID"]["default"];
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
                'left' => 'custPrefenceID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["custPrefenceID"]) || $this->instance["custPrefenceID"] != func_get_args(0)) {
                if (!isset($this->columns["custPrefenceID"]["ignore_updates"]) || $this->columns["custPrefenceID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["custPrefenceID"] = func_get_arg(0);
        }
        return $this;
    }

    public function card_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["card_no"])) {
                return $this->instance["card_no"];
            } else if (isset($this->columns["card_no"]["default"])) {
                return $this->columns["card_no"]["default"];
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
                'left' => 'card_no',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["card_no"]) || $this->instance["card_no"] != func_get_args(0)) {
                if (!isset($this->columns["card_no"]["ignore_updates"]) || $this->columns["card_no"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["card_no"] = func_get_arg(0);
        }
        return $this;
    }

    public function custAvailablePrefID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["custAvailablePrefID"])) {
                return $this->instance["custAvailablePrefID"];
            } else if (isset($this->columns["custAvailablePrefID"]["default"])) {
                return $this->columns["custAvailablePrefID"]["default"];
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
                'left' => 'custAvailablePrefID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["custAvailablePrefID"]) || $this->instance["custAvailablePrefID"] != func_get_args(0)) {
                if (!isset($this->columns["custAvailablePrefID"]["ignore_updates"]) || $this->columns["custAvailablePrefID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["custAvailablePrefID"] = func_get_arg(0);
        }
        return $this;
    }

    public function pref_key()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["pref_key"])) {
                return $this->instance["pref_key"];
            } else if (isset($this->columns["pref_key"]["default"])) {
                return $this->columns["pref_key"]["default"];
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
                'left' => 'pref_key',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["pref_key"]) || $this->instance["pref_key"] != func_get_args(0)) {
                if (!isset($this->columns["pref_key"]["ignore_updates"]) || $this->columns["pref_key"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["pref_key"] = func_get_arg(0);
        }
        return $this;
    }

    public function pref_value()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["pref_value"])) {
                return $this->instance["pref_value"];
            } else if (isset($this->columns["pref_value"]["default"])) {
                return $this->columns["pref_value"]["default"];
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
                'left' => 'pref_value',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["pref_value"]) || $this->instance["pref_value"] != func_get_args(0)) {
                if (!isset($this->columns["pref_value"]["ignore_updates"]) || $this->columns["pref_value"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["pref_value"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

