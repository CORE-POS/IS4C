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
  @class VendorContactModel
*/
class VendorContactModel extends BasicModel 
{

    protected $name = "vendorContact";

    protected $columns = array(
    'vendorID' => array('type'=>'INT','primary_key'=>True),
    'phone' => array('type'=>'VARCHAR(15)'),
    'fax' => array('type'=>'VARCHAR(15)'),
    'email' => array('type'=>'VARCHAR(50)'),
    'website' => array('type'=>'VARCHAR(100)'),
    'notes' => array('type'=>'TEXT')
    );

    /* START ACCESSOR FUNCTIONS */

    public function vendorID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["vendorID"])) {
                return $this->instance["vendorID"];
            } else if (isset($this->columns["vendorID"]["default"])) {
                return $this->columns["vendorID"]["default"];
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
                'left' => 'vendorID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["vendorID"]) || $this->instance["vendorID"] != func_get_args(0)) {
                if (!isset($this->columns["vendorID"]["ignore_updates"]) || $this->columns["vendorID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["vendorID"] = func_get_arg(0);
        }
        return $this;
    }

    public function phone()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["phone"])) {
                return $this->instance["phone"];
            } else if (isset($this->columns["phone"]["default"])) {
                return $this->columns["phone"]["default"];
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
                'left' => 'phone',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["phone"]) || $this->instance["phone"] != func_get_args(0)) {
                if (!isset($this->columns["phone"]["ignore_updates"]) || $this->columns["phone"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["phone"] = func_get_arg(0);
        }
        return $this;
    }

    public function fax()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["fax"])) {
                return $this->instance["fax"];
            } else if (isset($this->columns["fax"]["default"])) {
                return $this->columns["fax"]["default"];
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
                'left' => 'fax',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["fax"]) || $this->instance["fax"] != func_get_args(0)) {
                if (!isset($this->columns["fax"]["ignore_updates"]) || $this->columns["fax"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["fax"] = func_get_arg(0);
        }
        return $this;
    }

    public function email()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["email"])) {
                return $this->instance["email"];
            } else if (isset($this->columns["email"]["default"])) {
                return $this->columns["email"]["default"];
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
                'left' => 'email',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["email"]) || $this->instance["email"] != func_get_args(0)) {
                if (!isset($this->columns["email"]["ignore_updates"]) || $this->columns["email"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["email"] = func_get_arg(0);
        }
        return $this;
    }

    public function website()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["website"])) {
                return $this->instance["website"];
            } else if (isset($this->columns["website"]["default"])) {
                return $this->columns["website"]["default"];
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
                'left' => 'website',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["website"]) || $this->instance["website"] != func_get_args(0)) {
                if (!isset($this->columns["website"]["ignore_updates"]) || $this->columns["website"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["website"] = func_get_arg(0);
        }
        return $this;
    }

    public function notes()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["notes"])) {
                return $this->instance["notes"];
            } else if (isset($this->columns["notes"]["default"])) {
                return $this->columns["notes"]["default"];
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
                'left' => 'notes',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["notes"]) || $this->instance["notes"] != func_get_args(0)) {
                if (!isset($this->columns["notes"]["ignore_updates"]) || $this->columns["notes"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["notes"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

