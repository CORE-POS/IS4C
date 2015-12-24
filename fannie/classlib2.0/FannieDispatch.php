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

class FannieDispatch 
{

    private static $logger;

    static public function setLogger($l)
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
            /**
              Put fatals in the error log as well as the debug log
              For good measure, put them in STDERR too. Try to
              ensure somebody notices.
            */
            $msg = $error['message']
                . ' Line ' . $error['line']
                . ', File ' . $error['file'];
            self::$logger->error($msg);
            file_put_contents('php://stderr', $msg, FILE_APPEND);
        }
    }

    /**
      Log page load in usageStats table
      @param $dbc [SQLManager] database connection
      @return [boolean] success / fail
    */
    static protected function logUsage(SQLManager $dbc, $op_db)
    {
        if (php_sapi_name() === 'cli') {
            // don't log cli usage
            return false;
        }

        $user = FannieAuth::checkLogin();
        if ($user === false) {
            $user = 'n/a';
        }

        $prep = $dbc->prepare(
            'INSERT INTO usageStats
                (tdate, pageName, referrer, userHash, ipHash)
             VALUES
                (?, ?, ?, ?, ?)');
        $args = array(
            date('Y-m-d H:i:s'),
            basename(filter_input(INPUT_SERVER, 'PHP_SELF')),
        );
        $referrer = isset($_SERVER['HTTP_REFERER']) ? basename($_SERVER['HTTP_REFERER']) : 'n/a';
        $referrer = filter_input(INPUT_SERVER, 'HTTP_REFERER');
        $args[] = $referrer === null ? 'n/a' : basename($referrer);
        $args[] = sha1($user);
        $ip_addr = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
        $args[] = sha1($ip_addr);

        return $dbc->execute($prep, $args);
    }

    /**
      Lookup custom permissions for a page 
    */
    static protected function authOverride(SQLManager $dbc, $op_db, $page_class)
    {
        $prep = $dbc->prepare('
            SELECT authClass
            FROM PagePermissons
            WHERE pageClass=?',
            $op_db);
        $auth = $dbc->getValue($prep, array($page_class), $op_db);

        return $auth ? $auth : false;
    }

    static protected function i18n()
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
        $frames = debug_backtrace();
        // conditionalExec() is the only function on the stack
        if (count($frames) == 1) {
            $config = FannieConfig::factory();
            $logger = new FannieLogger();
            if ($config->get('SYSLOG_SERVER')) {
                $logger->setRemoteSyslog(
                    $config->get('SYSLOG_SERVER'),
                    $config->get('SYSLOG_PORT'),
                    $config->get('SYSLOG_PROTOCOL')
                );
            }
            $op_db = $config->get('OP_DB');
            $dbc = FannieDB::get($op_db);
            self::setLogger($logger);

            // setup error logging
            self::setErrorHandlers();
            // initialize locale & gettext
            self::i18n();

            // draw current page
            $page = basename(filter_input(INPUT_SERVER, 'PHP_SELF'));
            $class = substr($page,0,strlen($page)-4);
            if ($class != 'index' && class_exists($class)) {
                $obj = new $class();
                if ($dbc && $dbc->isConnected($op_db)) {
                    // write URL log
                    self::logUsage($dbc, $op_db);
                    /*
                    $auth = self::authOverride($dbc, $op_db, $class);
                    if ($auth) {
                        $obj->setPermissions($auth);
                    }
                    */
                }
                $obj->setConfig($config);
                $obj->setLogger($logger);
                if (is_a($obj, 'FannieReportPage')) {
                    $dbc = FannieDB::getReadOnly($op_db);
                }
                $obj->setConnection($dbc);
                $obj->draw_page();
            } else {
                trigger_error('Missing class '.$class, E_USER_NOTICE);
            }
        }
    }
}

