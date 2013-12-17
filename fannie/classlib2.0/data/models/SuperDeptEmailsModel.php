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
  @class SuperDeptEmailsModel
*/
class SuperDeptEmailsModel extends BasicModel
{

    protected $name = "superDeptEmails";
    protected $preferred_db = 'op';

    protected $columns = array(
    'superID' => array('type'=>'INT', 'primary_key'=>true),
    'email_address' => array('type'=>'VARCHAR(255)'),
	);

    /* START ACCESSOR FUNCTIONS */

    public function superID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["superID"])) {
                return $this->instance["superID"];
            } elseif(isset($this->columns["superID"]["default"])) {
                return $this->columns["superID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["superID"] = func_get_arg(0);
        }
    }

    public function email_address()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["email_address"])) {
                return $this->instance["email_address"];
            } elseif(isset($this->columns["email_address"]["default"])) {
                return $this->columns["email_address"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["email_address"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

