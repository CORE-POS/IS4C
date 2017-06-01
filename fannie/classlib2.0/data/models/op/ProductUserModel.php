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
  @class ProductUserModel
*/
class ProductUserModel extends BasicModel 
{

    protected $name = "productUser";
    protected $preferred_db = 'op';

    protected $columns = array(
    'upc' => array('type'=>'VARCHAR(13)', 'primary_key'=>True),
    'description' => array('type'=>'VARCHAR(255)'),
    'brand' => array('type'=>'VARCHAR(255)'),
    'sizing' => array('type'=>'VARCHAR(255)'),
    'photo' => array('type'=>'VARCHAR(255)'),
    'nutritionFacts' => array('type'=>'VARCHAR(255)'),
    'long_text' => array('type'=>'TEXT'),
    'enableOnline' => array('type'=>'TINYINT'),
    'soldOut' => array('type'=>'TINYINT', 'default'=>0),
    'signCount' => array('type'=>'TINYINT', 'default'=>1),
    'narrow' => array('type'=>'TINYINT', 'default'=>0),
    );

    public function doc()
    {
        return '
Depends on:
* products (table)

Use:
Longer product descriptions for use in
online webstore
        ';
    }

    public function hookAddColumnnarrow()
    {
        if ($this->connection->tableExists('NarrowTags')) {
            $this->connection->startTransaction();
            $prep = $this->connection->prepare('UPDATE productUser SET narrow=1 WHERE upc=?');
            $res = $this->connection->query('SELECT upc FROM NarrowTags');
            while ($row = $this->connection->fetchRow($res)) {
                $this->connection->execute($prep, array($row['upc']));
            }
            $this->connection->commitTransaction();
        }
    }
}

