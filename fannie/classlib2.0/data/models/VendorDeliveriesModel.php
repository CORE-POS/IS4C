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
  @class VendorDeliveriesModel
*/
class VendorDeliveriesModel extends BasicModel
{

    protected $name = "vendorDeliveries";

    protected $columns = array(
    'vendorID' => array('type'=>'INT', 'primary_key'=>true),
    'frequency' => array('type'=>'VARCHAR(10)'),
    'regular' => array('type'=>'TINYINT', 'default'=>1),
    'nextDelivery' => array('type'=>'DATETIME'),
    'nextNextDelivery' => array('type'=>'DATETIME'),
    'sunday' => array('type'=>'TINYINT', 'default'=>0),
    'monday' => array('type'=>'TINYINT', 'default'=>0),
    'tuesday' => array('type'=>'TINYINT', 'default'=>0),
    'wednesday' => array('type'=>'TINYINT', 'default'=>0),
    'thursday' => array('type'=>'TINYINT', 'default'=>0),
    'friday' => array('type'=>'TINYINT', 'default'=>0),
    'saturday' => array('type'=>'TINYINT', 'default'=>0),
    );

    /**
      Calculate next delivery dates
    */
    public function autoNext()
    {
        $now = mktime();
        switch (strtolower($this->frequency())) {
            case 'weekly':
                $next = $now;
                $found = false;
                for ($i=0; $i<7; $i++) {
                    $next = mktime(0, 0, 0, date('n',$next), date('j',$next)+1, date('Y',$next)); 
                    $func = strtolower(date('l', $next));
                    if ($this->$func()) {
                        $this->nextDelivery(date('Y-m-d', $next));
                        $found = true;
                        break;
                    }
                }

                if ($found) {
                    for ($i=0; $i<7; $i++) {
                        $next = mktime(0, 0, 0, date('n',$next), date('j',$next)+1, date('Y',$next)); 
                        $func = strtolower(date('l', $next));
                        if ($this->$func()) {
                            $this->nextNextDelivery(date('Y-m-d', $next));
                            break;
                        }
                    }
                }
                break;
        }
    }

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

    public function frequency()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["frequency"])) {
                return $this->instance["frequency"];
            } else if (isset($this->columns["frequency"]["default"])) {
                return $this->columns["frequency"]["default"];
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
                'left' => 'frequency',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["frequency"]) || $this->instance["frequency"] != func_get_args(0)) {
                if (!isset($this->columns["frequency"]["ignore_updates"]) || $this->columns["frequency"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["frequency"] = func_get_arg(0);
        }
        return $this;
    }

    public function regular()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["regular"])) {
                return $this->instance["regular"];
            } else if (isset($this->columns["regular"]["default"])) {
                return $this->columns["regular"]["default"];
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
                'left' => 'regular',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["regular"]) || $this->instance["regular"] != func_get_args(0)) {
                if (!isset($this->columns["regular"]["ignore_updates"]) || $this->columns["regular"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["regular"] = func_get_arg(0);
        }
        return $this;
    }

    public function nextDelivery()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["nextDelivery"])) {
                return $this->instance["nextDelivery"];
            } else if (isset($this->columns["nextDelivery"]["default"])) {
                return $this->columns["nextDelivery"]["default"];
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
                'left' => 'nextDelivery',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["nextDelivery"]) || $this->instance["nextDelivery"] != func_get_args(0)) {
                if (!isset($this->columns["nextDelivery"]["ignore_updates"]) || $this->columns["nextDelivery"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["nextDelivery"] = func_get_arg(0);
        }
        return $this;
    }

    public function nextNextDelivery()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["nextNextDelivery"])) {
                return $this->instance["nextNextDelivery"];
            } else if (isset($this->columns["nextNextDelivery"]["default"])) {
                return $this->columns["nextNextDelivery"]["default"];
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
                'left' => 'nextNextDelivery',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["nextNextDelivery"]) || $this->instance["nextNextDelivery"] != func_get_args(0)) {
                if (!isset($this->columns["nextNextDelivery"]["ignore_updates"]) || $this->columns["nextNextDelivery"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["nextNextDelivery"] = func_get_arg(0);
        }
        return $this;
    }

    public function sunday()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["sunday"])) {
                return $this->instance["sunday"];
            } else if (isset($this->columns["sunday"]["default"])) {
                return $this->columns["sunday"]["default"];
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
                'left' => 'sunday',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["sunday"]) || $this->instance["sunday"] != func_get_args(0)) {
                if (!isset($this->columns["sunday"]["ignore_updates"]) || $this->columns["sunday"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["sunday"] = func_get_arg(0);
        }
        return $this;
    }

    public function monday()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["monday"])) {
                return $this->instance["monday"];
            } else if (isset($this->columns["monday"]["default"])) {
                return $this->columns["monday"]["default"];
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
                'left' => 'monday',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["monday"]) || $this->instance["monday"] != func_get_args(0)) {
                if (!isset($this->columns["monday"]["ignore_updates"]) || $this->columns["monday"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["monday"] = func_get_arg(0);
        }
        return $this;
    }

    public function tuesday()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tuesday"])) {
                return $this->instance["tuesday"];
            } else if (isset($this->columns["tuesday"]["default"])) {
                return $this->columns["tuesday"]["default"];
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
                'left' => 'tuesday',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["tuesday"]) || $this->instance["tuesday"] != func_get_args(0)) {
                if (!isset($this->columns["tuesday"]["ignore_updates"]) || $this->columns["tuesday"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["tuesday"] = func_get_arg(0);
        }
        return $this;
    }

    public function wednesday()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["wednesday"])) {
                return $this->instance["wednesday"];
            } else if (isset($this->columns["wednesday"]["default"])) {
                return $this->columns["wednesday"]["default"];
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
                'left' => 'wednesday',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["wednesday"]) || $this->instance["wednesday"] != func_get_args(0)) {
                if (!isset($this->columns["wednesday"]["ignore_updates"]) || $this->columns["wednesday"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["wednesday"] = func_get_arg(0);
        }
        return $this;
    }

    public function thursday()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["thursday"])) {
                return $this->instance["thursday"];
            } else if (isset($this->columns["thursday"]["default"])) {
                return $this->columns["thursday"]["default"];
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
                'left' => 'thursday',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["thursday"]) || $this->instance["thursday"] != func_get_args(0)) {
                if (!isset($this->columns["thursday"]["ignore_updates"]) || $this->columns["thursday"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["thursday"] = func_get_arg(0);
        }
        return $this;
    }

    public function friday()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["friday"])) {
                return $this->instance["friday"];
            } else if (isset($this->columns["friday"]["default"])) {
                return $this->columns["friday"]["default"];
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
                'left' => 'friday',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["friday"]) || $this->instance["friday"] != func_get_args(0)) {
                if (!isset($this->columns["friday"]["ignore_updates"]) || $this->columns["friday"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["friday"] = func_get_arg(0);
        }
        return $this;
    }

    public function saturday()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["saturday"])) {
                return $this->instance["saturday"];
            } else if (isset($this->columns["saturday"]["default"])) {
                return $this->columns["saturday"]["default"];
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
                'left' => 'saturday',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["saturday"]) || $this->instance["saturday"] != func_get_args(0)) {
                if (!isset($this->columns["saturday"]["ignore_updates"]) || $this->columns["saturday"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["saturday"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

