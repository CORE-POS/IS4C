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
  @class OriginsModel
*/
class OriginsModel extends BasicModel
{

    protected $name = "origins";
    protected $preferred_db = 'op';

    protected $columns = array(
    'originID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'countryID' => array('type'=>'INT'),
    'stateProvID' => array('type'=>'INT'),
    'customID' => array('type'=>'INT'),
    'local' => array('type'=>'TINYINT', 'default'=>0),
    'name' => array('type'=>'VARCHAR(100)'),
    'shortName' => array('type'=>'VARCHAR(50)'),
    );

    public function doc()
    {
        return '
Depends on:
* originCountry
* originStateProv
* originCustomRegion

Use:
This table defines locations.
The IDs correspond to the other origin
tables. The local field indicates whether
or not this origin is considered local
by the co-op.
        ';
    }

    /**
      Look up local origins. Normally these are
      found in the origins table but will use the
      older, deprecated originName table if it
      exists and the appropriate columns aren't in
      the origins table.
      @return [array] originID => shortName
    */
    public function getLocalOrigins()
    {
        $def = $this->connection->tableDefinition('origins');
        $ret = array();
        if (isset($def['shortName'])) {
            $o = new OriginsModel($this->connection);
            $o->local(1);
            foreach ($o->find('originID') as $origin) {
                $ret[$origin->originID()] = $origin->shortName();
            }
        } elseif ($this->connection->tableExists('originNames')) {
            $q = '
                SELECT originID,
                    shortName 
                FROM originName 
                WHERE local=1 
                ORDER BY originID';
            $r = $this->connection->query($q);
            while ($w = $this->connection->fetchRow($r)) {
                $ret[$w['originID']] = $w['shortName'];
            }
        }

        return $ret;
    }
}

