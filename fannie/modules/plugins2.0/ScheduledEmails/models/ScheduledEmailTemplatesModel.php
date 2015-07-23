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
  @class ScheduledEmailTemplatesModel
*/
class ScheduledEmailTemplatesModel extends BasicModel
{

    protected $name = "ScheduledEmailTemplates";
    protected $preferred_db = 'plugin:ScheduledEmailDB';

    protected $columns = array(
    'scheduledEmailTemplateID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'name' => array('type'=>'VARCHAR(100)'),
    'subject' => array('type'=>'VARCHAR(100)'),
    'hasText' => array('type'=>'TINYINT', 'default'=>0),
    'textCopy' => array('type'=>'TEXT'),
    'hasHTML' => array('type'=>'TINYINT', 'default'=>0),
    'htmlCopy' => array('type'=>'TEXT'),
    );


    /* START ACCESSOR FUNCTIONS */

    public function scheduledEmailTemplateID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["scheduledEmailTemplateID"])) {
                return $this->instance["scheduledEmailTemplateID"];
            } else if (isset($this->columns["scheduledEmailTemplateID"]["default"])) {
                return $this->columns["scheduledEmailTemplateID"]["default"];
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
                'left' => 'scheduledEmailTemplateID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["scheduledEmailTemplateID"]) || $this->instance["scheduledEmailTemplateID"] != func_get_args(0)) {
                if (!isset($this->columns["scheduledEmailTemplateID"]["ignore_updates"]) || $this->columns["scheduledEmailTemplateID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["scheduledEmailTemplateID"] = func_get_arg(0);
        }
        return $this;
    }

    public function name()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["name"])) {
                return $this->instance["name"];
            } else if (isset($this->columns["name"]["default"])) {
                return $this->columns["name"]["default"];
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
                'left' => 'name',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["name"]) || $this->instance["name"] != func_get_args(0)) {
                if (!isset($this->columns["name"]["ignore_updates"]) || $this->columns["name"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["name"] = func_get_arg(0);
        }
        return $this;
    }

    public function subject()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["subject"])) {
                return $this->instance["subject"];
            } else if (isset($this->columns["subject"]["default"])) {
                return $this->columns["subject"]["default"];
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
                'left' => 'subject',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["subject"]) || $this->instance["subject"] != func_get_args(0)) {
                if (!isset($this->columns["subject"]["ignore_updates"]) || $this->columns["subject"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["subject"] = func_get_arg(0);
        }
        return $this;
    }

    public function hasText()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["hasText"])) {
                return $this->instance["hasText"];
            } else if (isset($this->columns["hasText"]["default"])) {
                return $this->columns["hasText"]["default"];
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
                'left' => 'hasText',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["hasText"]) || $this->instance["hasText"] != func_get_args(0)) {
                if (!isset($this->columns["hasText"]["ignore_updates"]) || $this->columns["hasText"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["hasText"] = func_get_arg(0);
        }
        return $this;
    }

    public function textCopy()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["textCopy"])) {
                return $this->instance["textCopy"];
            } else if (isset($this->columns["textCopy"]["default"])) {
                return $this->columns["textCopy"]["default"];
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
                'left' => 'textCopy',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["textCopy"]) || $this->instance["textCopy"] != func_get_args(0)) {
                if (!isset($this->columns["textCopy"]["ignore_updates"]) || $this->columns["textCopy"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["textCopy"] = func_get_arg(0);
        }
        return $this;
    }

    public function hasHTML()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["hasHTML"])) {
                return $this->instance["hasHTML"];
            } else if (isset($this->columns["hasHTML"]["default"])) {
                return $this->columns["hasHTML"]["default"];
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
                'left' => 'hasHTML',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["hasHTML"]) || $this->instance["hasHTML"] != func_get_args(0)) {
                if (!isset($this->columns["hasHTML"]["ignore_updates"]) || $this->columns["hasHTML"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["hasHTML"] = func_get_arg(0);
        }
        return $this;
    }

    public function htmlCopy()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["htmlCopy"])) {
                return $this->instance["htmlCopy"];
            } else if (isset($this->columns["htmlCopy"]["default"])) {
                return $this->columns["htmlCopy"]["default"];
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
                'left' => 'htmlCopy',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["htmlCopy"]) || $this->instance["htmlCopy"] != func_get_args(0)) {
                if (!isset($this->columns["htmlCopy"]["ignore_updates"]) || $this->columns["htmlCopy"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["htmlCopy"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

