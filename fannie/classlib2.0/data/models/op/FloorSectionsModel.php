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
  @class FloorSectionsModel
*/
class FloorSectionsModel extends BasicModel
{

    protected $name = "FloorSections";

    protected $columns = array(
    'floorSectionID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'storeID' => array('type'=>'INT', 'default'=>1),
    'name' => array('type'=>'VARCHAR(50)'),
    );

    public function toOptions($selected=0, $id_as_label=false)
    {
        $prep = $this->connection->prepare('
            SELECT f.floorSectionID,
                s.description,
                f.name
            FROM ' . FannieDB::fqn('FloorSections', 'op') . ' AS f
                INNER JOIN ' . FannieDB::fqn('Stores', 'op') . ' AS s ON f.storeID=s.storeID
            ORDER BY s.description, f.name');
        $res = $this->connection->execute($prep);
        $ret = '';
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= sprintf('<option %s value="%d">%s %s</option>',
                ($row['floorSectionID'] == $selected ? 'selected' : ''),
                $row['floorSectionID'], $row['description'], $row['name']);
        }

        return $ret;
    } 

    public function doc()
    {
        return '
Floor Sections are a simplified way of managing in-store product locations.
A floor section is just an arbitrarily named area of the store that can be
as large or as small makes practical sense.';
    }
}

