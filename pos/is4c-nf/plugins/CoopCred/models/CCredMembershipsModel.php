<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op
    Copyright 2014 West End Food Co-op, Toronto, Ontario

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
  @class CCredMembershipsModel
*/
class CCredMembershipsModel extends BasicModel
{

    protected $name = "CCredMemberships";

    protected $columns = array(
        // FK to coop_cred.CCredPrograms
        'programID' => array('type'=>'SMALLINT(6)', 'not_null'=>True,
            'default'=>0, 'index'=>True),
        // FK to custdata
        'cardNo' => array('type'=>'INT(11)', 'not_null'=>True, 'default'=>0,
            'index'=>True),
        'creditBalance' => array('type'=>'MONEY', 'not_null'=>True, 'default'=>0),
        // Always 0 in CoopCred; means Member may not be in debt to store.
        'creditLimit' => array('type'=>'MONEY', 'not_null'=>True, 'default'=>0),
        // The most that can be deposited in a Member's account.
        // Is negative, so CCredMemberships.creditBalance must be larger.
        // Defaults to CCredPrograms.maxCreditBalance.
        'maxCreditBalance' => array('type'=>'MONEY', 'not_null'=>True, 'default'=>0),
        'creditOK' => array('type'=>'TINYINT', 'not_null'=>True, 'default'=>0),
        'inputOK' => array('type'=>'TINYINT', 'not_null'=>True, 'default'=>0),
        'transferOK' => array('type'=>'TINYINT', 'not_null'=>True, 'default'=>0),
        'isBank' => array('type'=>'TINYINT', 'not_null'=>True, 'default'=>0),
        'modified' => array('type'=>'DATETIME', 'not_null'=>True,
            'default'=>"'0000-00-00 00:00:00'"),
        'modifiedBy' => array('type'=>'INT(11)', 'not_null'=>True, 'default'=>0),
        'membershipID' => array('type'=>'INT(11)','primary_key'=>True,
            'increment'=>True)
	);

    /* START ACCESSOR FUNCTIONS */

    public function programID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["programID"])) {
                return $this->instance["programID"];
            } else if (isset($this->columns["programID"]["default"])) {
                return $this->columns["programID"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["programID"]) || $this->instance["programID"] != func_get_args(0)) {
                if (!isset($this->columns["programID"]["ignore_updates"]) || $this->columns["programID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["programID"] = func_get_arg(0);
        }
    }

    public function cardNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["cardNo"])) {
                return $this->instance["cardNo"];
            } else if (isset($this->columns["cardNo"]["default"])) {
                return $this->columns["cardNo"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["cardNo"]) || $this->instance["cardNo"] != func_get_args(0)) {
                if (!isset($this->columns["cardNo"]["ignore_updates"]) || $this->columns["cardNo"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["cardNo"] = func_get_arg(0);
        }
    }

    public function creditBalance()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["creditBalance"])) {
                return $this->instance["creditBalance"];
            } else if (isset($this->columns["creditBalance"]["default"])) {
                return $this->columns["creditBalance"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["creditBalance"]) || $this->instance["creditBalance"] != func_get_args(0)) {
                if (!isset($this->columns["creditBalance"]["ignore_updates"]) || $this->columns["creditBalance"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["creditBalance"] = func_get_arg(0);
        }
    }

    public function creditLimit()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["creditLimit"])) {
                return $this->instance["creditLimit"];
            } else if (isset($this->columns["creditLimit"]["default"])) {
                return $this->columns["creditLimit"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["creditLimit"]) || $this->instance["creditLimit"] != func_get_args(0)) {
                if (!isset($this->columns["creditLimit"]["ignore_updates"]) || $this->columns["creditLimit"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["creditLimit"] = func_get_arg(0);
        }
    }

    public function maxCreditBalance()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["maxCreditBalance"])) {
                return $this->instance["maxCreditBalance"];
            } else if (isset($this->columns["maxCreditBalance"]["default"])) {
                return $this->columns["maxCreditBalance"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["maxCreditBalance"]) || $this->instance["maxCreditBalance"] != func_get_args(0)) {
                if (!isset($this->columns["maxCreditBalance"]["ignore_updates"]) || $this->columns["maxCreditBalance"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["maxCreditBalance"] = func_get_arg(0);
        }
    }

    public function creditOK()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["creditOK"])) {
                return $this->instance["creditOK"];
            } else if (isset($this->columns["creditOK"]["default"])) {
                return $this->columns["creditOK"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["creditOK"]) || $this->instance["creditOK"] != func_get_args(0)) {
                if (!isset($this->columns["creditOK"]["ignore_updates"]) || $this->columns["creditOK"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["creditOK"] = func_get_arg(0);
        }
    }

    public function inputOK()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["inputOK"])) {
                return $this->instance["inputOK"];
            } else if (isset($this->columns["inputOK"]["default"])) {
                return $this->columns["inputOK"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["inputOK"]) || $this->instance["inputOK"] != func_get_args(0)) {
                if (!isset($this->columns["inputOK"]["ignore_updates"]) || $this->columns["inputOK"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["inputOK"] = func_get_arg(0);
        }
    }

    public function transferOK()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["transferOK"])) {
                return $this->instance["transferOK"];
            } else if (isset($this->columns["transferOK"]["default"])) {
                return $this->columns["transferOK"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["transferOK"]) || $this->instance["transferOK"] != func_get_args(0)) {
                if (!isset($this->columns["transferOK"]["ignore_updates"]) || $this->columns["transferOK"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["transferOK"] = func_get_arg(0);
        }
    }

    public function isBank()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["isBank"])) {
                return $this->instance["isBank"];
            } else if (isset($this->columns["isBank"]["default"])) {
                return $this->columns["isBank"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["isBank"]) || $this->instance["isBank"] != func_get_args(0)) {
                if (!isset($this->columns["isBank"]["ignore_updates"]) || $this->columns["isBank"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["isBank"] = func_get_arg(0);
        }
    }

    public function modified()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["modified"])) {
                return $this->instance["modified"];
            } else if (isset($this->columns["modified"]["default"])) {
                return $this->columns["modified"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["modified"]) || $this->instance["modified"] != func_get_args(0)) {
                if (!isset($this->columns["modified"]["ignore_updates"]) || $this->columns["modified"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["modified"] = func_get_arg(0);
        }
    }

    public function modifiedBy()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["modifiedBy"])) {
                return $this->instance["modifiedBy"];
            } else if (isset($this->columns["modifiedBy"]["default"])) {
                return $this->columns["modifiedBy"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["modifiedBy"]) || $this->instance["modifiedBy"] != func_get_args(0)) {
                if (!isset($this->columns["modifiedBy"]["ignore_updates"]) || $this->columns["modifiedBy"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["modifiedBy"] = func_get_arg(0);
        }
    }

    public function membershipID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["membershipID"])) {
                return $this->instance["membershipID"];
            } else if (isset($this->columns["membershipID"]["default"])) {
                return $this->columns["membershipID"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["membershipID"]) || $this->instance["membershipID"] != func_get_args(0)) {
                if (!isset($this->columns["membershipID"]["ignore_updates"]) || $this->columns["membershipID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["membershipID"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

