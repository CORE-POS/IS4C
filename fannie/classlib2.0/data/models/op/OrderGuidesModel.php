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
  @class OrderGuidesModel
*/
class OrderGuidesModel extends BasicModel
{
    protected $name = "OrderGuides";
    protected $preferred_db = 'op';

    protected $columns = array(
    'orderGuideID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'vendorID' => array('type'=>'INT'),
    'storeID' => array('type'=>'INT'),
    'upc' => array('type'=>'VARCHAR(13)'),
    'description' => array('type'=>'VARCHAR(255)'),
    'par' => array('type'=>'MONEY'),
    'seq' => array('type'=>'INT'),
    );

    public function doc()
    {
        return '
An OrderGuide is a non-automated way to build purchase orders. A guide is
defined with a preset selection of items and the user than fills in the
amounts to order';
    }
}

