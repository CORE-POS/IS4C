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
  @class LikeCodesModel
*/
class LikeCodesModel extends BasicModel
{

    protected $name = "likeCodes";
    protected $preferred_db = 'op';

    protected $columns = array(
    'likeCode' => array('type'=>'INT', 'primary_key'=>true),
    'likeCodeDesc' => array('type'=>'VARCHAR(50)'),
    );

    public function doc()
    {
        return '
Table: likeCodes

Columns:
    likeCode int
    likeCodeDesc varchar

Depends on:
    upcLike (table)

Use:
Like Codes group sets of items that will always
have the same price. It\'s mostly used for produce,
but could be applied to product lines, too
(e.g., all Clif bars)

The actual likeCode => upc mapping is in upcLike
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function likeCode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["likeCode"])) {
                return $this->instance["likeCode"];
            } else if (isset($this->columns["likeCode"]["default"])) {
                return $this->columns["likeCode"]["default"];
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
                'left' => 'likeCode',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["likeCode"]) || $this->instance["likeCode"] != func_get_args(0)) {
                if (!isset($this->columns["likeCode"]["ignore_updates"]) || $this->columns["likeCode"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["likeCode"] = func_get_arg(0);
        }
        return $this;
    }

    public function likeCodeDesc()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["likeCodeDesc"])) {
                return $this->instance["likeCodeDesc"];
            } else if (isset($this->columns["likeCodeDesc"]["default"])) {
                return $this->columns["likeCodeDesc"]["default"];
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
                'left' => 'likeCodeDesc',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["likeCodeDesc"]) || $this->instance["likeCodeDesc"] != func_get_args(0)) {
                if (!isset($this->columns["likeCodeDesc"]["ignore_updates"]) || $this->columns["likeCodeDesc"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["likeCodeDesc"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

