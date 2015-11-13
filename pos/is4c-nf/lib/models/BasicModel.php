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
        /**
          Experiment using lambdas. I was curious
          if I could do recursion without having
          a named function.
        */
        $search = function($path) use (&$search) {
            if (is_file($path) && substr($path,'-4')=='.php') {
                include_once($path);
                $class = basename(substr($path,0,strlen($path)-4));
                if (class_exists($class) && is_subclass_of($class, 'BasicModel')) {
                    return array($class);
                }
            } elseif(is_dir($path)) {
                $dh = opendir($path);
                $ret = array();    
                while( ($file=readdir($dh)) !== False) {
                    if ($file == '.' || $file == '..') {
                        continue;
                    }
                    $ret = array_merge($ret, $search($path.'/'.$file));
                }
                return $ret;
            }
            return array();
        };
        $models = $search(dirname(__FILE__));

        return $models;
    }

    /**
      Interface method
      Should eventually inherit from \COREPOS\common\BasicModel
    */
    public function setConnectionByName($db_name)
    {
        if ($db_name == \CoreLocal::get('pDatabase')) {
            $this->connection = \Database::pDataConnect();
        } else if ($db_name == \CoreLocal::get('tDatabase')) {
            $this->connection = \Database::tDataConnect();
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

    if ($argc > 2 && $argv[1] == '--doc') {
        array_shift($argv);
        array_shift($argv);
        $tables = array();
        foreach ($argv as $file) {
            if (!file_exists($file)) {
                continue;
            }
            if (!substr($file, -4) == 'php') {
                continue;
            }
            $class = pathinfo($file, PATHINFO_FILENAME);
            if (!class_exists($class)) { // nested / cross-linked includes
                include($file);
                if (!class_exists($class)) {
                    continue;
                }
            }
            $obj = new $class(null);
            if (!is_a($obj, 'BasicModel')) {
                continue;
            }

            $table = $obj->getName();
            $doc = '### ' . $table . "\n";
            if (is_a($obj, 'ViewModel')) {
                $doc .= '**View**' . "\n\n";
            }
            $doc .= $obj->columnsDoc();
            $doc .= $obj->doc();

            $tables[$table] = $doc;
        }
        ksort($tables);
        foreach ($tables as $t => $doc) {
            echo '* [' . $t . '](#' . strtolower($t) . ')' . "\n";
        }
        echo "\n";
        foreach ($tables as $t => $doc) {
            echo $doc;
            echo "\n";
        }
        return 0;
    }

    /* Argument signatures, to php, where BasicModel.php is the first:
   * 3 args: Create new Model: php BasicModel.php --new <Model Name>\n";
   * 4 args: Update Table Structure: php BasicModel.php --update <Database name> <Subclass Filename[[Model].php]>\n";
    */
    if (($argc < 3 || $argc > 4) || ($argc == 3 && $argv[1] != "--new") || ($argc == 4 && $argv[1] != '--update')) {
        echo "Create new Model: php BasicModel.php --new <Model Name>\n";
        echo "Update Table Structure: php BasicModel.php --update <Database name> <Subclass Filename>\n";
        echo "Generate markdown documentation: php BasicModel.php --doc <Model Filename(s)>\n";
        return 0;
    }

    // Create new Model
    if ($argc == 3) {
        $modelname = $argv[2];
        if (substr($modelname,-4) == '.php') {
            $modelname = substr($modelname,0,strlen($modelname)-4);
        }
        if (substr($modelname,-5) != 'Model') {
            $modelname .= 'Model';
        }
        echo "Generating Model '$modelname'\n";
        $obj = new BasicModel(null);
        $obj->newModel($modelname);
        return 0;
    }

    $classfile = $argv[1];
    if ($argc == 4) {
        $classfile = $argv[3];
    }
    if (substr($classfile,-4) != '.php') {
        $classfile .= '.php';
    }
    if (!file_exists($classfile)) {
        echo "Error: file '$classfile' does not exist\n";
        return 1;
    }

    $class = pathinfo($classfile, PATHINFO_FILENAME);
    include($classfile);
    if (!class_exists($class)) {
        echo "Error: class '$class' does not exist\n";
        return 1;
    }

    // A new object of the type named on the command line.
    $obj = new $class(null);
    if (!is_a($obj, 'BasicModel')) {
        echo "Error: invalid class. Must be BasicModel\n";
        return 1;
    }

    // Update Table Structure
    // Show what changes are needed but don't make them yet.
    $try = $obj->normalize($argv[2],BasicModel::NORMALIZE_MODE_CHECK);
    // If there was no error and there is anything to change,
    //  including creating the table.
    // Was: If the table exists and there is anything to change
    //  get OK to change.
    if ($try !== false && $try > 0) {
        while(true) {
            echo 'Apply Changes [Y/n]: ';
            $in = rtrim(fgets(STDIN));
            if ($in === 'n' || $in === false || $in === '') {
                echo "Goodbye.\n";
                break;
            } elseif($in ==='Y') {
                // THIS WILL APPLY PROPOSED CHANGES!
                $obj->normalize($argv[2], BasicModel::NORMALIZE_MODE_APPLY, true);
                break;
            }
        }
    }
}

