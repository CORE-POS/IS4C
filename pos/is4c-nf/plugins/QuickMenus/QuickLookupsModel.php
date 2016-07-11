<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of Fannie.

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

namespace COREPOS\pos\plugins\QuickMenus;

/**
  @class QuickLookupsModel
*/
class QuickLookupsModel extends \COREPOS\pos\lib\models\BasicModel
{

    protected $name = "QuickLookups";

    protected $preferred_db = 'op';

    protected $columns = array(
    'quickLookupID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'lookupSet' => array('type'=>'SMALLINT', 'default'=>0),
    'label' => array('type'=>'VARCHAR(100)'),
    'action' => array('type'=>'VARCHAR(25)'),
    'sequence' => array('type'=>'SMALLINT', 'default'=>0),
    );
}

