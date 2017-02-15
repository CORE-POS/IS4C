<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

/**
  Logging class that matches the spec for PSR-3 LoggerInterface
  If the actual interface definition is available via composer
  FannieLogger formally implements it; otherwise it just contains
  the same public methods. Either way the actual functionality
  is inherited from FannieBaseLogger.
*/
class FannieLogger extends \COREPOS\common\Logger
{
    protected $programName = 'fannie';

    /**
      Get filename for log
      @param [integer] log level constant
      @return [string] filename or [boolean] false
    */
    public function getLogLocation($int_level)
    {
        $filename = 'fannie.log';
        if ($int_level == self::DEBUG) {
            $filename = 'debug_fannie.log';
        }
        // if the logs directory is not writable, try
        // failing over to /tmp
        $dir = dirname(__FILE__) . '/../logs/';
        if (!is_writable($dir .  $filename)) {
            $dir = sys_get_temp_dir() . '/';
        }

        return $dir . $filename;
    }

    public function verboseDebugging()
    {
        if (class_exists('FannieConfig') && FannieConfig::config('CUSTOM_ERRORS') >= 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
      If monolog is present, use monolog instead of FannieLogger.
      When using monolog file based logging to fannie/logs/fannie.log
      is enabled automatically. If a fannie/logs/monolog.php file is
      present that file is included here. Its purpose is to add any
      additional, custom handlers.
    */
    public static function factory()
    {
        if (!class_exists('Monolog\\Logger')) {
            return new self();
        }

        try {
            $monolog = new Monolog\Logger('fannie');
            $file = __DIR__ . '/../logs/fannie.log';
            $stream = new Monolog\Handler\StreamHandler($file);
            $monolog->pushHandler($stream);
            if (file_exists(__DIR__ . '/../logs/monolog.php')) {
                include(__DIR__ . '/../logs/monolog.php');
            }

            return $monolog;

        } catch (Exception $ex) {
            return new self();
        }
    }
}

