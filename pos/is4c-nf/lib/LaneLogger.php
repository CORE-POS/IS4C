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

namespace COREPOS\pos\lib;

/**
  Logging class that matches the spec for PSR-3 LoggerInterface
  If the actual interface definition is available via composer
  FannieLogger formally implements it; otherwise it just contains
  the same public methods. Either way the actual functionality
  is inherited from FannieBaseLogger.
*/
class LaneLogger extends \COREPOS\common\Logger
{
    protected $program_name = 'lane';

    /**
      Get filename for log
      @param [integer] log level constant
      @return [string] filename or [boolean] false
    */
    public function getLogLocation($int_level)
    {
        $filename = 'lane.log';
        if ($int_level == self::DEBUG) {
            $filename = 'debug_lane.log';
        }
        // if the logs directory is not writable, try
        // failing over to /tmp
        $dir = dirname(__FILE__) . '/../log/';
        if (!$this->validateLocation($dir, $filename)) {
            $dir = sys_get_temp_dir() . '/';
        }

        return $dir . $filename;
    }

    private function validateLocation($directory, $filename)
    {
        if (file_exists($directory . $filename) && is_writable($directory . $filename)) {
            return true;
        } elseif (!file_exists($directory . $filename) && is_writable($directory)) {
            return true;
        }

        return false;
    }

    public function isLogging()
    {
        $dir = __DIR__ . '/../log/';

        return $this->validateLocation($dir, 'lane.log') && $this->validateLocation($dir, 'debug_lane.log');
    }
}

