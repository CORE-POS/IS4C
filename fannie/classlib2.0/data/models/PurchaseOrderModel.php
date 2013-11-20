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
  @class PurchaseOrderModel
*/
class PurchaseOrderModel extends BasicModel 
{

    protected $name = "PurchaseOrder";

    protected $columns = array(
    'orderID' => array('type'=>'INT','default'=>0,'increment'=>True,'primary_key'=>True),
    'vendorID' => array('type'=>'INT'),
    'creationDate' => array('type'=>'DATETIME'),
    'placed' => array('type'=>'TINYINT','default'=>0,'index'=>True),
    'placedDate' => array('type'=>'DATETIME'),
    'userID' => array('type'=>'INT'),
    'standingID' => array('type'=>'INT')
    );

    protected $preferred_db = 'op';

    /**
      A really, REALLY old version of this table might exist.
      If so, just delete it and start over with the new schema.
    */
    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=False){
        $dbc = FannieDB::get($db_name);
        $this->connection = $dbc;
        if (!$dbc->table_exists($this->name))
            return parent::normalize($db_name, $mode, $doCreate);
        $def = $dbc->table_definition($this->name);
        if (count($def)==3 && isset($def['stamp']) && isset($def['id']) && isset($def['name'])){
            echo "==========================================\n";
            if ($mode == BasicModel::NORMALIZE_MODE_APPLY){
                $dbc->query('DROP TABLE '.$dbc->identifier_escape($this->name));
                $success = $this->create();    
                echo "Recreating table ".$this->name.": ";
                echo ($success) ? 'Succeeded' : 'Failed';
                echo "\n";
                echo "==========================================\n";
                return $success;
            }
            else {
                echo $this->name." is very old. It needs to be re-created\n";
                echo "Any data in the current table will be lost\n";
                echo "==========================================\n";
                return count($this->columns);
            }
        }
        else
            return parent::normalize($db_name, $mode, $doCreate);
    }

    /* START ACCESSOR FUNCTIONS */

    public function orderID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["orderID"])) {
                return $this->instance["orderID"];
            } elseif(isset($this->columns["orderID"]["default"])) {
                return $this->columns["orderID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["orderID"] = func_get_arg(0);
        }
    }

    public function vendorID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["vendorID"])) {
                return $this->instance["vendorID"];
            } elseif(isset($this->columns["vendorID"]["default"])) {
                return $this->columns["vendorID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["vendorID"] = func_get_arg(0);
        }
    }

    public function creationDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["creationDate"])) {
                return $this->instance["creationDate"];
            } elseif(isset($this->columns["creationDate"]["default"])) {
                return $this->columns["creationDate"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["creationDate"] = func_get_arg(0);
        }
    }

    public function placed()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["placed"])) {
                return $this->instance["placed"];
            } elseif(isset($this->columns["placed"]["default"])) {
                return $this->columns["placed"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["placed"] = func_get_arg(0);
        }
    }

    public function placedDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["placedDate"])) {
                return $this->instance["placedDate"];
            } elseif(isset($this->columns["placedDate"]["default"])) {
                return $this->columns["placedDate"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["placedDate"] = func_get_arg(0);
        }
    }

    public function userID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["userID"])) {
                return $this->instance["userID"];
            } elseif(isset($this->columns["userID"]["default"])) {
                return $this->columns["userID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["userID"] = func_get_arg(0);
        }
    }

    public function standingID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["standingID"])) {
                return $this->instance["standingID"];
            } elseif(isset($this->columns["standingID"]["default"])) {
                return $this->columns["standingID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["standingID"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

