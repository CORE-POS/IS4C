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
  @class PayPeriodsModel
*/
class PayPeriodsModel extends BasicModel
{

    protected $name = "payperiods";

    protected $columns = array(
    'periodID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'periodStart' => array('type'=>'DATETIME'),
    'periodEnd' => array('type'=>'DATETIME'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function periodID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["periodID"])) {
                return $this->instance["periodID"];
            } else if (isset($this->columns["periodID"]["default"])) {
                return $this->columns["periodID"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["periodID"]) || $this->instance["periodID"] != func_get_args(0)) {
                if (!isset($this->columns["periodID"]["ignore_updates"]) || $this->columns["periodID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["periodID"] = func_get_arg(0);
        }
    }

    public function periodStart()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["periodStart"])) {
                return $this->instance["periodStart"];
            } else if (isset($this->columns["periodStart"]["default"])) {
                return $this->columns["periodStart"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["periodStart"]) || $this->instance["periodStart"] != func_get_args(0)) {
                if (!isset($this->columns["periodStart"]["ignore_updates"]) || $this->columns["periodStart"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["periodStart"] = func_get_arg(0);
        }
    }

    public function periodEnd()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["periodEnd"])) {
                return $this->instance["periodEnd"];
            } else if (isset($this->columns["periodEnd"]["default"])) {
                return $this->columns["periodEnd"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["periodEnd"]) || $this->instance["periodEnd"] != func_get_args(0)) {
                if (!isset($this->columns["periodEnd"]["ignore_updates"]) || $this->columns["periodEnd"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["periodEnd"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

