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
  @class CustReceiptMessageModel
*/
class CustReceiptMessageModel extends BasicModel
{

    protected $name = "custReceiptMessage";
    protected $preferred_db = 'op';

    protected $columns = array(
    'card_no' => array('type' => 'INT', 'index'=>true),
    'msg_text' => array('type'=>'VARCHAR(255)'),
    'modifier_module' => array('type'=>'VARCHAR(50)'),
	);

    /* START ACCESSOR FUNCTIONS */

    public function card_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["card_no"])) {
                return $this->instance["card_no"];
            } elseif(isset($this->columns["card_no"]["default"])) {
                return $this->columns["card_no"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["card_no"] = func_get_arg(0);
        }
    }

    public function msg_text()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["msg_text"])) {
                return $this->instance["msg_text"];
            } elseif(isset($this->columns["msg_text"]["default"])) {
                return $this->columns["msg_text"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["msg_text"] = func_get_arg(0);
        }
    }

    public function modifier_module()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["modifier_module"])) {
                return $this->instance["modifier_module"];
            } elseif(isset($this->columns["modifier_module"]["default"])) {
                return $this->columns["modifier_module"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["modifier_module"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

