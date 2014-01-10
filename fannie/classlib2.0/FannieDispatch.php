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

    /**
      Helper: get output-appropriate newline
    */
    static private function nl()
    {
        if (php_sapi_name() == 'cli') { 
            return "\n";
        } else {
            return "<br />";
        }
    }
    
    /**
      Helper: get output-appropriate tab
    */
    static private function tab()
    {
        if (php_sapi_name() == 'cli') {
            return "\t";
        } else {
            return "<li>";
        }
    }

    /**
      Helper: tabs in html are implemented with <li> tags
      but the first block of a given indentation level
      needs a <ul> tag
    */
    static private function indent()
    {
        if (php_sapi_name() == 'cli') {
            return "";
        } else {
            return "<ul>";
        }
    }

    /**
      Helper: reverse of indent()
    */
    static private function outdent()
    {
        if (php_sapi_name() == 'cli') {
            return "";
        } else {
            return "</ul>";
        }
    }


    /**
      Error handler function. Can register as PHP's error
      handling function and use Fannie's output format
    */
    static public function errorHandler($errno, $errstr, $errfile='', $errline=0, $errcontext=array())
    {
        echo $errstr.' Line '.$errline.', '.$errfile.self::nl();
        self::printStack(debug_backtrace());

        return true;
    }

    /**
      Exception handler function. Can register as PHP's exception
      handling function and use Fannie's output format
    */
    static public function exceptionHandler($exception)
    {
        echo $exception->getMessage()." Line ".$exception->getLine().", ".$exception->getFile().self::nl();
        self::printStack($exception->getTrace());
    }
    
    /**
      Print entire call stack
      @param $stack [array] current call stack
    */
    static public function printStack($stack)
    {
        echo "STACK:".self::nl();
        $i = 1;
        foreach($stack as $frame) {
            if (!isset($frame['line'])) $frame['line']=0;
            if (!isset($frame['file'])) $frame['file']='File not given';
            if (!isset($frame['args'])) $frame['args'] =array();
            if (isset($frame['class'])) $frame['function'] = $frame['class'].'::'.$frame['function'];
            echo self::indent();
            echo "Frame $i".self::nl();
            echo self::tab().$frame['function'].'(';
            $args = '';
            foreach($frame['args'] as $arg) {
                $args .= $arg.', ';
            }
            $args = rtrim($args);
            $args = rtrim($args,',');
            echo $args.')'.self::nl();
            echo self::tab().'Line '.$frame['line'].', '.$frame['file'].self::nl();
            $i++;
        }
        for ($j=0; $j < ($i-1); $j++) {
            echo self::outdent();
        }
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
      @return [boolean] success / fail
    */
    static public function logUsage()
    {
        global $FANNIE_OP_DB;

        if (php_sapi_name() === 'cli') {
            // don't log cli usage
            return false;
        }

        $dbc = FannieDB::get($FANNIE_OP_DB);
        if (!$dbc || !isset($dbc->connections[$FANNIE_OP_DB]) || $dbc->connections[$FANNIE_OP_DB] == false) {
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

    /**
      Render the current page if appropriate
      The page is only shown if it's accessed
      directly rather than through an include().
    */
    static public function go()
    {
        $bt = debug_backtrace();
        // go() is the only function on the stack
        if (count($bt) == 1) {
    
            set_error_handler(array('FannieDispatch','errorHandler'));
            set_exception_handler(array('FannieDispatch','exceptionHandler'));
            register_shutdown_function(array('FannieDispatch','catchFatal'));

            self::logUsage();

            $page = basename($_SERVER['PHP_SELF']);
            $class = substr($page,0,strlen($page)-4);
            if (class_exists($class)) {
                $obj = new $class();
                $obj->draw_page();
            } else {
                trigger_error('Missing class '.$class, E_USER_NOTICE);
            }
        }
    }

    /**
      Render the current page if appropriate
      The page is only shown if it's accessed
      directly rather than through an include().

      @param $custom_errors [boolean] use built-in error handlers
    */
    static public function conditionalExec($custom_errors=true)
    {
        $bt = debug_backtrace();
        // conditionalExec() is the only function on the stack
        if (count($bt) == 1) {
    
            if ($custom_errors) {
                set_error_handler(array('FannieDispatch','errorHandler'));
                set_exception_handler(array('FannieDispatch','exceptionHandler'));
                register_shutdown_function(array('FannieDispatch','catchFatal'));
            }

            self::logUsage();

            $page = basename($_SERVER['PHP_SELF']);
            $class = substr($page,0,strlen($page)-4);
            if (class_exists($class)) {
                $obj = new $class();
                $obj->draw_page();
            } else {
                trigger_error('Missing class '.$class, E_USER_NOTICE);
            }
        }
    }
}

