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
  @class FloorSectionsListViewModel
*/
class FloorSectionsListViewModel extends ViewModel
{

    protected $name = "FloorSectionsListView";
    protected $preferred_db = 'op';

    protected $columns = array(
    'upc' => array('type'=>'VARCHAR(13)', 'primary_key'=>true),
    'storeID' => array('type'=>'INT', 'primary_key'=>true),
    'sections'=> array('type'=>'VARCHAR(255)'),
    );

    public function definition()
    {
        $concat = 'GROUP_CONCAT(f.name SEPARATOR \',\')';
        if ($this->connection->dbmsName() == 'postgres9') {
            $concat = 'string_agg(f.name, \',\')';
        }
        return '
            SELECT m.upc,
                f.storeID,
                ' . $concat . ' AS sections
            FROM FloorSectionProductMap AS m
                INNER JOIN FloorSections AS f ON m.floorSectionID=f.floorSectionID
            GROUP BY m.upc,
                f.storeID
        ';
    }

    public function doc()
    {
        return '
            This view is just a convenience to simplify listing
            all floor sections where a product can be found. It currently
            is MySQL only but could be ported to Postgres 9+ using the
            STRING_AGG function in place of GROUP_CONCAT.
        ';
    }
}

