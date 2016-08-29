<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

namespace COREPOS\pos\lib\models;
use COREPOS\pos\lib\Database;

if (!class_exists('AutoLoader')) {
    include(dirname(__FILE__) . '/../AutoLoader.php');
}

/**
  @class BasicModel
*/

class BasicModel extends \COREPOS\common\BasicModel
{

    /** check for potential changes **/
    const NORMALIZE_MODE_CHECK = 1;
    /** apply changes **/
    const NORMALIZE_MODE_APPLY = 2;

    protected $new_model_namespace = '\\COREPOS\\pos\\lib\\models\\';

    /**
      Interface method
      Should eventually inherit from \COREPOS\common\BasicModel
    */
    public function getModels()
    {
        $mods = AutoLoader::listModules('COREPOS\pos\lib\models\BasicModel');

        return $mods;
    }

    /**
      Interface method
      Should eventually inherit from \COREPOS\common\BasicModel
    */
    public function setConnectionByName($db_name)
    {
        if ($db_name == \CoreLocal::get('pDatabase')) {
            $this->connection = Database::pDataConnect();
        } else if ($db_name == \CoreLocal::get('tDatabase')) {
            $this->connection = Database::tDataConnect();
        } else {
            /**
              Allow for db other than main ones, e.g. for a plugin.
              Force a new connection to avoid messing with the
              one maintained by the Database class
            */
            $this->connection = new \COREPOS\pos\lib\SQLManager(
                \CoreLocal::get("localhost"),
                \CoreLocal::get("DBMS"),
                $db_name,
                \CoreLocal::get("localUser"),
                \CoreLocal::get("localPass"),
                false,
                true
            );
        }
    }

}

if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {

    include_once(dirname(__FILE__).'/../AutoLoader.php');
    \AutoLoader::loadMap();
    $obj = new BasicModel(null);
    return $obj->cli($argc, $argv);
}

