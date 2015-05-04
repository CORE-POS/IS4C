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
  @class ShelfTagQueuesModel
*/
class ShelfTagQueuesModel extends BasicModel
{

    protected $name = "ShelfTagQueues";
    protected $preferred_db = 'op';

    protected $columns = array(
    'shelfTagQueueID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'description' => array('type'=>'VARCHAR(50)'),
    );

    public function initQueues()
    {
        $supers = $this->connection->query('
            SELECT superID,
                super_name
            FROM MasterSuperDepts AS m
            WHERE superID > 0
            GROUP BY superID,
                super_name
        ');
        while ($w = $this->connection->fetchRow($supers)) {
            $this->shelfTagQueueID($w['superID']);
            $this->description($w['super_name']);
            $this->save();
        }
        $this->reset();
    }

    public function toOptions($selected=0)
    {
        $queues = $this->find('shelfTagQueueID');
        if (count($queues) == 0) {
            $this->initQueues();
            $queues = $this->find('shelfTagQueueID');
        }
        $ret = '<option value="0">Default Queue</option>';
        foreach ($queues as $q) {
            $ret .= sprintf('<option %s value="%d">%s</option>',
                ($selected == $q->shelfTagQueueID() ? 'selected' : ''),
                $q->shelfTagQueueID(), $q->description());
        }

        return $ret;
    }


    /* START ACCESSOR FUNCTIONS */

    public function shelfTagQueueID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["shelfTagQueueID"])) {
                return $this->instance["shelfTagQueueID"];
            } else if (isset($this->columns["shelfTagQueueID"]["default"])) {
                return $this->columns["shelfTagQueueID"]["default"];
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
                'left' => 'shelfTagQueueID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["shelfTagQueueID"]) || $this->instance["shelfTagQueueID"] != func_get_args(0)) {
                if (!isset($this->columns["shelfTagQueueID"]["ignore_updates"]) || $this->columns["shelfTagQueueID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["shelfTagQueueID"] = func_get_arg(0);
        }
        return $this;
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
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'description',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["description"]) || $this->instance["description"] != func_get_args(0)) {
                if (!isset($this->columns["description"]["ignore_updates"]) || $this->columns["description"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["description"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

