<?php

/*******************************************************************************

    Copyright 2019 Whole Foods Co-op

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
  @class DeliCategoriesModel
*/
class DeliSnapshotsModel extends BasicModel
{
    protected $name = "DeliSnapshots";

    protected $columns = array(
    'deliSnapshotID' => array('type'=>'INT', 'primary_key' => True, 'increment'=>True),
    'snapShotDate' => array('type'=>'DATETIME'),
    'id' => array('type'=>'INT'),
    'item' => array('type'=>'VARCHAR(50)'),
    'orderno' => array('type'=>'VARCHAR(15)'),
    'units' => array('type'=>'VARCHAR(10)'),
    'cases' => array('type'=>'FLOAT'),
    'fraction' => array('type'=>'VARCHAR(10)'),
    'totalstock' => array('type'=>'FLOAT'),
    'price' => array('type'=>'MONEY'),
    'total' => array('type'=>'MONEY'),
    'size' => array('type'=>'VARCHAR(20)'),
    'category' => array('type'=>'VARCHAR(50)', 'index'=>True),
    'upc' => array('type'=>'VARCHAR(13)'),
    'vendorID' => array('type'=>'INT'),
    'storeID' => array('type'=>'INT'),
    'categoryID' => array('type'=>'INT'),
    'seq' => array('type'=>'INT', 'default'=>0),
    'modified' => array('type'=>'DATETIME'),
    'attnFlag' => array('type'=>'TINYINT', 'default'=>0),
    );

}

