<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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
  @class ProdCostHistoryModel
*/
class ProdCostHistoryModel extends BasicModel
{

    protected $name = "ProdCostHistory";
    protected $preferred_db = 'op';

    protected $columns = array(
    'prodCostHistoryID' => array('type'=>'BIGINT UNSIGNED', 'primary_key'=>true, 'increment'=>true),
    'upc' => array('type'=>'VARCHAR(13)', 'index'=>true),
    'storeID' => array('type'=>'INT', 'default'=>0),
    'modified' => array('type'=>'DATETIME'),
    'cost' => array('type'=>'DECIMAL(10,3)'),
    'uid' => array('type'=>'INT'),
    'prodUpdateID' => array('type'=>'BIGINT UNSIGNED','index'=>true),
    );
}

