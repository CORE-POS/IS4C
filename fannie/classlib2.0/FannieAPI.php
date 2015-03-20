<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

class FannieAPI 
{

    /**
      Initialize session to retain class
      definition info.
    */
    static public function init()
    {
        if (ini_get('session.auto_start')==0 && !headers_sent() && php_sapi_name() != 'cli') {
            @session_start();
        }
        if (!isset($_SESSION['FannieClassMap'])) {
            $_SESSION['FannieClassMap'] = array();
        } elseif(!is_array($_SESSION['FannieClassMap'])) {
            $_SESSION['FannieClassMap'] = array();
        }
        if (!isset($_SESSION['FannieClassMap']['SQLManager'])) {
            $_SESSION['FannieClassMap']['SQLManager'] = realpath(dirname(__FILE__).'/../src/SQLManager.php');
        }
    }

    /**
      Load definition for given class
      @param $name the class name
    */
    static public function loadClass($name)
    {
        $map = $_SESSION['FannieClassMap'];

        // class map should be array
        // of class_name => file_name
        if (!is_array($map)) { 
            $map = array();
            $_SESSION['FannieClassMap'] = array();
        }

        // if class is known in the map, include its file
        // otherwise search for an appropriate file
        if (isset($map[$name]) && !class_exists($name,false)
           && file_exists($map[$name])) {

            include_once($map[$name]);
        } else {

            /**
              There's a namespace involved
              If the prefix is COREPOS\Fannie\API, look for class
              in classlib2.0 directory path. 
              If the prefix is COREPOS\Fannie\Plugin, look for class
              in modules/plugins2.0 directory path
              Otherwise, just strip off the namespace and search
              both plugins and API class library
            */
            if (strstr($name, '\\')) {
                $full_name = explode('\\', $name);
                if (count($full_name) >= 3 && $full_name[0] == 'COREPOS' && $full_name[1] == 'Fannie' 
                    && ($full_name[2] == 'API' || $full_name[2] == 'Plugin')) {
                    $filename = '';
                    for ($i=3; $i<count($full_name); $i++) {
                        $filename .= $full_name[$i];
                        if ($i < count($full_name) - 1) {
                            $filename .= '/';
                        } else {
                            $filename .= '.php';
                        }
                    }
                    $expected_file = '';
                    if ($full_name[2] == 'API') {
                        $expected_file = dirname(__FILE__) . '/' . $filename;
                    } else {
                        $expected_file = dirname(__FILE__) . '/../modules/plugins2.0/' . $filename;
                    }
                    if (file_exists($expected_file)) {
                        include_once($expected_file);
                        $_SESSION['FannieClassMap'][$name] = $expected_file;
                        return;
                    }
                } else {
                    // remove all namespacing for global search below
                    $name = $full_name[count($full_name) - 1];
                }
            }

            // search class lib for definition
            $file = self::findClass($name, dirname(__FILE__));
            if ($file !== false) {
                include_once($file);
            }
            // search plugins for definition
            $file = self::findClass($name, dirname(__FILE__).'/../modules/plugins2.0');
            if ($file !== false) {
                // only use if enabled
                $owner = \COREPOS\Fannie\API\FanniePlugin::memberOf($file);
                if (\COREPOS\Fannie\API\FanniePlugin::isEnabled($owner)) {
                    include_once($file);
                }
            }
        }
    }

    /**
      Search for class in given path
      @param $name the class name
      @param $path path to search
      @return A filename or false
    */
    static private function findClass($name, $path)
    {
        if (!is_dir($path)) {
            return false;
        } else if ($name == 'index') {
            return false;
        }

        $dh = opendir($path);
        while($dh && ($file=readdir($dh)) !== false) {
            if ($file[0] == ".") continue;
            if ($file == 'noauto') continue;
            $fullname = realpath($path.'/'.$file);
            if (is_dir($fullname)) {
                // recurse looking for file
                $file = self::findClass($name, $fullname);
                if ($file !== false) { 
                    return $file;
                }
            } elseif (substr($file,-4) == '.php') {
                // map all PHP files as long as we're searching
                // but only return if the correct file is found
                $class = substr($file,0,strlen($file)-4);
                $_SESSION['FannieClassMap'][$class] = $fullname;
                if ($class == $name) {
                    return $fullname;
                }
            }
        }

        return false;
    }

    static public function listFiles($path)
    {
        if (is_file($path) && substr($path,-4)=='.php') {
            return array($path);
        } elseif (is_dir($path)) {
            $dh = opendir($path);
            $ret = array();
            while( ($file=readdir($dh)) !== false) {
                if ($file == '.' || $file == '..') continue;
                if ($file == 'noauto') continue;
                if ($file == 'index.php') continue;
                if ($file == 'Store-Specific') continue;
                $ret = array_merge($ret, self::listFiles($path.'/'.$file));
            }
            return $ret;
        }
        return array();
    }

    /**
      Get a list of all available classes implementing a given
      base class
      @param $base_class [string] name of base class
      @param $include_base [boolean] include base class name in the result set
        [optional, default false]
      @return [array] of [string] class names
    */
    static public function listModules($base_class, $include_base=false)
    {
        $directories = array();
        $directories[] = dirname(__FILE__).'/../modules/plugins2.0/';

        switch($base_class) {
            case 'ItemModule':
                $directories[] = dirname(__FILE__).'/../item/modules/';
                break;
            case 'MemberModule':
            case '\COREPOS\Fannie\API\member\MemberModule':
                $directories[] = dirname(__FILE__).'/../mem/modules/';
                break;
            case 'FannieTask':
                $directories[] = dirname(__FILE__).'/../cron/tasks/';
                break;
            case 'BasicModel':
                $directories[] = dirname(__FILE__).'/data/models/';
                break;
            case 'BasicModelHook':
            case '\COREPOS\Fannie\API\data\hooks\BasicModelHook':
                $directories[] = dirname(__FILE__).'/data/hooks/';
                break;
            case 'FannieReportPage':
                $directories[] = dirname(__FILE__).'/../reports/';
                break;
            case 'FannieReportTool':
            case '\COREPOS\Fannie\API\FannieReportTool':
                $directories[] = dirname(__FILE__).'/../reports/';
                break;
            case 'FannieSignage':
            case '\COREPOS\Fannie\API\item\FannieSignage':
                $directories[] = dirname(__FILE__) . '/item/signage/';
                break;
            case 'FanniePage':
                $directories[] = dirname(__FILE__).'/../admin/';
                $directories[] = dirname(__FILE__).'/../batches/';
                $directories[] = dirname(__FILE__).'/../cron/management/';
                $directories[] = dirname(__FILE__).'/../item/';
                $directories[] = dirname(__FILE__).'/../logs/';
                $directories[] = dirname(__FILE__).'/../reports/';
                $directories[] = dirname(__FILE__).'/../mem/';
                $directories[] = dirname(__FILE__).'/../purchasing/';
                /*
                $directories[] = dirname(__FILE__).'/../install/';
                $directories[] = dirname(__FILE__).'/../ordering/';
                */
                break;
        }

        // recursive search
        $search = function($path) use (&$search) {
            if (is_file($path) && substr($path,-4)=='.php') {
                return array($path);
            } elseif (is_dir($path)) {
                $dh = opendir($path);
                $ret = array();
                while( ($file=readdir($dh)) !== false) {
                    if ($file == '.' || $file == '..') continue;
                    if ($file == 'noauto') continue;
                    if ($file == 'index.php') continue;
                    if ($file == 'Store-Specific') continue;
                    $ret = array_merge($ret, $search($path.'/'.$file));
                }
                return $ret;
            }
            return array();
        };

        $files = array();
        foreach($directories as $dir) {
            $files = array_merge($files, $search($dir));
        }

        $ret = array();
        foreach($files as $file) {
            $class = substr(basename($file),0,strlen(basename($file))-4);
            // matched base class
            if ($class === $base_class) {
                if ($include_base) {
                    $ret[] = $class;
                }
                continue;
            }
            
            // almost certainly not a class definition
            if ($class == 'index') {
                continue;
            }

            // if the file is part of a plugin, make sure
            // the plugin is enabled. The exception is when requesting
            // a list of plugin classes
            if (strstr($file, 'plugins2.0') && $base_class != 'FanniePlugin' && $base_class != '\COREPOS\Fannie\API\FanniePlugin') {
                $parent = \COREPOS\Fannie\API\FanniePlugin::memberOf($file);
                if ($parent === false || !\COREPOS\Fannie\API\FanniePlugin::isEnabled($parent)) {
                    continue;
                }
            }

            // verify class exists
            ob_start();
            include_once($file);
            ob_end_clean();

            $namespaced_class = self::pathToClass($file);

            if (!class_exists($class) && !class_exists($namespaced_class)) {
                continue;
            }

            if (class_exists($class) && is_subclass_of($class, $base_class)) {
                $ret[] = $class;
            } elseif (class_exists($namespaced_class) && is_subclass_of($namespaced_class, $base_class)) {
                $ret[] = $namespaced_class;
            }
        }

        return $ret;
    }

    /**
      Determine fully namespaced class name
      based on filesystem path
      @param $path [string] file system path
      @return [string] class name with namespace if applicable
    */
    public static function pathToClass($path)
    {
        if (strstr($path, '/modules/plugins2.0/')) {
            $name = '\\COREPOS\\Fannie\\Plugin';
            $parts = explode('/', $path);
            $start = false;
            for ($i=0; $i<count($parts); $i++) {
                if ($parts[$i] == '') { 
                    continue;
                } elseif ($parts[$i] == 'plugins2.0') {
                    $start = true;
                    continue;
                }
                if ($start) {
                    $name .= '\\' . $parts[$i];
                }
            }

            return substr($name, 0, strlen($name)-4);
        } elseif (strstr($path, '/classlib2.0/')) {
            $name = '\\COREPOS\\Fannie\\API';
            $parts = explode('/', $path);
            $start = false;
            for ($i=0; $i<count($parts); $i++) {
                if ($parts[$i] == '') { 
                    continue;
                } elseif ($parts[$i] == 'classlib2.0') {
                    $start = true;
                    continue;
                }
                if ($start) {
                    $name .= '\\' . $parts[$i];
                }
            }

            return substr($name, 0, strlen($name)-4);
        } else {
            $name = basename($file);        

            return substr($name, 0, strlen($name)-4);
        }
    }
}

FannieAPI::init();
if (function_exists('spl_autoload_register')) {
    spl_autoload_register(array('FannieAPI','loadClass'));
    if (file_exists(dirname(__FILE__) . '/../../vendor/autoload.php')) {
        include_once(dirname(__FILE__) . '/../../vendor/autoload.php');
    }
} else {
    function __autoload($name)
    {
        FannieAPI::loadClass($name);
    }
}

