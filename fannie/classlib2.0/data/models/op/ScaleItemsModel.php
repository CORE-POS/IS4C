<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
    'linkedPLU' => array('type'=>'VARCHAR(13)'),
    'mosaStatement' => array('type'=>'TINYINT', 'default'=>0),
    'originText' => array('type'=>'VARCHAR(100)'),
    );

    protected $preferred_db = 'op';

    public function doc()
    {
        return '
Use:
This holds info for deli-scale items. It\'s
formatted to match what the Hobart
DataGateWeigh file wants to see in a CSV
        ';
    }

    public function save()
    {
        if ($this->record_changed) {
            $this->modified(date('Y-m-d H:i:s'));
        }

        return parent::save();
    }

    public function mergeDescription()
    {
        if ($this->itemdesc() != '') {
            return $this->itemdesc();
        } else {
            $p = new ProductsModel($this->connection);
            $p->upc($this->plu());
            if ($p->load()) {
                return $p->description();
            }
        }
        return $this->itemdesc();
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
            $alter = 'ALTER TABLE ' . $this->connection->identifierEscape($this->name) . '
                      CHANGE COLUMN ' . $this->connection->identifierEscape('class') . ' ' .
                      $this->connection->identifierEscape('reportingClass') . ' '  .
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
}

