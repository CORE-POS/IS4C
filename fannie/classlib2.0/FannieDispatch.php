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

class FannieDispatch 
{

    private static $logger;

    static private function setLogger($l)
    {
        self::$logger = $l;
    }

    /**
      Error handler function. Can register as PHP's error
      handling function and use Fannie's output format
    */
    static public function errorHandler($errno, $errstr, $errfile='', $errline=0, $errcontext=array())
    {
        $msg = $errstr . ' Line '
                . $errline
                . ', '
                . $errfile;
        self::$logger->debug($msg);

        return true;
    }

    /**
      Exception handler function. Can register as PHP's exception
      handling function and use Fannie's output format
    */
    static public function exceptionHandler($exception)
    {
        $msg = $exception->getMessage()
                . " Line "
                . $exception->getLine()
                . ", "
                . $exception->getFile();
        self::$logger->debug($msg);
    }
    
    /**
      Try to print a call stack on fatal errors
      if the environment / configuration permits
    */
    static public function catchFatal()
    {
        $error = error_get_last();
        if ($error["type"] == E_ERROR) {
            self::errorHandler($error["type"], $error["message"], $error["file"], $error["line"]);
        }
    }

    /**
      Log page load in usageStats table
      @param $dbc [SQLManager] database connection
      @return [boolean] success / fail
    */
    static public function logUsage(SQLManager $dbc, $op_db)
    {
        if (php_sapi_name() === 'cli') {
            // don't log cli usage
            return false;
        }

        if (!$dbc || !isset($dbc->connections[$op_db]) || $dbc->connections[$op_db] == false) {
            // database unavailable
            return false;
        }

        $user = FannieAuth::checkLogin();
        if ($user === false) {
            $user = 'n/a';
        }

        $model = new UsageStatsModel($dbc);
        $model->tdate(date('Y-m-d H:i:s'));
        $model->pageName(basename($_SERVER['PHP_SELF']));
        $referrer = isset($_SERVER['HTTP_REFERER']) ? basename($_SERVER['HTTP_REFERER']) : 'n/a';
        $model->referrer($referrer);
        $model->userHash(sha1($user));
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'n/a';
        $model->ipHash(sha1($ip));
        
        return $model->save();
    }

    static public function i18n()
    {
        if (function_exists('bindtextdomain') && defined('LC_MESSAGES')) {
            setlocale(LC_MESSAGES, "en_US");
            bindtextdomain('messages', realpath(dirname(__FILE__).'/../locale'));
            bind_textdomain_codeset('messages', 'UTF-8');
            textdomain('messages');
        }
    }

    static public function setErrorHandlers()
    {
        set_error_handler(array('FannieDispatch','errorHandler'));
        set_exception_handler(array('FannieDispatch','exceptionHandler'));
        register_shutdown_function(array('FannieDispatch','catchFatal'));
    }

    /**
      Render the current page if appropriate
      The page is only shown if it's accessed
      directly rather than through an include().

      @param $custom_errors @deprecated
        This behavior is controlled by config variable
        FANNIE_CUSTOM_ERRORS. The optional parameter
        remains for th sake of compatibility but does
        not do anything. It will go away when all calls
        to this method have been cleaned up.
    */
    static public function conditionalExec($custom_errors=true)
    {
        $bt = debug_backtrace();
        // conditionalExec() is the only function on the stack
        if (count($bt) == 1) {
            $config = FannieConfig::factory();
            $logger = new FannieLogger();
            $op_db = $config->get('OP_DB');
            $dbc = FannieDB::get($op_db);
            self::setLogger($logger);

            // setup error logging
            self::setErrorHandlers();
            // initialize locale & gettext
            self::i18n();
            // write URL log
            self::logUsage($dbc, $op_db);

            // draw current page
            $page = basename($_SERVER['PHP_SELF']);
            $class = substr($page,0,strlen($page)-4);
            if ($class != 'index' && class_exists($class)) {
                $obj = new $class();
                $obj->setConfig($config);
                $obj->setLogger($logger);
                $obj->setConnection($dbc);
                $obj->draw_page();
            } else {
                trigger_error('Missing class '.$class, E_USER_NOTICE);
            }
        }
    }
}

