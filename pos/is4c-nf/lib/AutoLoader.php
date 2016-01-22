<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of IT CORE.

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

if (!defined('CONF_LOADED')) {
    include_once(dirname(__FILE__).'/LocalStorage/conf.php');
}

/**
  @class LibraryClass
  Class for defining library functions.
  All methods should be static.

  This exists to make documented hierarchy
  more sensible.
*/
class LibraryClass {
}

/**
  @class AutoLoader
  Map available modules and register automatic
  class loading
*/
class AutoLoader extends LibraryClass 
{

    /**
      Autoload class by name
      @param $name class name
    */
    static public function loadClass($name)
    {
        global $CORE_LOCAL;
        $map = CoreLocal::get("ClassLookup");
        if (!is_array($map)) {
            // attempt to build map before giving up
            self::loadMap();
            $map = CoreLocal::get("ClassLookup");
            if (!is_array($map)) {
                return;
            }
        }

        if (isset($map[$name]) && !file_exists($map[$name])) {
            // file is missing. 
            // rebuild map to see if the class is
            // gone or the file just moved
            self::loadMap();
            $map = CoreLocal::get("ClassLookup");
            if (!is_array($map)) {
                return;
            }
        } elseif (!isset($map[$name]) && strpos($name, '\\') > 0) {
            $pieces = explode('\\', $name);
            $sep = DIRECTORY_SEPARATOR;
            if (count($pieces) > 2 && $pieces[0] == 'COREPOS' && $pieces[1] == 'common') {
                $path = dirname(__FILE__) . $sep . '..' . $sep . '..' . $sep . '..' . $sep . 'common' . $sep;
                $path .= self::arrayToPath(array_slice($pieces, 2));
                if (file_exists($path)) {
                    $map[$name] = $path;
                }
            } elseif (count($pieces) > 2 && $pieces[0] == 'COREPOS' && $pieces[1] == 'pos') {
                $path = dirname(__FILE__) . $sep . '..' . $sep;
                $path .= self::arrayToPath(array_slice($pieces, 2));
                if (file_exists($path)) {
                    $map[$name] = $path;
                }
            }
        } elseif (!isset($map[$name])) {
            // class is unknown
            // rebuild map to see if the definition
            // file has been added
            self::loadMap();
            $map = CoreLocal::get("ClassLookup");
            if (!is_array($map)) {
                return;
            }
        }

        if (isset($map[$name]) && !class_exists($name,false)
           && file_exists($map[$name])) {

            include_once($map[$name]);
        }
    }

    private static function arrayToPath($arr)
    {
        $ret = array_reduce($arr, function($carry, $item){ return $carry . $item . DIRECTORY_SEPARATOR; });

        return substr($ret, 0, strlen($ret)-1) . '.php';
    }

    /**
      Map available classes. Class names should
      match filenames for lookups to work.
    */
    static public function loadMap()
    {
        $class_map = array();
        $search_path = realpath(dirname(__FILE__).'/../');
        self::recursiveLoader($search_path, $class_map);
        CoreLocal::set("ClassLookup",$class_map);
    }

    static private $class_paths = array(
        'DiscountType'      => '/Scanning/DiscountTypes',
        'FooterBox'         => '/FooterBoxes',
        'Kicker'            => '/Kickers',
        'Parser'            => '/../parser-class-lib/parse',
        'PreParser'         => '/../parser-class-lib/preparse',
        'PriceMethod'       => '/Scanning/PriceMethods',
        'SpecialUPC'        => '/Scanning/SpecialUPCs',
        'SpecialDept'       => '/Scanning/SpecialDepts',
        'TenderModule'      => '/Tenders',
        'ProductSearch'     => '/Search/Products',
        'PrintHandler'      => '/PrintHandlers',
        'TotalAction'       => '/TotalActions',
        'TenderReport'      => '/ReceiptBuilding/TenderReports',
        'BasicModel'        => '/models',
        'COREPOS\pos\lib\models\BasicModel' => '/models',
        'DefaultReceiptDataFetch'   => '/ReceiptBuilding/ReceiptDataFetch',
        'DefaultReceiptFilter'      => '/ReceiptBuilding/ReceiptFilter',
        'DefaultReceiptSort'        => '/ReceiptBuilding/ReceiptSort',
        'DefaultReceiptTag'         => '/ReceiptBuilding/ReceiptTag',
        'DefaultReceiptSavings'     => '/ReceiptBuilding/ReceiptSavings',
        'DefaultReceiptThanks'      => '/ReceiptBuilding/ThankYou',
        'ReceiptMessage'            => '/ReceiptBuilding/Messages',
        'CustomerReceiptMessage'    => '/ReceiptBuilding/custMessages',
        'VariableWeightReWrite'     => '/Scanning/VariableWeightReWrites',
    );

    private static $base_classes = array(
        'DiscountModule'    => '/DiscountModule.php',
        'MemberLookup'      => '/MemberLookup.php',
        'ItemNotFound'      => '/ItemNotFound.php',
    );

    /**
      Get a list of available modules with the
      given base class
      @param $base_class string class name
      @param $include_base whether base class should be included
        in the return value
      @return an array of class names
    */
    static public function listModules($base_class, $include_base=False)
    {
        $ret = array();
        
        // lookup plugin modules, then standard modules
        $map = Plugin::pluginMap();
        if (isset(self::$class_paths[$base_class])) {
            $path = realpath(dirname(__FILE__) . self::$class_paths[$base_class]);
            $map = Plugin::pluginMap($path,$map);
        }
        if (isset(self::$base_classes[$base_class])) {
            $path = realpath(dirname(__FILE__) . self::$base_classes[$base_class]);
            $map[$base_class] = $path;

        }

        foreach($map as $name => $file) {

            // matched base class
            if ($name === $base_class) {
                if ($include_base) $ret[] = $name;
                continue;
            }
            if (in_array($name, self::$blacklist)) {
                continue;
            }

            if (strstr($file,'plugins')) {
                $parent = Plugin::memberOf($file);
                if ($base_class !== 'Plugin' && $parent && !Plugin::isEnabled($parent)) {
                    continue;
                }
            }

            ob_start();
            $ns_class = self::fileToFullClass($file);
            if (class_exists($ns_class)) {
                $name = $ns_class;
            } elseif (!class_exists($name)) { 
                ob_end_clean();
                continue;
            }

            if (is_subclass_of($name,$base_class)) {
                $ret[] = $name;
            }

            ob_end_clean();
        }

        return $ret;
    }

    static private $blacklist = array();
    static public function blacklist($class)
    {
        if (!in_array($class, self::$blacklist)) {
            self::$blacklist[] = $class;
        }
    }

    static private function fileToFullClass($file)
    {
        $file = realpath($file);
        if (substr($file, -4) == '.php') {
            $file = substr($file, 0, strlen($file)-4);
        }

        $path = realpath(dirname(__FILE__) . '/../') . DIRECTORY_SEPARATOR;
        $file = str_replace($path, '', $file);
        $nss = array_reduce(explode(DIRECTORY_SEPARATOR, $file),
            function ($carry, $item) { return $carry . '\\' . $item; });

        return 'COREPOS\\pos' . $nss;
    }

    /**
      Helper function to walk through file structure
      @param $path starting path
      @param $map array of class name => file
      @return $map (by reference)
    */
    static private function recursiveLoader($path,&$map=array())
    {
        if(!is_dir($path)) {
            return $map;
        }
        
        // skip searching these directories
        // to improve overall performance
        $exclude = array(
            'css',
            'graphics',
            'gui-modules',
            'js',
            'locale',
            'log',
            'NewMagellan',
            'test',
        );

        $dir = opendir($path);
        while($dir && ($file=readdir($dir)) !== false) {
            if ($file[0] == ".") continue;

            $fullname = realpath($path."/".$file);
            if (is_dir($fullname) && !in_array($file, $exclude)) {
                self::recursiveLoader($fullname, $map);
            } else if (substr($file,-4) == '.php') {
                $class = substr($file,0,strlen($file)-4);
                $map[$class] = $fullname;
            }
        }
        closedir($dir);
    }

    /**
      Use a dedicated dispatch function to launch
      page classes.
      @param $redirect [boolean, default true]
        go to login page if an error occurs
      
      This method checks for the session variable
      CashierNo as a general indicator that the current
      session has been properly initialized
    */
    public static function dispatch($redirect=true)
    {
        $stack = debug_backtrace();
        if (count($stack) == 1) {
            $page = basename($_SERVER['PHP_SELF']);
            $class = substr($page,0,strlen($page)-4);
            if (CoreLocal::get('CashierNo') !== '' && $class != 'index' && class_exists($class)) {
                $page = new $class();
            } elseif ($redirect) {
                $url = MiscLib::baseURL();
                header('Location: ' . $url . 'login.php');
            } else {
                trigger_error('Missing class '.$class, E_USER_NOTICE);
            }
        }
    }
}

spl_autoload_register(array('AutoLoader','loadClass'), true, true);
// add composer classes if present
if (file_exists(dirname(__FILE__) . '/../../../vendor/autoload.php')) {
    include_once(dirname(__FILE__) . '/../../../vendor/autoload.php');
}

/** 
  Internationalization 
  setlocale() probably always exists
  but the gettext functions may or may not
  be available
*/
if (function_exists('setlocale') && defined('LC_MESSAGES') && CoreLocal::get('locale') !== '') {
    setlocale(LC_MESSAGES, CoreLocal::get('locale') . '.utf8');
    putenv('LC_MESSAGES=' . CoreLocal::get('locale') . '.utf8');
    if (function_exists('bindtextdomain')) {
        bindtextdomain('pos-nf', realpath(dirname(__FILE__).'/../locale'));
        textdomain('pos-nf');
    }
}

/**
  Add placeholder gettext function if
  the module is not enabled. Translations
  won't work but pages won't crash either
*/
if (!function_exists('gettext')) {
    function _($str) { return $str; }
}

