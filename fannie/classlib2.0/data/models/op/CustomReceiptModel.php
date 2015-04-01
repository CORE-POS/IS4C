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
  @class CustomReceiptModel
*/
class CustomReceiptModel extends BasicModel
{

    protected $name = "customReceipt";
    protected $preferred_db = 'op';

    protected $columns = array(
        'text' => array('type'=>'VARCHAR(80)'),
        'seq' => array('type'=>'INT', 'primary_key'=>true),
        'type' => array('type'=>'VARCHAR(20)', 'primary_key'=>true),
    );

    public function doc()
    {
        return '
Table: customReceipt

Columns:
    text varchar
    seq int
    type varchar

Depends on:
    none

Use:
This table contains strings of text
that originally lived in the lane\'s 
ini.php. At first it was only used
for receipt headers and footers, hence
the name. Submit a patch if you want
a saner name.

Current valid types are:
receiptHeader
receiptFooter
ckEndorse
welcomeMsg
farewellMsg
trainingMsg
chargeSlip
        ';
    }


    /* START ACCESSOR FUNCTIONS */

    public function text()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["text"])) {
                return $this->instance["text"];
            } else if (isset($this->columns["text"]["default"])) {
                return $this->columns["text"]["default"];
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
                'left' => 'text',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["text"]) || $this->instance["text"] != func_get_args(0)) {
                if (!isset($this->columns["text"]["ignore_updates"]) || $this->columns["text"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["text"] = func_get_arg(0);
        }
        return $this;
    }

    public function seq()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["seq"])) {
                return $this->instance["seq"];
            } else if (isset($this->columns["seq"]["default"])) {
                return $this->columns["seq"]["default"];
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
                'left' => 'seq',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["seq"]) || $this->instance["seq"] != func_get_args(0)) {
                if (!isset($this->columns["seq"]["ignore_updates"]) || $this->columns["seq"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["seq"] = func_get_arg(0);
        }
        return $this;
    }

    public function type()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["type"])) {
                return $this->instance["type"];
            } else if (isset($this->columns["type"]["default"])) {
                return $this->columns["type"]["default"];
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
                'left' => 'type',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["type"]) || $this->instance["type"] != func_get_args(0)) {
                if (!isset($this->columns["type"]["ignore_updates"]) || $this->columns["type"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["type"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

