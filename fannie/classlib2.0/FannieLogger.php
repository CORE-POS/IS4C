<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

/**
  Logging class that matches the spec for PSR-3 LoggerInterface
  If the actual interface definition is available via composer
  FannieLogger formally implements it; otherwise it just contains
  the same public methods. Either way the actual functionality
  is inherited from FannieBaseLogger.

  This is *not* in use yet. Just planning ahead to be more 
  in-line with modern standards and best practices.
*/
class FannieBaseLogger 
{

    protected $log_level_map  = array(
        0 => 'emergency',
        1 => 'alert',
        2 => 'critical',
        3 => 'error',
        4 => 'warning',
        5 => 'notice',
        6 => 'info',
        7 => 'debug',
    );

    const EMERGENCY     = 0;
    const ALERT         = 1;
    const CRITICAL      = 2;
    const ERROR         = 3;
    const WARNING       = 4;
    const NOTICE        = 5;
    const INFO          = 6;
    const DEBUG         = 7;

    protected function normalizeLevel($level)
    {
        if (isset($this->log_level_map[$level])) {
            return $this->log_level_map[$level];
        } else {
            return strtolower($level);
        }
    }

    public function emergency($message, array $context = array())
    {
        $this->writeLog($message, $context, self::EMERGENCY);
    }

    public function alert($message, array $context = array())
    {
        $this->writeLog($message, $context, self::ALERT);
    }

    public function critical($message, array $context = array())
    {
        $this->writeLog($message, $context, self::CRITICAL);
    }

    public function error($message, array $context = array())
    {
        $this->writeLog($message, $context, self::ERROR);
    }

    public function warning($message, array $context = array())
    {
        $this->writeLog($message, $context, self::WARNING);
    }

    public function notice($message, array $context = array())
    {
        $this->writeLog($message, $context, self::NOTICE);
    }

    public function info($message, array $context = array())
    {
        $this->writeLog($message, $context, self::INFO);
    }

    public function debug($message, array $context = array())
    {
        $this->writeLog($message, $context, self::DEBUG);
    }


    public function log($level, $message, array $context = array())
    {
    }

    private function writeLog($message, array $context, $int_level)
    {
        $file = $this->getLogLocation($int_level);
        $verbose_debug = false;
        if (class_exists('FannieConfig') && FannieConfig::config('CUSTOM_ERRORS') >= 1) {
            $verbose_debug = true;
        }

        /**
          The 'logfile' context value just exists for testing
          purposes. Calling code should not rely on this
          behavior 
        */
        if (isset($context['logfile'])) {
            $file = $context['logfile'];
        }
        if ($file) {
            $date = date('M j H:i:s');
            $host = gethostname();
            if ($host === false) {
                $host = 'localhost';
            }
            $pid = getmypid();
            if ($pid === false) {
                $pid = 0;
            }
            $tag = 'fannie[' . $pid . ']';
            $fp = fopen($file, 'a');
            $log_line = sprintf('%s %s fannie[%d]: (%s) %s',
                $date, $host, $pid,
                $this->log_level_map[$int_level],
                $message);
            fwrite($fp, $log_line . "\n");
            if ($int_level === self::DEBUG && $verbose_debug) {
                $stack = array();
                if (isset($context['exception']) && $context['exception'] instanceof Exception) {
                    $stack = $this->stackTrace($context['exception']->getTrace());
                } else {
                    $stack = $this->stackTrace(debug_backtrace());
                }
                foreach ($stack as $frame) {
                    $log_line = sprintf('%s %s fannie[%d]: (%s) %s',
                        $date, $host, $pid,
                        $this->log_level_map[$int_level],
                        $frame);
                    fwrite($fp, $log_line . "\n");
                }
            }
            fclose($fp);
        }
    }

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

    private function stackTrace($stack)
    {
        $i = count($stack);
        $lines = array();
        foreach ($stack as $frame) {
            $ret = 'Frame #' . $i . ' - ';
            $line = isset($frame['line']) ? $frame['line'] : 0;
            $file = isset($frame['file']) ? $frame['file'] : 'Unknown file';
            $args = isset($frame['args']) ? $frame['args'] : array();
            $function = isset($frame['function']) ? $frame['function'] : 'Unknown function';
            if (isset($frame['class'])) {
                $function = $frame['class'] . '::' . $function;
            }
            $ret .= 'File ' . $file . ', Line ' . $line 
                . ', function ' . $function;
            $lines[] = $ret;
            $i--;
        }

        return $lines;
    }
}

if (interface_exists('\Psr\Log\LoggerInterface')) {
    class FannieLogger extends FannieBaseLogger implements \Psr\Log\LoggerInterface 
    {
        public function log($level, $message, array $context = array())
        {
            switch ($this->normalizeLevel($level)) {
                case \Psr\Log\LogLevel::EMERGENCY:
                    $this->emergency($message, $context);
                    break;
                case \Psr\Log\LogLevel::ALERT:
                    $this->alert($message, $context);
                    break;
                case \Psr\Log\LogLevel::CRITICAL:
                    $this->critical($message, $context);
                    break;
                case \Psr\Log\LogLevel::ERROR:
                    $this->error($message, $context);
                    break;
                case \Psr\Log\LogLevel::WARNING:
                    $this->warning($message, $context);
                    break;
                case \Psr\Log\LogLevel::NOTICE:
                    $this->notice($message, $context);
                    break;
                case \Psr\Log\LogLevel::INFO:
                    $this->info($message, $context);
                    break;
                case \Psr\Log\LogLevel::DEBUG:
                    $this->debug($message, $context);
                    break;
            }
        }
    }
} else {
    class FannieLogger extends FannieBaseLogger 
    {
        public function log($level, $message, array $context = array())
        {
            switch ($this->normalizeLevel($level)) {
                case 'emergency':
                    $this->emergency($message, $context);
                    break;
                case 'alert':
                    $this->alert($message, $context);
                    break;
                case 'critical':
                    $this->critical($message, $context);
                    break;
                case 'error':
                    $this->error($message, $context);
                    break;
                case 'warning':
                    $this->warning($message, $context);
                    break;
                case 'notice':
                    $this->notice($message, $context);
                    break;
                case 'info':
                    $this->info($message, $context);
                    break;
                case 'debug':
                    $this->debug($message, $context);
                    break;
            }
        }
    }
}
