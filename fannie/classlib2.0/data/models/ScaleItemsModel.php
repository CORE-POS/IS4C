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
  @class ScaleItemsModel
*/
class ScaleItemsModel extends BasicModel
{

    protected $name = "scaleItems";

    protected $columns = array(
    'plu' => array('type'=>'VARCHAR(13)', 'primary_key'=>true),
    'price' => array('type'=>'MONEY'),
    'itemdesc' => array('type'=>'VARCHAR(100)'),
    'exceptionprice' => array('type'=>'MONEY'),
    'weight' => array('type'=>'TINYINT', 'default'=>0),
    'bycount' => array('type'=>'TINYINT', 'default'=>0),
    'tare' => array('type'=>'FLOAT', 'default'=>0),
    'shelflife' => array('type'=>'SMALLINT', 'default'=>0),
    'netWeight' => array('type'=>'SMALLINT', 'default'=>0),
    'text' => array('type'=>'TEXT'),
    'reportingClass' => array('type'=>'VARCHAR(6)'),
    'label' => array('type'=>'INT'),
    'graphics' => array('type'=>'INT'),
    'modified' => array('type'=>'DATETIME', 'ignore_updates'=>true),
    );

    protected $preferred_db = 'op';

    public function save()
    {
        if ($this->record_changed) {
            $this->modified(date('Y-m-d H:i:s'));
        }

        return parent::save();
    }

    /**
      Custom normalization:
      The original version of scaleItems contained a column named "class".
      "class" is not a valid PHP function name, so the model is unable
      to have a method corresponding to the column.

      This will rename the legacy "class" column to "reportingClass" if
      needed. Otherwise, it just calls BasicModel::normalize().
    */
    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=False)
    {
        $this->connection = FannieDB::get($db_name);
        if (!$this->connection->table_exists($this->name)) {
            return parent::normalize($db_name, $mode, $doCreate);
        }

        $current_definition = $this->connection->tableDefinition($this->name);
        if (isset($current_definition['class']) && !isset($current_definition['reportingClass'])) {
            $alter = 'ALTER TABLE ' . $this->connection->identifier_escape($this->name) . '
                      CHANGE COLUMN ' . $this->connection->identifier_escape('class') . ' ' .
                      $this->connection->identifier_escape('reportingClass') . ' '  .
                      $this->getMeta($this->columns['reportingClass']['type']);

            printf("%s column class as reportingClass\n", 
                    ($mode==BasicModel::NORMALIZE_MODE_CHECK)?"Need to rename":"Renaming"
            );
            printf("\tSQL Details: %s\n", $alter);
            if ($mode == BasicModel::NORMALIZE_MODE_APPLY) {
                $renamed = $this->connection->query($alter);
                return 0;
            } else {
                return 1;
            }
        } else {
            return parent::normalize($db_name, $mode, $doCreate);
        }
    }

    /* START ACCESSOR FUNCTIONS */

    public function plu()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["plu"])) {
                return $this->instance["plu"];
            } else if (isset($this->columns["plu"]["default"])) {
                return $this->columns["plu"]["default"];
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
                'left' => 'plu',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["plu"]) || $this->instance["plu"] != func_get_args(0)) {
                if (!isset($this->columns["plu"]["ignore_updates"]) || $this->columns["plu"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["plu"] = func_get_arg(0);
        }
        return $this;
    }

    public function price()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["price"])) {
                return $this->instance["price"];
            } else if (isset($this->columns["price"]["default"])) {
                return $this->columns["price"]["default"];
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
                'left' => 'price',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["price"]) || $this->instance["price"] != func_get_args(0)) {
                if (!isset($this->columns["price"]["ignore_updates"]) || $this->columns["price"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["price"] = func_get_arg(0);
        }
        return $this;
    }

    public function itemdesc()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["itemdesc"])) {
                return $this->instance["itemdesc"];
            } else if (isset($this->columns["itemdesc"]["default"])) {
                return $this->columns["itemdesc"]["default"];
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
                'left' => 'itemdesc',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["itemdesc"]) || $this->instance["itemdesc"] != func_get_args(0)) {
                if (!isset($this->columns["itemdesc"]["ignore_updates"]) || $this->columns["itemdesc"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["itemdesc"] = func_get_arg(0);
        }
        return $this;
    }

    public function exceptionprice()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["exceptionprice"])) {
                return $this->instance["exceptionprice"];
            } else if (isset($this->columns["exceptionprice"]["default"])) {
                return $this->columns["exceptionprice"]["default"];
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
                'left' => 'exceptionprice',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["exceptionprice"]) || $this->instance["exceptionprice"] != func_get_args(0)) {
                if (!isset($this->columns["exceptionprice"]["ignore_updates"]) || $this->columns["exceptionprice"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["exceptionprice"] = func_get_arg(0);
        }
        return $this;
    }

    public function weight()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["weight"])) {
                return $this->instance["weight"];
            } else if (isset($this->columns["weight"]["default"])) {
                return $this->columns["weight"]["default"];
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
                'left' => 'weight',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["weight"]) || $this->instance["weight"] != func_get_args(0)) {
                if (!isset($this->columns["weight"]["ignore_updates"]) || $this->columns["weight"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["weight"] = func_get_arg(0);
        }
        return $this;
    }

    public function bycount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["bycount"])) {
                return $this->instance["bycount"];
            } else if (isset($this->columns["bycount"]["default"])) {
                return $this->columns["bycount"]["default"];
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
                'left' => 'bycount',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["bycount"]) || $this->instance["bycount"] != func_get_args(0)) {
                if (!isset($this->columns["bycount"]["ignore_updates"]) || $this->columns["bycount"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["bycount"] = func_get_arg(0);
        }
        return $this;
    }

    public function tare()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tare"])) {
                return $this->instance["tare"];
            } else if (isset($this->columns["tare"]["default"])) {
                return $this->columns["tare"]["default"];
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
                'left' => 'tare',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["tare"]) || $this->instance["tare"] != func_get_args(0)) {
                if (!isset($this->columns["tare"]["ignore_updates"]) || $this->columns["tare"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["tare"] = func_get_arg(0);
        }
        return $this;
    }

    public function shelflife()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["shelflife"])) {
                return $this->instance["shelflife"];
            } else if (isset($this->columns["shelflife"]["default"])) {
                return $this->columns["shelflife"]["default"];
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
                'left' => 'shelflife',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["shelflife"]) || $this->instance["shelflife"] != func_get_args(0)) {
                if (!isset($this->columns["shelflife"]["ignore_updates"]) || $this->columns["shelflife"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["shelflife"] = func_get_arg(0);
        }
        return $this;
    }

    public function netWeight()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["netWeight"])) {
                return $this->instance["netWeight"];
            } else if (isset($this->columns["netWeight"]["default"])) {
                return $this->columns["netWeight"]["default"];
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
                'left' => 'netWeight',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["netWeight"]) || $this->instance["netWeight"] != func_get_args(0)) {
                if (!isset($this->columns["netWeight"]["ignore_updates"]) || $this->columns["netWeight"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["netWeight"] = func_get_arg(0);
        }
        return $this;
    }

    public function text()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["text"])) {
                return $this->instance["text"];
            } else if (isset($this->columns["text"]["default"])) {
                return $this->columns["text"]["default"];
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
                'left' => 'text',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["text"]) || $this->instance["text"] != func_get_args(0)) {
                if (!isset($this->columns["text"]["ignore_updates"]) || $this->columns["text"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["text"] = func_get_arg(0);
        }
        return $this;
    }

    public function reportingClass()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["reportingClass"])) {
                return $this->instance["reportingClass"];
            } else if (isset($this->columns["reportingClass"]["default"])) {
                return $this->columns["reportingClass"]["default"];
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
                'left' => 'reportingClass',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["reportingClass"]) || $this->instance["reportingClass"] != func_get_args(0)) {
                if (!isset($this->columns["reportingClass"]["ignore_updates"]) || $this->columns["reportingClass"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["reportingClass"] = func_get_arg(0);
        }
        return $this;
    }

    public function label()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["label"])) {
                return $this->instance["label"];
            } else if (isset($this->columns["label"]["default"])) {
                return $this->columns["label"]["default"];
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
                'left' => 'label',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["label"]) || $this->instance["label"] != func_get_args(0)) {
                if (!isset($this->columns["label"]["ignore_updates"]) || $this->columns["label"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["label"] = func_get_arg(0);
        }
        return $this;
    }

    public function graphics()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["graphics"])) {
                return $this->instance["graphics"];
            } else if (isset($this->columns["graphics"]["default"])) {
                return $this->columns["graphics"]["default"];
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
                'left' => 'graphics',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["graphics"]) || $this->instance["graphics"] != func_get_args(0)) {
                if (!isset($this->columns["graphics"]["ignore_updates"]) || $this->columns["graphics"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["graphics"] = func_get_arg(0);
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
    /* END ACCESSOR FUNCTIONS */
}

