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
  @class VendorItemsModel
*/
class VendorItemsModel extends BasicModel 
{

    protected $name = "vendorItems";
    protected $preferred_db = 'op';

    protected $columns = array(
    'vendorItemID' => array('type'=>'INT', 'index'=>true, 'increment'=>true),
    'upc' => array('type'=>'VARCHAR(13)','index'=>true),
    'sku' => array('type'=>'VARCHAR(13)','index'=>true,'primary_key'=>true),
    'brand' => array('type'=>'VARCHAR(50)'),
    'description' => array('type'=>'VARCHAR(50)'),
    'size' => array('type'=>'VARCHAR(25)'),
    'units' => array('type'=>'DOUBLE', 'default'=>1),
    'cost' => array('type'=>'DECIMAL(10,3)'),
    'saleCost' => array('type'=>'DECIMAL(10,3)', 'default'=>0),
    'vendorDept' => array('type'=>'INT', 'default'=>0),
    'vendorID' => array('type'=>'INT','index'=>true,'primary_key'=>true),
    'srp' => array('type'=>'MONEY'),
    'modified' => array('type'=>'datetime', 'ignore_updates'=>true),
    );

    public function doc()
    {
        return '
Depends on:
* vendors (table)
* vendorDepartments (table)

Use:
This table has items from vendors. Cost
and vendor department margin are used to 
calculate SRPs, but the other fields are useful
for making shelf tags.

Size relates to an indivdual product.
Units relates to a case. So a case of beer has 24
units, each with a size of 12 oz.

Cost represents the unit cost. Cost times units 
should then equal the case cost. Sale Cost is
for storing temporary special prices.

upc corresponds to products.upc. Multiple vendorItems
records may map to one products record if an item
is available from more than one vendor or under 
several SKUs from the one vendor. sku should 
uniquely identify an item for the purpose of ordering
it from the vendor. If the vendor does not have SKUs
you have to assign some. The field is wide enough
to hold a UPC; putting your UPC or the vendor\'s UPC
in the SKU field may be a simple solution to assigning
SKUs.
        ';
    }

    /**
      Helper: create a vendorItems record for an existing
      product if one does not exist
    */
    public function createIfMissing($upc, $vendorID)
    {
        $aliasP = $this->connection->prepare("SELECT upc FROM VendorAliases WHERE vendorID=? AND upc=?");
        $aliased = $this->connection->getValue($aliasP, array($vendorID, $upc));
        if ($aliased) {
            return true;
        }
        $findP = $this->connection->prepare('
            SELECT v.upc
            FROM vendorItems AS v
            WHERE v.vendorID=?
                AND v.upc=?');
        $findR = $this->connection->execute($findP, array($vendorID, $upc));
        if ($this->connection->num_rows($findR) == 0) {
            // create item from product
            $prod = new ProductsModel($this->connection);
            $prod->upc($upc);
            $prod->load();
            $vend = new VendorItemsModel($this->connection);
            $vend->vendorID($vendorID);
            $vend->upc($upc);
            $vend->sku($upc);
            $vend->brand($prod->brand());
            $vend->description($prod->description());
            $vend->cost($prod->cost());
            $vend->saleCost(0);
            $vend->vendorDept(0);
            $vend->units(1);
            $vend->size($prod->size() . $prod->unitofmeasure());
            $vend->save();
        }
    }

    /**
      Helper: update vendor costs when updating a product cost
      if the product has a defined vendor
    */
    public function updateCostByUPC($upc, $cost, $vendorID)
    {
        $updateP = $this->connection->prepare('
            UPDATE vendorItems
            SET cost=?,
                modified=' . $this->connection->now() . '
            WHERE vendorID=?
                AND sku=?'); 
        $skuModel = new VendorAliasesModel($this->connection);
        $skuModel->vendorID($vendorID);
        $skuModel->upc($upc);
        foreach ($skuModel->find() as $obj) {
            $this->connection->execute($updateP, array($cost, $vendorID, $obj->sku()));
        }

        $vModel = new VendorItemsModel($this->connection);
        $vModel->vendorID($vendorID);
        $vModel->upc($upc);
        foreach ($vModel->find() as $obj) {
            $this->connection->execute($updateP, array($cost, $vendorID, $obj->sku()));
        }
    }

    public function save()
    {
        if ($this->record_changed) {
            $this->modified(date('Y-m-d H:i:s'));
        }

        return parent::save();
    }
}

