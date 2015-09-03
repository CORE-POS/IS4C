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
  @class ScheduledEmailQueueModel
*/
class ScheduledEmailQueueModel extends BasicModel
{
    protected $name = "ScheduledEmailQueue";
    protected $preferred_db = 'plugin:ScheduledEmailDB';

    protected $columns = array(
    'scheduledEmailQueueID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'scheduledEmailTemplateID' => array('type'=>'INT'),
    'cardNo' => array('type'=>'INT'),
    'sendDate' => array('type'=>'DATETIME'),
    'templateData' => array('type'=>'TEXT'),
    'sent' => array('type'=>'TINYINT', 'default'=>0),
    'sentDate' => array('type'=>'DATETIME'),
    'sentToEmail' => array('type'=>'VARCHAR(100)'),
    );


    /* START ACCESSOR FUNCTIONS */

    public function scheduledEmailQueueID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["scheduledEmailQueueID"])) {
                return $this->instance["scheduledEmailQueueID"];
            } else if (isset($this->columns["scheduledEmailQueueID"]["default"])) {
                return $this->columns["scheduledEmailQueueID"]["default"];
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
                'left' => 'scheduledEmailQueueID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["scheduledEmailQueueID"]) || $this->instance["scheduledEmailQueueID"] != func_get_args(0)) {
                if (!isset($this->columns["scheduledEmailQueueID"]["ignore_updates"]) || $this->columns["scheduledEmailQueueID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["scheduledEmailQueueID"] = func_get_arg(0);
        }
        return $this;
    }

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

    public function sendDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["sendDate"])) {
                return $this->instance["sendDate"];
            } else if (isset($this->columns["sendDate"]["default"])) {
                return $this->columns["sendDate"]["default"];
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
                'left' => 'sendDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["sendDate"]) || $this->instance["sendDate"] != func_get_args(0)) {
                if (!isset($this->columns["sendDate"]["ignore_updates"]) || $this->columns["sendDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["sendDate"] = func_get_arg(0);
        }
        return $this;
    }

    public function templateData()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["templateData"])) {
                return $this->instance["templateData"];
            } else if (isset($this->columns["templateData"]["default"])) {
                return $this->columns["templateData"]["default"];
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
                'left' => 'templateData',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["templateData"]) || $this->instance["templateData"] != func_get_args(0)) {
                if (!isset($this->columns["templateData"]["ignore_updates"]) || $this->columns["templateData"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["templateData"] = func_get_arg(0);
        }
        return $this;
    }

    public function sent()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["sent"])) {
                return $this->instance["sent"];
            } else if (isset($this->columns["sent"]["default"])) {
                return $this->columns["sent"]["default"];
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
                'left' => 'sent',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["sent"]) || $this->instance["sent"] != func_get_args(0)) {
                if (!isset($this->columns["sent"]["ignore_updates"]) || $this->columns["sent"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["sent"] = func_get_arg(0);
        }
        return $this;
    }

    public function sentDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["sentDate"])) {
                return $this->instance["sentDate"];
            } else if (isset($this->columns["sentDate"]["default"])) {
                return $this->columns["sentDate"]["default"];
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
                'left' => 'sentDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["sentDate"]) || $this->instance["sentDate"] != func_get_args(0)) {
                if (!isset($this->columns["sentDate"]["ignore_updates"]) || $this->columns["sentDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["sentDate"] = func_get_arg(0);
        }
        return $this;
    }

    public function sentToEmail()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["sentToEmail"])) {
                return $this->instance["sentToEmail"];
            } else if (isset($this->columns["sentToEmail"]["default"])) {
                return $this->columns["sentToEmail"]["default"];
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
                'left' => 'sentToEmail',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["sentToEmail"]) || $this->instance["sentToEmail"] != func_get_args(0)) {
                if (!isset($this->columns["sentToEmail"]["ignore_updates"]) || $this->columns["sentToEmail"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["sentToEmail"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

