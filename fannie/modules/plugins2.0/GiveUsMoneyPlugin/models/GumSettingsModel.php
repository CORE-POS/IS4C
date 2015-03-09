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
  @class GumSettingsModel

  Catch-all key-value storage for settings.
  This plugin may wind up with a lot of settings
  and keeping them separate from Fannie's
  general settings may be a bit easier.
*/
class GumSettingsModel extends BasicModel
{

    protected $name = "GumSettings";

    protected $columns = array(
    'gumSettingID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'key' => array('type'=>'VARCHAR(50)', 'index'=>true),
    'value' => array('type'=>'VARCHAR(50)'),
    );

    protected $unique = array('key');

    /* START ACCESSOR FUNCTIONS */

    public function gumSettingID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["gumSettingID"])) {
                return $this->instance["gumSettingID"];
            } else if (isset($this->columns["gumSettingID"]["default"])) {
                return $this->columns["gumSettingID"]["default"];
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
                'left' => 'gumSettingID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["gumSettingID"]) || $this->instance["gumSettingID"] != func_get_args(0)) {
                if (!isset($this->columns["gumSettingID"]["ignore_updates"]) || $this->columns["gumSettingID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["gumSettingID"] = func_get_arg(0);
        }
        return $this;
    }

    public function key()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["key"])) {
                return $this->instance["key"];
            } else if (isset($this->columns["key"]["default"])) {
                return $this->columns["key"]["default"];
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
                'left' => 'key',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["key"]) || $this->instance["key"] != func_get_args(0)) {
                if (!isset($this->columns["key"]["ignore_updates"]) || $this->columns["key"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["key"] = func_get_arg(0);
        }
        return $this;
    }

    public function value()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["value"])) {
                return $this->instance["value"];
            } else if (isset($this->columns["value"]["default"])) {
                return $this->columns["value"]["default"];
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
                'left' => 'value',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["value"]) || $this->instance["value"] != func_get_args(0)) {
                if (!isset($this->columns["value"]["ignore_updates"]) || $this->columns["value"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["value"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

