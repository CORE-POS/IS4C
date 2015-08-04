<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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
  @class CustomerNotificationsModel
*/
class CustomerNotificationsModel extends BasicModel
{

    protected $name = "CustomerNotifications";
    
    protected $preferred_db = 'op';

    protected $columns = array(
    'customerNotificationID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'cardNo' => array('type'=>'INT'),
    'customerID' => array('type'=>'INT', 'default'=>0),
    'source' => array('type'=>'VARCHAR(50)'),
    'type' => array('type'=>'VARCHAR(50)'),
    'message' => array('type'=>'VARCHAR(255)'),
    'modifierModule' => array('type'=>'VARCHAR(50)'),
    );

    public function doc()
    {
        return '
Use:
Display account specific or customer specific
messages in various ways at the lane.';
    }


    /* START ACCESSOR FUNCTIONS */

    public function customerNotificationID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["customerNotificationID"])) {
                return $this->instance["customerNotificationID"];
            } elseif(isset($this->columns["customerNotificationID"]["default"])) {
                return $this->columns["customerNotificationID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["customerNotificationID"] = func_get_arg(0);
        }
    }

    public function cardNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["cardNo"])) {
                return $this->instance["cardNo"];
            } elseif(isset($this->columns["cardNo"]["default"])) {
                return $this->columns["cardNo"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["cardNo"] = func_get_arg(0);
        }
    }

    public function customerID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["customerID"])) {
                return $this->instance["customerID"];
            } elseif(isset($this->columns["customerID"]["default"])) {
                return $this->columns["customerID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["customerID"] = func_get_arg(0);
        }
    }

    public function source()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["source"])) {
                return $this->instance["source"];
            } elseif(isset($this->columns["source"]["default"])) {
                return $this->columns["source"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["source"] = func_get_arg(0);
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

    public function message()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["message"])) {
                return $this->instance["message"];
            } elseif(isset($this->columns["message"]["default"])) {
                return $this->columns["message"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["message"] = func_get_arg(0);
        }
    }

    public function modifierModule()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["modifierModule"])) {
                return $this->instance["modifierModule"];
            } elseif(isset($this->columns["modifierModule"]["default"])) {
                return $this->columns["modifierModule"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["modifierModule"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

