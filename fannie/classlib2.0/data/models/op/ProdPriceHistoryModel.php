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
  @class ProdPriceHistoryModel
*/
class ProdPriceHistoryModel extends BasicModel
{

    protected $name = "prodPriceHistory";
    protected $preferred_db = 'op';

    protected $columns = array(
    'prodPriceHistoryID' => array('type'=>'BIGINT UNSIGNED', 'primary_key'=>true, 'increment'=>true),
    'upc' => array('type'=>'VARCHAR(13)', 'index'=>true),
    'storeID' => array('type'=>'INT', 'default'=>0),
    'modified' => array('type'=>'DATETIME'),
    'price' => array('type'=>'MONEY'),
    'uid' => array('type'=>'INT'),
    'prodUpdateID' => array('type'=>'BIGINT UNSIGNED','index'=>true),
    );

    public function doc()
    {
        return '
Depends on:
* prodUpdate (table)

Use:
This table holds a compressed version of prodUpdate.
A entry is only made when an item\'s regular price setting
changes. uid is the user who made the change.
        ';
    }
}

