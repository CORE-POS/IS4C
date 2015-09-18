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
  @class ProdPhysicalLocationModel
*/
class ProdPhysicalLocationModel extends BasicModel
{

    protected $name = "prodPhysicalLocation";
    protected $preferred_db = 'op';

    protected $columns = array(
    'upc' => array('type'=>'VARCHAR(13)', 'primary_key'=>true),
    'store_id' => array('type'=>'SMALLINT', 'default'=>0),
    'floorSectionID' => array('type'=>'INT'),
    'section' => array('type'=>'SMALLINT', 'default'=>0),
    'subsection' => array('type'=>'SMALLINT', 'default'=>0),
    'shelf_set' => array('type'=>'SMALLINT', 'default'=>0),
    'shelf' => array('type'=>'SMALLINT', 'default'=>0),
    'location' => array('type'=>'INT', 'default'=>0),
    );

    public function doc()
    {
        return '
Depends on:
* products (table)

Use:
Storing physical location of products within a store.

Floor Section ID replaces section and subsection to
areas of the store can be given human-readable names
rather than using a pure numbering system.

Section and/or subsection represents a set of shelves.
In a lot of cases this would be one side of an aisle but
it could also be an endcap or a cooler or something against
a wall that isn\'t formally an aisle. A store can use either
or both. For example, section could map to aisle numbering
and subsection could indicate the left or right side of
that aisle. Another option would be to map section to a
super department (e.g., grocery) and subsection to an aisle-side
within that department.

"Shelf set" is a division within a subsection. It could be
one physical shelving unit or a freezer door.

Shelf indicates the vertical shelf location. Bottom to
top numbering is recommended.

Location is the horizontal location on the shelf.
        ';
    }
}

