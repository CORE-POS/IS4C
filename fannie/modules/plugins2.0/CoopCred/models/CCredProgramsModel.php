<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op
    Copyright 2014 West End Food Co-op, Toronto

    This file is part of IT CORE.

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
  @class CCredProgramsModel
*/
class CCredProgramsModel extends BasicModel
{

	// The actual name of the table.
	protected $name = 'CCredPrograms';

	protected $columns = array(
        'programID' => array('type'=>'SMALLINT(6)', 'default'=>0, 'primary_key'=>True,
            'increment'=>True),
        'programName' => array('type'=>'VARCHAR(100)', 'not_null'=>True,
            'default'=>"''"),
        // default is ignored
        'active' => array('type'=>'TINYINT', 'not_null'=>True, 'default'=>0),
        'startDate' => array('type'=>'DATE', 'not_null'=>True,
            'default'=>"'0000-00-00'"),
        'endDate' => array('type'=>'DATE', 'default'=>'NULL'),
        // FK to op.custdata and CoopCred.Members
        'bankID' => array('type'=>'INT(11)', 'not_null'=>True, 'default'=>0),
        // FK to op.departments
        'paymentDepartment' => array('type'=>'INT(11)', 'not_null'=>True, 'default'=>0),
        'tenderType' => array('type'=>'VARCHAR(2)', 'not_null'=>True, 'default'=>"''"),
        'inputTenderType' => array('type'=>'VARCHAR(2)', 'not_null'=>True, 'default'=>"''"),
        'creditOK' => array('type'=>'TINYINT', 'not_null'=>True, 'default'=>0),
        'inputOK' => array('type'=>'TINYINT', 'not_null'=>True, 'default'=>0),
        'transferOK' => array('type'=>'TINYINT', 'not_null'=>True, 'default'=>0),
        // The most that can be deposited in a Member's account.
        // Is negative, so CCredMembers.creditBalance must be larger.
        // This is the default for the Program, can be overridden per-member.
        'maxCreditBalance' => array('type'=>'MONEY', 'not_null'=>True, 'default'=>0),
        'modified' => array('type'=>'DATETIME', 'not_null'=>True,
            'default'=>"'0000-00-00 00:00:00'"),
        'modifiedBy' => array('type'=>'INT(11)', 'not_null'=>True, 'default'=>0),
        // Text for departments.dept_name
        'paymentName' => array('type'=>'VARCHAR(30)', 'not_null'=>True, 'default'=>"''"),
        // Text for payment/input keycap, or very short-form references.
        'paymentKeyCap' => array('type'=>'VARCHAR(25)', 'not_null'=>True, 'default'=>"''"),
        // Text for tenders.TenderName
        'tenderName' => array('type'=>'VARCHAR(25)', 'not_null'=>True, 'default'=>"''"),
        // Text for tender keycap, or very short-form references.
        'tenderKeyCap' => array('type'=>'VARCHAR(25)', 'not_null'=>True, 'default'=>"''")
	);

    public function name()
    {
        return $this->name;
    }

    /* Run the regular save() with the connection switched to each lane.
     * Restore the connection to the server before returning.
     * @return true on success for all lanes, or
     *  messages on failure to load record or
     *  find lane db name or per-lane errors.
     * Accumulate errors about lane connection and lane save() and
     *  log to Fannie and return.
     */
    public function pushToLanesCoopCred()
    {
        global $FANNIE_LANES, $FANNIE_PLUGIN_SETTINGS;

        $errors = array();

        /* Columns for unique-ness must already be assigned.
         * The save() to server must already be done.
         */
        if (!$this->load()) {
            $msg="pTLCC Program load failed";
            $this->connection->logger($msg);
            return $msg;
        }

        if (array_key_exists('CoopCredLaneDatabase', $FANNIE_PLUGIN_SETTINGS) &&
            $FANNIE_PLUGIN_SETTINGS['CoopCredLaneDatabase'] != "") {
            $coopCredLaneDatabase = $FANNIE_PLUGIN_SETTINGS['CoopCredLaneDatabase'];
        } else {
            $msg ="pTLCC Program failed to get lane db name";
            $this->connection->logger($msg);
            return $msg;
        }

        $current = $this->connection;
        // save to each lane
        $laneNumber = 0;
        foreach($FANNIE_LANES as $lane) {
            $laneNumber++;
            $lane['op'] = $coopCredLaneDatabase;
            $sql = new SQLManager($lane['host'],$lane['type'],$lane['op'],
                        $lane['user'],$lane['pw']);    
            if (!is_object($sql) || $sql->connections[$lane['op']] === false) {
                $errors[] = "pTLCC Program connect to lane{$laneNumber} failed.";
                continue;
            }
            $this->connection = $sql;
            /* Update or Insert as appropriate.
             * Return membershipID or False
             */
            if ($this->save() === False) {
                $errors[] = "pTLCC Program save to lane{$laneNumber} failed.";
            }
        }
        /* Restore connection to Fannie. */
        $this->connection = $current;

        if (count($errors)>0) {
            $msg = implode("\n",$errors);
            $this->connection->logger($msg);
            return $msg;
        } else {
            return true;
        }

    // pushToLanesCoopCred
    }


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
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'programID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["programID"]) || $this->instance["programID"] != func_get_args(0)) {
                if (!isset($this->columns["programID"]["ignore_updates"]) || $this->columns["programID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["programID"] = func_get_arg(0);
        }
        return $this;
    }

    public function programName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["programName"])) {
                return $this->instance["programName"];
            } else if (isset($this->columns["programName"]["default"])) {
                return $this->columns["programName"]["default"];
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
                'left' => 'programName',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["programName"]) || $this->instance["programName"] != func_get_args(0)) {
                if (!isset($this->columns["programName"]["ignore_updates"]) || $this->columns["programName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["programName"] = func_get_arg(0);
        }
        return $this;
    }

    public function active()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["active"])) {
                return $this->instance["active"];
            } else if (isset($this->columns["active"]["default"])) {
                return $this->columns["active"]["default"];
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
                'left' => 'active',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["active"]) || $this->instance["active"] != func_get_args(0)) {
                if (!isset($this->columns["active"]["ignore_updates"]) || $this->columns["active"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["active"] = func_get_arg(0);
        }
        return $this;
    }

    public function startDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["startDate"])) {
                return $this->instance["startDate"];
            } else if (isset($this->columns["startDate"]["default"])) {
                return $this->columns["startDate"]["default"];
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
                'left' => 'startDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["startDate"]) || $this->instance["startDate"] != func_get_args(0)) {
                if (!isset($this->columns["startDate"]["ignore_updates"]) || $this->columns["startDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["startDate"] = func_get_arg(0);
        }
        return $this;
    }

    public function endDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["endDate"])) {
                return $this->instance["endDate"];
            } else if (isset($this->columns["endDate"]["default"])) {
                return $this->columns["endDate"]["default"];
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
                'left' => 'endDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["endDate"]) || $this->instance["endDate"] != func_get_args(0)) {
                if (!isset($this->columns["endDate"]["ignore_updates"]) || $this->columns["endDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["endDate"] = func_get_arg(0);
        }
        return $this;
    }

    public function bankID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["bankID"])) {
                return $this->instance["bankID"];
            } else if (isset($this->columns["bankID"]["default"])) {
                return $this->columns["bankID"]["default"];
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
                'left' => 'bankID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["bankID"]) || $this->instance["bankID"] != func_get_args(0)) {
                if (!isset($this->columns["bankID"]["ignore_updates"]) || $this->columns["bankID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["bankID"] = func_get_arg(0);
        }
        return $this;
    }

    public function paymentDepartment()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["paymentDepartment"])) {
                return $this->instance["paymentDepartment"];
            } else if (isset($this->columns["paymentDepartment"]["default"])) {
                return $this->columns["paymentDepartment"]["default"];
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
                'left' => 'paymentDepartment',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["paymentDepartment"]) || $this->instance["paymentDepartment"] != func_get_args(0)) {
                if (!isset($this->columns["paymentDepartment"]["ignore_updates"]) || $this->columns["paymentDepartment"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["paymentDepartment"] = func_get_arg(0);
        }
        return $this;
    }

    public function tenderType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tenderType"])) {
                return $this->instance["tenderType"];
            } else if (isset($this->columns["tenderType"]["default"])) {
                return $this->columns["tenderType"]["default"];
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
                'left' => 'tenderType',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["tenderType"]) || $this->instance["tenderType"] != func_get_args(0)) {
                if (!isset($this->columns["tenderType"]["ignore_updates"]) || $this->columns["tenderType"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["tenderType"] = func_get_arg(0);
        }
        return $this;
    }

    public function inputTenderType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["inputTenderType"])) {
                return $this->instance["inputTenderType"];
            } else if (isset($this->columns["inputTenderType"]["default"])) {
                return $this->columns["inputTenderType"]["default"];
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
                'left' => 'inputTenderType',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["inputTenderType"]) || $this->instance["inputTenderType"] != func_get_args(0)) {
                if (!isset($this->columns["inputTenderType"]["ignore_updates"]) || $this->columns["inputTenderType"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["inputTenderType"] = func_get_arg(0);
        }
        return $this;
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
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'creditOK',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["creditOK"]) || $this->instance["creditOK"] != func_get_args(0)) {
                if (!isset($this->columns["creditOK"]["ignore_updates"]) || $this->columns["creditOK"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["creditOK"] = func_get_arg(0);
        }
        return $this;
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
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'inputOK',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["inputOK"]) || $this->instance["inputOK"] != func_get_args(0)) {
                if (!isset($this->columns["inputOK"]["ignore_updates"]) || $this->columns["inputOK"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["inputOK"] = func_get_arg(0);
        }
        return $this;
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
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'transferOK',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["transferOK"]) || $this->instance["transferOK"] != func_get_args(0)) {
                if (!isset($this->columns["transferOK"]["ignore_updates"]) || $this->columns["transferOK"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["transferOK"] = func_get_arg(0);
        }
        return $this;
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
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'maxCreditBalance',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["maxCreditBalance"]) || $this->instance["maxCreditBalance"] != func_get_args(0)) {
                if (!isset($this->columns["maxCreditBalance"]["ignore_updates"]) || $this->columns["maxCreditBalance"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["maxCreditBalance"] = func_get_arg(0);
        }
        return $this;
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
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'modified',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["modified"]) || $this->instance["modified"] != func_get_args(0)) {
                if (!isset($this->columns["modified"]["ignore_updates"]) || $this->columns["modified"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["modified"] = func_get_arg(0);
        }
        return $this;
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
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'modifiedBy',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["modifiedBy"]) || $this->instance["modifiedBy"] != func_get_args(0)) {
                if (!isset($this->columns["modifiedBy"]["ignore_updates"]) || $this->columns["modifiedBy"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["modifiedBy"] = func_get_arg(0);
        }
        return $this;
    }

    public function paymentName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["paymentName"])) {
                return $this->instance["paymentName"];
            } else if (isset($this->columns["paymentName"]["default"])) {
                return $this->columns["paymentName"]["default"];
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
                'left' => 'paymentName',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["paymentName"]) || $this->instance["paymentName"] != func_get_args(0)) {
                if (!isset($this->columns["paymentName"]["ignore_updates"]) || $this->columns["paymentName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["paymentName"] = func_get_arg(0);
        }
        return $this;
    }

    public function paymentKeyCap()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["paymentKeyCap"])) {
                return $this->instance["paymentKeyCap"];
            } else if (isset($this->columns["paymentKeyCap"]["default"])) {
                return $this->columns["paymentKeyCap"]["default"];
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
                'left' => 'paymentKeyCap',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["paymentKeyCap"]) || $this->instance["paymentKeyCap"] != func_get_args(0)) {
                if (!isset($this->columns["paymentKeyCap"]["ignore_updates"]) || $this->columns["paymentKeyCap"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["paymentKeyCap"] = func_get_arg(0);
        }
        return $this;
    }

    public function tenderName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tenderName"])) {
                return $this->instance["tenderName"];
            } else if (isset($this->columns["tenderName"]["default"])) {
                return $this->columns["tenderName"]["default"];
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
                'left' => 'tenderName',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["tenderName"]) || $this->instance["tenderName"] != func_get_args(0)) {
                if (!isset($this->columns["tenderName"]["ignore_updates"]) || $this->columns["tenderName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["tenderName"] = func_get_arg(0);
        }
        return $this;
    }

    public function tenderKeyCap()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tenderKeyCap"])) {
                return $this->instance["tenderKeyCap"];
            } else if (isset($this->columns["tenderKeyCap"]["default"])) {
                return $this->columns["tenderKeyCap"]["default"];
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
                'left' => 'tenderKeyCap',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["tenderKeyCap"]) || $this->instance["tenderKeyCap"] != func_get_args(0)) {
                if (!isset($this->columns["tenderKeyCap"]["ignore_updates"]) || $this->columns["tenderKeyCap"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["tenderKeyCap"] = func_get_arg(0);
        }
        return $this;
    }
	/* END ACCESSOR FUNCTIONS */

// class CCredPrograms
}
