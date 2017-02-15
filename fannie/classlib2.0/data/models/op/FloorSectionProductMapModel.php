<?php

/*******************************************************************************

    Copyright 2016 Whole Foods Co-op

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
  @class FloorSectionProductMapModel
*/
class FloorSectionProductMapModel extends BasicModel
{

    protected $name = "FloorSectionProductMap";
    protected $preferred_db = 'op';

    protected $columns = array(
    'floorSectionProductMapID' => array('type'=>'INT', 'increment'=>true, 'index'=>true),
    'upc' => array('type'=>'VARCHAR(13)', 'primary_key'=>true),
    'floorSectionID' => array('type'=>'INT', 'primary_key'=>true),
    );

    public function doc()
    {
        return '
            This table will eventually deprecate prodPhysicalLocation.
            Using floor sections seems more practical than the original,
            hyper-specific data setup that noted *exactly* where on a
            particular shelf the item is located. But having a mapping
            table also works better to cope with products that are located
            in more than one place.
        ';
    }
}

