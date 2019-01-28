<?php

/*******************************************************************************

    Copyright 2017 Whole Foods Co-op

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
  @class SkuCOOLHistoryModel
*/
class SkuCOOLHistoryModel extends BasicModel
{
    protected $name = "SkuCOOLHistory";
    protected $preferred_db = 'op';

    protected $columns = array(
    'skuCoolHistoryID' => array('type'=>'INT', 'index'=>true, 'increment'=>true),
    'vendorID' => array('type'=>'INT', 'primary_key'=>true),
    'sku' => array('type'=>'VARCHAR(13)', 'primary_key'=>true),
    'ordinal' => array('type'=>'SMALLINT', 'primary_key'=>true),
    'tdate' => array('type'=>'DATETIME'),
    'coolText' => array('type'=>'VARCHAR(255)'),
    );

    /**
     * Add a new entry to history and automatically rotate down the
     * existing history entries
     * @param $vendorID [int]
     * @param $sku [string]
     * @param $text [string]
     * @return [bool] success
     */
    public function rotateIn($vendorID, $sku, $text)
    {
        $delP = $this->connection->prepare("DELETE FROM {$this->name} WHERE vendorID=? AND sku=? AND ordinal=3");
        $twoP = $this->connection->prepare("UPDATE {$this->name} SET ordinal=3 WHERE vendorID=? AND sku=? AND ordinal=2");
        $oneP = $this->connection->prepare("UPDATE {$this->name} SET ordinal=2 WHERE vendorID=? AND sku=? AND ordinal=1");
        $addP = $this->connection->prepare("INSERT INTO {$this->name} (vendorID, sku, ordinal, tdate, coolText) VALUES (?, ?, 1, ?, ?)");
        $this->connection->execute($delP, array($vendorID, $sku));
        $this->connection->execute($twoP, array($vendorID, $sku));
        $this->connection->execute($oneP, array($vendorID, $sku));
        $ret = $this->connection->execute($addP, array($vendorID, $sku, date('Y-m-d H:i:s'), $text));

        return $ret ? true : false;
    }

    public function doc()
    {
        return '
Use:
Track country of origin (COOL) for a given vendor SKU.
            ';
    }
}

