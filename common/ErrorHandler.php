<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op

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

namespace COREPOS\common;

/**
  @class ErrorHandler
  Class to register PSR Log class as error/exception/fatal
  handler
*/
class ErrorHandler
{
    private static $logger;

    /**
      The @ error suppression operator is normally
      ignored by logging but if an error REALLY merits
      suppression it can be marked down here.
      Format:
      [filename]-[line#]-[error type]
    */
    private static $ignore = array(
        'MiscLib.php-117-2' => true,
    );

    /**
      Allow code using ErrorHandler to customize
      where warnings can be safely suppressed.
      @param $ignores [array] see format of ErrorHandler::$ignore above
    */
    static public function addIgnores($ignores)
    {
        if (!is_array($ignores)) {
            $ignores = array($ignores);
        }
        foreach ($ignores as $i) {
            self::$ignore[$i] = true;
        }
    }

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
        if (!isset(self::$ignore[basename($errfile) . '-' . $errline . '-' . $errno])) {
            $msg = $errstr . ' Line '
                    . $errline
                    . ', '
                    . $errfile;
            self::$logger->debug($msg);
        }

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
        if ($error["type"] == E_ERROR 
            || $error['type'] == E_PARSE 
            || $error['type'] == E_CORE_ERROR
            || $error['type'] == E_COMPILE_ERROR) {
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

    static public function setErrorHandlers()
    {
        $class = 'COREPOS\common\ErrorHandler';
        set_error_handler(array($class,'errorHandler'));
        set_exception_handler(array($class,'exceptionHandler'));
        register_shutdown_function(array($class,'catchFatal'));
    }
}

