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
            } else if (isset($this->columns["customerNotificationID"]["default"])) {
                return $this->columns["customerNotificationID"]["default"];
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
                'left' => 'customerNotificationID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["customerNotificationID"]) || $this->instance["customerNotificationID"] != func_get_args(0)) {
                if (!isset($this->columns["customerNotificationID"]["ignore_updates"]) || $this->columns["customerNotificationID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["customerNotificationID"] = func_get_arg(0);
        }
        return $this;
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
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'cardNo',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["cardNo"]) || $this->instance["cardNo"] != func_get_args(0)) {
                if (!isset($this->columns["cardNo"]["ignore_updates"]) || $this->columns["cardNo"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["cardNo"] = func_get_arg(0);
        }
        return $this;
    }

    public function customerID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["customerID"])) {
                return $this->instance["customerID"];
            } else if (isset($this->columns["customerID"]["default"])) {
                return $this->columns["customerID"]["default"];
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
                'left' => 'customerID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["customerID"]) || $this->instance["customerID"] != func_get_args(0)) {
                if (!isset($this->columns["customerID"]["ignore_updates"]) || $this->columns["customerID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["customerID"] = func_get_arg(0);
        }
        return $this;
    }

    public function source()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["source"])) {
                return $this->instance["source"];
            } else if (isset($this->columns["source"]["default"])) {
                return $this->columns["source"]["default"];
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
                'left' => 'source',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["source"]) || $this->instance["source"] != func_get_args(0)) {
                if (!isset($this->columns["source"]["ignore_updates"]) || $this->columns["source"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["source"] = func_get_arg(0);
        }
        return $this;
    }

    public function type()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["type"])) {
                return $this->instance["type"];
            } else if (isset($this->columns["type"]["default"])) {
                return $this->columns["type"]["default"];
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
                'left' => 'type',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["type"]) || $this->instance["type"] != func_get_args(0)) {
                if (!isset($this->columns["type"]["ignore_updates"]) || $this->columns["type"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["type"] = func_get_arg(0);
        }
        return $this;
    }

    public function message()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["message"])) {
                return $this->instance["message"];
            } else if (isset($this->columns["message"]["default"])) {
                return $this->columns["message"]["default"];
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
                'left' => 'message',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["message"]) || $this->instance["message"] != func_get_args(0)) {
                if (!isset($this->columns["message"]["ignore_updates"]) || $this->columns["message"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["message"] = func_get_arg(0);
        }
        return $this;
    }

    public function modifierModule()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["modifierModule"])) {
                return $this->instance["modifierModule"];
            } else if (isset($this->columns["modifierModule"]["default"])) {
                return $this->columns["modifierModule"]["default"];
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
                'left' => 'modifierModule',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["modifierModule"]) || $this->instance["modifierModule"] != func_get_args(0)) {
                if (!isset($this->columns["modifierModule"]["ignore_updates"]) || $this->columns["modifierModule"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["modifierModule"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

