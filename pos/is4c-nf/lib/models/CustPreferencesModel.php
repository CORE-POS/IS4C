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
  @class CustPreferencesModel
*/
class CustPreferencesModel extends BasicModel
{

    protected $name = "custPreferences";

    protected $preferred_db = 'op';

    protected $columns = array(
    'card_no' => array('type' => 'INT', 'primary_key'=>true),
    'pref_key' => array('type' => 'VARCHAR(50)', 'primary_key'=>true),
    'pref_value' => array('type'=>'VARCHAR(100)'),
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

    public function pref_key()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["pref_key"])) {
                return $this->instance["pref_key"];
            } elseif(isset($this->columns["pref_key"]["default"])) {
                return $this->columns["pref_key"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["pref_key"] = func_get_arg(0);
        }
    }

    public function pref_value()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["pref_value"])) {
                return $this->instance["pref_value"];
            } elseif(isset($this->columns["pref_value"]["default"])) {
                return $this->columns["pref_value"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["pref_value"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

