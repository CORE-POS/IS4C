<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
  @class PurchaseOrderItemsModel
*/
class PurchaseOrderItemsModel extends BasicModel 
{

    protected $name = "PurchaseOrderItems";

    protected $columns = array(
    'orderID' => array('type'=>'INT','primary_key'=>True),
    'sku' => array('type'=>'VARCHAR(13)','primary_key'=>True),
    'quantity' => array('type'=>'DOUBLE'),
    'unitCost' => array('type'=>'MONEY'),
    'caseSize' => array('type'=>'DOUBLE'),
    'receivedDate' => array('type'=>'DATETIME'),
    'receivedQty' => array('type'=>'DOUBLE'),
    'receivedTotalCost' => array('type'=>'MONEY'),
    'unitSize' => array('type'=>'VARCHAR(25)'),
    'brand' => array('type'=>'VARCHAR(50)'),
    'description' => array('type'=>'VARCHAR(50)'),
    'internalUPC' => array('type'=>'VARCHAR(13)'),
    'salesCode' => array('type'=>'INT'),
    'isSpecialOrder' => array('type'=>'TINYINT', 'default'=>0),
    'receivedBy' => array('type'=>'INT', 'default'=>0),
    );

    protected $preferred_db = 'op';

    public function doc()
    {
        return '
Depends on:
* PurchaseOrder (table)
* vendorItems (table) 

Use:
Contains items to be purchased as part
of an order from a vendor.

quantity is the number of cases ordered.
unitCost corresponds to vendorItems.cost
and caseSize corresponds to vendorItems.units.
The estimated cost of puchase for the line
will be quantity * unitCost * caseSize.

The received fields are for when the items
are actually delivered. receivedQty may not
match quantity and receivedTotalCost may
not match the estimated cost. 

unitSize, brand, description, and internalUPC
are simply copied from vendorItems. If the
vendor discontinues a SKU or switches it to a
different product, this record will still 
        ';
    }

    public function guessCode()
    {
        $dbc = $this->connection;

        // case 1: item exists in products
        $deptP = $dbc->prepare('
            SELECT d.salesCode
            FROM products AS p
                INNER JOIN departments AS d ON p.department=d.dept_no
            WHERE p.upc=?');
        $code = $dbc->getValue($deptP, array($this->internalUPC()));
        if ($code) {
            return $code;
        }

        $order = new PurchaseOrderModel($dbc);
        $order->orderID($this->orderID());
        $order->load();

        // case 2: item is SKU-mapped but the order record
        // does not reflect the internal PLU
        $deptP = $dbc->prepare('
            SELECT d.salesCode
            FROM vendorSKUtoPLU AS v
                ' . DTrans::joinProducts('v', 'p', 'INNER') . '
                INNER JOIN departments AS d ON p.department=d.dept_no
            WHERE v.sku=?
                AND v.vendorID=?');
        $code = $dbc->getValue($deptP, array($this->sku(), $order->vendorID()));
        if ($code) {
            return $code;
        }

        // case 3: item is not normally carried but is in a vendor catalog
        // that has vendor => POS department mapping
        $deptP = $dbc->prepare('
            SELECT d.salesCode
            FROM vendorItems AS v
                INNER JOIN vendorDepartments AS z ON v.vendorDept=z.deptID AND v.vendorID=z.vendorID
                INNER JOIN departments AS d ON z.posDeptID=d.dept_no
            WHERE v.sku=?
                AND v.vendorID=?');
        $code = $dbc->getValue($deptP, array($this->sku(), $order->vendorID()));
        if ($code) {
            return $code;
        }

        return false;
    }

    /**
      A really, REALLY old version of this table might exist.
      If so, just delete it and start over with the new schema.
    */
    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=false)
    {
        $dbc = FannieDB::get($db_name);
        $this->connection = $dbc;
        if (!$dbc->tableExists($this->name)) {
            return parent::normalize($db_name, $mode, $doCreate);
        }
        $def = $dbc->tableDefinition($this->name);
        if (count($def)==4 && isset($def['upc']) && isset($def['vendor_id']) && isset($def['order_id']) && isset($def['quantity'])) {
            echo "==========================================\n";
            if ($mode == BasicModel::NORMALIZE_MODE_APPLY){
                $dbc->query('DROP TABLE '.$dbc->identifierEscape($this->name));
                $success = $this->create();    
                echo "Recreating table ".$this->name.": ";
                echo ($success) ? 'Succeeded' : 'Failed';
                echo "\n";
                echo "==========================================\n";
                return $success;
            } else {
                echo $this->name." is very old. It needs to be re-created\n";
                echo "Any data in the current table will be lost\n";
                echo "==========================================\n";
                return count($this->columns);
            }
        } else {
            return parent::normalize($db_name, $mode, $doCreate);
        }
    }
}

