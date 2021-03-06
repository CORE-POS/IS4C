<?php

/*******************************************************************************

    Copyright 2018 Whole Foods Co-op

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
  @class ProductCostChangesModel
*/
class ProductCostChangesModel extends BasicModel
{
    protected $name = "productCostChanges";
    protected $preferred_db = 'op';

    protected $columns = array(
    'id' => array('type'=>'INT','index'=>true,'primary_key'=>true),
    'upc' => array('type'=>'VARCHAR(13)', 'index'=>true),
    'previousCost' => array('type'=>'DECIMAL(10,3)'),
    'newCost' => array('type'=>'DECIMAL(10,3)'),
    'difference' => array('type'=>'DECIMAL(10,3)'),
    'date' => array('type'=>'DATE'),
    );

    public function doc()
    {
        return '
Track changes to product costs. This is redudant with the
table ProdCostHistory but the format differences haven\'t been
reconciled yet. This stores two sequential costs per record
for easier access to the difference.
';
    }

}

