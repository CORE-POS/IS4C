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

    static private function nl()
    {
        if (php_sapi_name() == 'cli') { 
            return "\n";
        } else {
            return "<br />";
        }
    }
    
    static private function tab()
    {
        if (php_sapi_name() == 'cli') {
            return "\t";
        } else {
            return "<li>";
        }
    }

    static public function errorHandler($errno, $errstr, $errfile='', $errline=0, $errcontext=array())
    {
        echo $errstr.' Line '.$errline.', '.$errfile.self::nl();
        self::printStack(debug_backtrace());
        return true;
    }

    static public function exceptionHandler($exception)
    {
        echo $exception->getMessage()." Line ".$exception->getLine().", ".$exception->getFile().self::nl();
        self::printStack($exception->getTrace());
    }
    
    static public function printStack($stack)
    {
        echo "STACK:".self::nl();
        $i = 1;
        foreach($stack as $frame) {
            if (!isset($frame['line'])) $frame['line']=0;
            if (!isset($frame['file'])) $frame['file']='File not given';
            if (!isset($frame['args'])) $frame['args'] =array();
            if (isset($frame['class'])) $frame['function'] = $frame['class'].'::'.$frame['function'];
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

    }

    static public function catchFatal()
    {
        $error = error_get_last();
        if ($error["type"] == E_ERROR) {
            self::errorHandler($error["type"], $error["message"], $error["file"], $error["line"]);
        }
    }

    static public function go()
    {
        $bt = debug_backtrace();
        if (count($bt) == 1) {
    
            set_error_handler(array('FannieDispatch','errorHandler'));
            set_exception_handler(array('FannieDispatch','exceptionHandler'));
            register_shutdown_function(array('FannieDispatch','catchFatal'));

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

