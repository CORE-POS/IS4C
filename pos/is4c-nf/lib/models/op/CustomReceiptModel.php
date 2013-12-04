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

    /* START ACCESSOR FUNCTIONS */

    public function text()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["text"])) {
                return $this->instance["text"];
            } elseif(isset($this->columns["text"]["default"])) {
                return $this->columns["text"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["text"] = func_get_arg(0);
        }
    }

    public function seq()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["seq"])) {
                return $this->instance["seq"];
            } elseif(isset($this->columns["seq"]["default"])) {
                return $this->columns["seq"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["seq"] = func_get_arg(0);
        }
    }

    public function type()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["type"])) {
                return $this->instance["type"];
            } elseif(isset($this->columns["type"]["default"])) {
                return $this->columns["type"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["type"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

