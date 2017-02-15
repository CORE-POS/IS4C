<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

namespace COREPOS\pos\lib;
use COREPOS\pos\lib\LaneLogger;
use COREPOS\pos\lib\LocalStorage\WrappedStorage;
use \AutoLoader;
use \ReflectionClass;

use COREPOS\common\mvc\FormValueContainer;

/**
  @class AjaxCallback
*/
class AjaxCallback
{
    protected $encoding = 'json';
    protected static $logger;
    protected $session;    
    protected $form;    

    public function __construct($session, $form)
    {
        $this->session = $session;
        $this->form = $form;
    }

    public function getEncoding()
    {
        return $this->encoding;
    }

    public function ajax()
    {
    }

    // @hintable
    public static function unitTest($class)
    {
        self::executeCallback($class);
    }
 
    public static function run()
    {
        register_shutdown_function(array('COREPOS\\pos\\lib\\AjaxCallback', 'ajaxFatal'));
        $callback_class = get_called_class();
        $file = filter_input(INPUT_SERVER, 'SCRIPT_FILENAME');
        $nsClass = AutoLoader::fileToFullClass($file);
        self::$logger = new LaneLogger();
        if ($callback_class === $nsClass || basename($file) === $callback_class . '.php') {
            ini_set('display_errors', 'off');
            /** 
              timing calls is off by default. uncomment start
              and end calls to collect data
            */
            //self::perfStart();
            self::executeCallback($callback_class);
            //self::perfEnd();
        }
    }

    // @hintable
    private static function executeCallback($callback_class)
    {
        $obj = new $callback_class(new WrappedStorage(), new FormValueContainer());
        ob_start();
        $output = $obj->ajax();
        $extra_output = ob_get_clean();
        if (strlen($extra_output) > 0) {
            self::$logger->debug("Extra AJAX output: {$extra_output}");
        }

        switch ($obj->getEncoding()) {
            case 'json':
                echo json_encode($output);
                break;
            case 'plain':
            default:
                echo $output;
                break;
        }
    }

    protected static $elapsed = null;
    protected static function perfStart()
    {
        self::$elapsed = microtime(true); 
    }

    protected static function perfEnd()
    {
        $timer = microtime(true) - self::$elapsed;
        $log = dirname(__FILE__) . '/../log/perf.log';
        $refl = new ReflectionClass(get_called_class());
        $file = basename($refl->getFileName());
        if (self::$elapsed !== null && is_writable($log)) {
            $fptr = fopen($log, 'a');
            fwrite($fptr, $file . "," . $timer . "\n");
            fclose($fptr);
        }
    }

    /**
      Output valid JSON when a fatal error occurs. Logging
      is handled by COREPOS\common\ErrorHandler. This response
      lets calling javascript code notify the user that something
      went wrong.
    */
    public static function ajaxFatal()
    {
        $error = error_get_last();
        if ($error["type"] == E_ERROR 
            || $error['type'] == E_PARSE 
            || $error['type'] == E_CORE_ERROR
            || $error['type'] == E_COMPILE_ERROR) {

            $msg = "{$error['message']} ({$error['file']} line {$error['line']})";
            $json = array('error' => $msg);
            echo json_encode($json);
        }
    }
}

