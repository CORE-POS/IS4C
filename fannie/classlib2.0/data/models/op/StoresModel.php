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
  @class StoresModel
*/
class StoresModel extends BasicModel
{

    protected $name = "Stores";

    protected $preferred_db = 'op';

    protected $columns = array(
    'storeID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'description' => array('type'=>'VARCHAR(50)'),
    'dbHost' => array('type'=>'VARCHAR(50)'),
    'dbDriver' => array('type'=>'VARCHAR(15)'),
    'dbUser' => array('type'=>'VARCHAR(25)'),
    'dbPassword' => array('type'=>'VARCHAR(25)'),
    'transDB' => array('type'=>'VARCHAR(20)'),
    'opDB' => array('type'=>'VARCHAR(20)'),
    'push' => array('type'=>'TINYINT', 'default'=>1),
    'pull' => array('type'=>'TINYINT', 'default'=>1),
    'hasOwnItems' => array('type'=>'TINYINT', 'default'=>1),
    'webServiceUrl' => array('type'=>'VARCHAR(255)'),
    );

    public function doc()
    {
        return '
Use:
List of known stores. By convention
storeID zero should NOT be used; it represents
all stores combined.

The local store should have an entry containing at 
least dbHost so it can identify itself. The other
database credentials are not necessary for the
local store since they must be known already to
access the table.

Entries for remote stores need full credentials.
Setting up user accounts with read-only to remote
        ';
    }
}

