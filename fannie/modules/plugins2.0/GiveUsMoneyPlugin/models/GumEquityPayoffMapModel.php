<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of CORE-POS.

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
  @class GumEquityPayoffMapModel

  This table stores a one-to-one relationship between 
  the GumEquityShares table and the GumPayoffs table
*/
class GumEquityPayoffMapModel extends BasicModel
{

    protected $name = "GumEquityPayoffMap";

    protected $columns = array(
    'gumEquityShareID' => array('type'=>'INT', 'primary_key'=>true),
    'gumPayoffID' => array('type'=>'INT', 'primary_key'=>true),
    );

    /* START ACCESSOR FUNCTIONS */

    public function gumEquityShareID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["gumEquityShareID"])) {
                return $this->instance["gumEquityShareID"];
            } else if (isset($this->columns["gumEquityShareID"]["default"])) {
                return $this->columns["gumEquityShareID"]["default"];
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
                'left' => 'gumEquityShareID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["gumEquityShareID"]) || $this->instance["gumEquityShareID"] != func_get_args(0)) {
                if (!isset($this->columns["gumEquityShareID"]["ignore_updates"]) || $this->columns["gumEquityShareID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["gumEquityShareID"] = func_get_arg(0);
        }
        return $this;
    }

    public function gumPayoffID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["gumPayoffID"])) {
                return $this->instance["gumPayoffID"];
            } else if (isset($this->columns["gumPayoffID"]["default"])) {
                return $this->columns["gumPayoffID"]["default"];
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
                'left' => 'gumPayoffID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["gumPayoffID"]) || $this->instance["gumPayoffID"] != func_get_args(0)) {
                if (!isset($this->columns["gumPayoffID"]["ignore_updates"]) || $this->columns["gumPayoffID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["gumPayoffID"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

