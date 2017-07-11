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

use COREPOS\pos\lib\LocalStorage\LaneCache;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\plugins\Plugin;
use COREPOS\pos\lib\LocalStorage\WrappedStorage;

use COREPOS\common\mvc\FormValueContainer;

if (!defined('CONF_LOADED')) {
    include_once(dirname(__FILE__).'/LocalStorage/conf.php');
}

/**
  @class AutoLoader
  Map available modules and register automatic
  class loading
*/
class AutoLoader 
{
    /**
      Autoload class by name
      @param $name class name
    */
    static public function loadClass($name)
    {
        $map = CoreLocal::get("ClassLookup");
        if (!is_array($map)) {
            // attempt to build map before giving up
            $map = self::loadMap();
            if (!is_array($map)) {
                return;
            }
        }

        if (!isset($map[$name]) && strpos($name, '\\') > 0) {
            if ($name[0] == '\\') { // some old PHP5.3 versions leave the leading backslash
                $name = substr($name, 1);
            }
            $sep = DIRECTORY_SEPARATOR;
            if (strpos($name, 'COREPOS\\common\\') === 0) {
                $ourPath = __DIR__ . $sep . '..' . $sep . '..' . $sep . '..' . $sep . 'common' . $sep . strtr(substr($name, 15), '\\', $sep) . '.php';
                $map[$name] = $ourPath;
                CoreLocal::set('ClassLookup', $map);
            } elseif (strpos($name, 'COREPOS\\pos\\') === 0) {
                $ourPath = __DIR__ . $sep . '..' . $sep . strtr(substr($name, 12), '\\', $sep) . '.php';
                $map[$name] = $ourPath;
                CoreLocal::set('ClassLookup', $map);
            }
        } elseif (!isset($map[$name])) {
            // class is unknown
            // rebuild map to see if the definition
            // file has been added
            $map = self::loadMap();
            if (!is_array($map)) {
                return;
            }
        }

        if (isset($map[$name]) && !class_exists($name,false)) {
            $included = include_once($map[$name]);
            if ($included === false) {
                unset($map[$name]);
                CoreLocal::set('ClassLookup', $map);
            }
        }
    }

    /**
      Map available classes. Class names should
      match filenames for lookups to work.
    */
    static public function loadMap()
    {
        $classMap = array();
        $searchPath = realpath(dirname(__FILE__).'/../');
        self::recursiveLoader($searchPath, $classMap);
        CoreLocal::set('ClassLookup', $classMap);

        return $classMap;
    }

    static private $classPaths = array(
        'COREPOS\pos\lib\Scanning\DiscountType' => '/Scanning/DiscountTypes',
        'COREPOS\pos\lib\FooterBoxes\FooterBox' => '/FooterBoxes',
        'COREPOS\pos\lib\Kickers\Kicker' => '/Kickers',
        'COREPOS\pos\lib\Notifier' => '/Notifiers',
        'COREPOS\\pos\\parser\\Parser' => '/../parser/parse',
        'COREPOS\\pos\\parser\\PreParser' => '/../parser/preparse',
        'COREPOS\pos\lib\Scanning\PriceMethod' => '/Scanning/PriceMethods',
        'COREPOS\pos\lib\Scanning\SpecialUPC' => '/Scanning/SpecialUPCs',
        'COREPOS\pos\lib\Scanning\SpecialDept' => '/Scanning/SpecialDepts',
        'COREPOS\pos\lib\Tenders\TenderModule' => '/Tenders',
        'COREPOS\pos\lib\Search\Products\ProductSearch' => '/Search/Products',
        'COREPOS\pos\lib\PrintHandlers\PrintHandler' => '/PrintHandlers',
        'COREPOS\pos\lib\TotalActions\TotalAction'       => '/TotalActions',
        'COREPOS\pos\lib\ReceiptBuilding\TenderReports\TenderReport' => '/ReceiptBuilding/TenderReports',
        'BasicModel'        => '/models',
        'COREPOS\pos\lib\models\BasicModel' => '/models',
        'COREPOS\pos\lib\ReceiptBuilding\DataFetch\DefaultReceiptDataFetch' => '/ReceiptBuilding/DataFetch',
        'COREPOS\pos\lib\ReceiptBuilding\Filter\DefaultReceiptFilter' => '/ReceiptBuilding/Filter',
        'COREPOS\pos\lib\ReceiptBuilding\Sort\DefaultReceiptSort' => '/ReceiptBuilding/Sort',
        'COREPOS\pos\lib\ReceiptBuilding\Tag\DefaultReceiptTag' => '/ReceiptBuilding/Tag',
        'COREPOS\pos\lib\ReceiptBuilding\Savings\DefaultReceiptSavings' => '/ReceiptBuilding/Savings',
        'COREPOS\pos\lib\ReceiptBuilding\ThankYou\DefaultReceiptThanks' => '/ReceiptBuilding/ThankYou',
        'COREPOS\pos\lib\ReceiptBuilding\Messages\ReceiptMessage' => '/ReceiptBuilding/Messages',
        'COREPOS\pos\lib\ReceiptBuilding\CustMessages\CustomerReceiptMessage' => '/ReceiptBuilding/CustMessages',
        'COREPOS\pos\lib\Scanning\VariableWeightReWrite' => '/Scanning/VariableWeightReWrites',
    );

    private static $baseClasses = array(
        'COREPOS\\pos\\lib\\MemberLookup' => '/MemberLookup.php',
        'COREPOS\\pos\\lib\\ItemNotFound' => '/ItemNotFound.php',
    );

    /**
      Get a list of available modules with the
      given base class
      @param $baseClass string class name
      @param $includeBase whether base class should be included
        in the return value
      @return an array of class names
    */
    static public function listModules($baseClass, $includeBase=False)
    {
        $ret = array();
        
        // lookup plugin modules, then standard modules
        $map = Plugin::pluginMap();
        if (isset(self::$classPaths[$baseClass])) {
            $path = realpath(dirname(__FILE__) . self::$classPaths[$baseClass]);
            $map = Plugin::pluginMap($path,$map);
        }
        if (isset(self::$baseClasses[$baseClass])) {
            $path = realpath(dirname(__FILE__) . self::$baseClasses[$baseClass]);
            $map[$baseClass] = $path;

        }

        foreach($map as $name => $file) {

            // matched base class
            if ($name === $baseClass) {
                if ($includeBase) $ret[] = $name;
                continue;
            }
            if (in_array($name, self::$blacklist)) {
                continue;
            }

            if (strstr($file,'plugins')) {
                $parent = Plugin::memberOf($file);
                if ($baseClass !== 'COREPOS\\pos\\plugins\\Plugin' && $parent && !Plugin::isEnabled($parent)) {
                    continue;
                }
            }

            ob_start();
            $nsClass = self::fileToFullClass($file);
            if (!class_exists($name, false) && class_exists($nsClass)) {
                $name = $nsClass;
            } elseif (!class_exists($name)) { 
                ob_end_clean();
                continue;
            }

            if (is_subclass_of($name,$baseClass)) {
                $ret[] = $name;
            } elseif ($nsClass === $baseClass && $includeBase) {
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

    static public function fileToFullClass($file)
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
    // @hintable
    static private function recursiveLoader($path,&$map=array())
    {
        if(!is_dir($path)) {
            return $map;
        }
        
        // skip searching these directories
        // to improve overall performance
        $exclude = array(
            'ajax',
            'ajax-callbacks',
            'css',
            'graphics',
            'gui-modules',
            'install',
            'js',
            'Kickers',
            'locale',
            'log',
            'scale-drivers',
            'models',
            'noauto',
            'PrintHandlers',
            'ReceiptBuilding',
            'test',
        );

        $dir = opendir($path);
        while($dir && ($file=readdir($dir)) !== false) {
            if ($file[0] == ".") continue;

            $fullname = $path . DIRECTORY_SEPARATOR . $file;
            if (is_dir($fullname) && !in_array($file, $exclude)) {
                self::recursiveLoader($fullname, $map);
            } elseif (substr($file,-4) == '.php') {
                $class = substr($file,0,strlen($file)-4);
                $map[$class] = realpath($fullname);
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
            $session = new WrappedStorage();
            $form = new FormValueContainer();
            $page = basename($_SERVER['PHP_SELF']);
            $class = substr($page,0,strlen($page)-4);
            if (CoreLocal::get('CashierNo') !== '' && $class != 'index' && class_exists($class)) {
                $page = new $class($session, $form);
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

COREPOS\common\ErrorHandler::setLogger(new \COREPOS\pos\lib\LaneLogger());
COREPOS\common\ErrorHandler::setErrorHandlers();

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
        bind_textdomain_codeset('pos-nf', 'UTF-8');
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

