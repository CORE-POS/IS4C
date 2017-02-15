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
  @class PurchaseOrderModel
*/
class PurchaseOrderModel extends BasicModel 
{

    protected $name = "PurchaseOrder";

    protected $columns = array(
    'orderID' => array('type'=>'INT','default'=>0,'increment'=>True,'primary_key'=>True),
    'vendorID' => array('type'=>'INT'),
    'storeID' => array('type'=>'INT'),
    'creationDate' => array('type'=>'DATETIME'),
    'placed' => array('type'=>'TINYINT','default'=>0,'index'=>True),
    'placedDate' => array('type'=>'DATETIME'),
    'userID' => array('type'=>'INT'),
    'vendorOrderID' => array('type'=>'VARCHAR(25)'),
    'vendorInvoiceID' => array('type'=>'VARCHAR(25)'),
    'standingID' => array('type'=>'INT')
    );

    protected $preferred_db = 'op';

    public function doc()
    {
        return '
Use:
Stores general an order from a vendor.
One or more records in purchaseOrderItems
should go with this record to list the
individual items to order.

vendorOrderID and vendorInvoiceID are memo
fields. If the vendor puts numbers or other
identifiers on orders and/or invoices those
values can be saved here for reference.
        ';
    }

    private function getCodingStatements($dbc)
    {
        $config = FannieConfig::factory();
        $soP1 = $dbc->prepare('
            SELECT d.salesCode
            FROM ' . $config->get('TRANS_DB') . $dbc->sep() . 'CompleteSpecialOrder AS o
                INNER JOIN departments AS d ON o.department=d.dept_no
            WHERE o.upc=?');
        $soP2 = $dbc->prepare('
            SELECT d.salesCode
            FROM ' . $config->get('TRANS_DB') . $dbc->sep() . 'PendingSpecialOrder AS o
                INNER JOIN departments AS d ON o.department=d.dept_no
            WHERE o.upc=?');
        $vdP = $dbc->prepare('
            SELECT d.salesCode
            FROM vendorItems AS v
                INNER JOIN vendorDepartments AS p ON v.vendorDept = p.deptID
                INNER JOIN departments AS d ON p.deptID=d.dept_no
            WHERE v.sku=?
                AND v.vendorID=?');

        return array($soP1, $soP2, $vdP);
    }

    public function guessAccounts()
    {
        $dbc = $this->connection; 
        $detailP = $dbc->prepare('
            SELECT o.sku,
                o.internalUPC,
                o.receivedTotalCost
            FROM PurchaseOrderItems AS o
            WHERE o.internalUPC NOT IN (
                SELECT upc FROM products
            )
                AND o.orderID=?
                AND o.receivedTotalCost <> 0');
        $detailR = $dbc->execute($detailP, array($this->orderID()));

        list($soP1, $soP2, $vdP) = $this->getCodingStatements($dbc);
        $coding = array('n/a' => 0.00);
        while ($row = $dbc->fetchRow($detailR)) {
            list($coding, $matched) = $this->checkSpecialOrder($dbc, array($soP1, array($row['internalUPC'])), $row, $coding);
            if ($matched === true) {
                continue;
            }

            list($coding, $matched) = $this->checkSpecialOrder($dbc, array($soP2, array($row['internalUPC'])), $row, $coding);
            if ($matched === true) {
                continue;
            }

            list($coding, $matched) = $this->checkSpecialOrder($dbc, array($vdP, array($row['sku'], $this->vendorID())), $row, $coding);
            if ($matched === true) {
                continue;
            }

            $coding['n/a'] += $row['receivedTotalCost'];
        }

        return $coding;
    }

    private function checkSpecialOrder($dbc, $stmt, $row, $coding)
    {
        $soR = $dbc->execute($stmt[0], $stmt[1]);
        if ($dbc->numRows($soR) > 0) {
            $soW = $dbc->fetchRow($soR);
            if (!isset($coding[$soW['salesCode']])) {
                $coding[$soW['salesCode']] = 0.00;
            }
            $coding[$soW['salesCode']] += $row['receivedTotalCost'];
            return array($coding, true);
        } else {
            return array($coding, false);
        }
    }

    /**
      A really, REALLY old version of this table might exist.
      If so, just delete it and start over with the new schema.
    */
    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=False){
        $dbc = FannieDB::get($db_name);
        $this->connection = $dbc;
        if (!$dbc->table_exists($this->name))
            return parent::normalize($db_name, $mode, $doCreate);
        $def = $dbc->tableDefinition($this->name);
        if (count($def)==3 && isset($def['stamp']) && isset($def['id']) && isset($def['name'])){
            echo "==========================================\n";
            if ($mode == BasicModel::NORMALIZE_MODE_APPLY){
                $dbc->query('DROP TABLE '.$dbc->identifierEscape($this->name));
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
}

