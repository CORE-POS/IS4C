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
  @class VendorsModel
*/
class VendorsModel extends BasicModel 
{

    protected $name = "vendors";
    protected $preferred_db = 'op';

    protected $columns = array(
    'vendorID' => array('type'=>'INT', 'primary_key'=>true),
    'vendorName' => array('type'=>'VARCHAR(50)'),
    'vendorAbbreviation' => array('type'=>'VARCHAR(10)'),
    'shippingMarkup' => array('type'=>'DOUBLE', 'default'=>0),
    'discountRate' => array('type'=>'DOUBLE', 'default'=>0),
    'phone' => array('type'=>'VARCHAR(15)'),
    'fax' => array('type'=>'VARCHAR(15)'),
    'email' => array('type'=>'VARCHAR(50)'),
    'website' => array('type'=>'VARCHAR(100)'),
    'address' => array('type'=>'VARCHAR(200)'),
    'city' => array('type'=>'VARCHAR(20)'),
    'state' => array('type'=>'VARCHAR(2)'),
    'zip' => array('type'=>'VARCHAR(10)'),
    'notes' => array('type'=>'TEXT'),
    'localOriginID' => array('type'=>'INT', 'default'=>0),
    'inactive' => array('type'=>'TINYINT', 'default'=>0),
    'orderMinimum' => array('type'=>'MONEY', 'default'=>0),
    'halfCases' => array('type'=>'TINYINT', 'default'=>0),
    );

    public function hookAddColumnvendorAbbreviation()
    {
        $query = '
            UPDATE vendors
            SET vendorAbbreviation=LEFT(vendorName, 10)';
        $this->connection->query($query);
    }

    public function toOptions($selected=0, $id_as_label=false)
    {
        $this->inactive(0);
        return parent::toOptions($selected, $id_as_label);
    }

    public function doc()
    {
        return '
Use:
List of known vendors. Pretty simple.
        ';
    }
}

