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
  @class ShrinkReasonsModel
*/
class ShrinkReasonsModel extends BasicModel
{

    protected $name = "ShrinkReasons";

    protected $preferred_db = 'op';
    protected $normalize_lanes = true;

    protected $columns = array(
    'shrinkReasonID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'description' => array('type'=>'VARCHAR(30)'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function shrinkReasonID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["shrinkReasonID"])) {
                return $this->instance["shrinkReasonID"];
            } else if (isset($this->columns["shrinkReasonID"]["default"])) {
                return $this->columns["shrinkReasonID"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["shrinkReasonID"]) || $this->instance["shrinkReasonID"] != func_get_args(0)) {
                if (!isset($this->columns["shrinkReasonID"]["ignore_updates"]) || $this->columns["shrinkReasonID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["shrinkReasonID"] = func_get_arg(0);
        }
    }

    public function description()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["description"])) {
                return $this->instance["description"];
            } else if (isset($this->columns["description"]["default"])) {
                return $this->columns["description"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["description"]) || $this->instance["description"] != func_get_args(0)) {
                if (!isset($this->columns["description"]["ignore_updates"]) || $this->columns["description"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["description"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

