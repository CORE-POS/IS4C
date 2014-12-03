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
  @class CustReceiptMessageModel
*/
class CustReceiptMessageModel extends BasicModel
{

    protected $name = "custReceiptMessage";
    protected $preferred_db = 'op';

    protected $columns = array(
    'card_no' => array('type'=>'INT','index'=>true),
    'msg_text' => array('type'=>'VARCHAR(255)'),
    'modifier_module' => array('type'=>'VARCHAR(50)'),
    );

    public function doc()
    {
        return '
Table: custReceiptMessage

Columns:
    card_no int
    msg_text varchar    
    modifier_module varchar

Depends on:
    custdata (table)

Use:
Create member-specific messages for
receipts.

- card_no is the member number
- msg_text is the message itself
- modifier_module is [optionally] the name
  of a class that should be invoked
  to potentially modify the message.
  An equity message, for example, might
  use a modifier module to check and see
  if payment was made in the current 
  transaction
        ';
    }

    /* START ACCESSOR FUNCTIONS */

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

    public function msg_text()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["msg_text"])) {
                return $this->instance["msg_text"];
            } else if (isset($this->columns["msg_text"]["default"])) {
                return $this->columns["msg_text"]["default"];
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
                'left' => 'msg_text',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["msg_text"]) || $this->instance["msg_text"] != func_get_args(0)) {
                if (!isset($this->columns["msg_text"]["ignore_updates"]) || $this->columns["msg_text"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["msg_text"] = func_get_arg(0);
        }
        return $this;
    }

    public function modifier_module()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["modifier_module"])) {
                return $this->instance["modifier_module"];
            } else if (isset($this->columns["modifier_module"]["default"])) {
                return $this->columns["modifier_module"]["default"];
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
                'left' => 'modifier_module',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["modifier_module"]) || $this->instance["modifier_module"] != func_get_args(0)) {
                if (!isset($this->columns["modifier_module"]["ignore_updates"]) || $this->columns["modifier_module"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["modifier_module"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

