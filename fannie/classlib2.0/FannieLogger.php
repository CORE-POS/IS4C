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
    public function emergency($message, array $context = array())
    {
        $this->writeLog($message, $context);
    }

    public function alert($message, array $context = array())
    {
        $this->writeLog($message, $context);
    }

    public function critical($message, array $context = array())
    {
        $this->writeLog($message, $context);
    }

    public function error($message, array $context = array())
    {
        $this->writeLog($message, $context);
    }

    public function warning($message, array $context = array())
    {
        $this->writeLog($message, $context);
    }

    public function notice($message, array $context = array())
    {
        $this->writeLog($message, $context);
    }

    public function info($message, array $context = array())
    {
        $this->writeLog($message, $context);
    }

    public function debug($message, array $context = array())
    {
        $this->writeLog($message, $context);
    }


    public function log($level, $message, array $context = array())
    {
    }

    private function writeLog($message, array $context = array())
    {
        $file = getLogLocation($file);
        if ($file) {
            $fp = fopen($file, 'a');
            fwrite($fp, date('r') . ': ' . $message . "\n");
            if (isset($context['exception']) && $context['exception'] instanceof Exception) {
                fwrite($fp, $this->stackTrace($context['exception']->getTrace()));
                fwrite($fp, "\n");
            } else {
                fwrite($fp, $this->stackTrace(debug_backtrace()));
                fwrite($fp, "\n");
            }
            fclose($fp);
        }
    }

    /**
      Get filename for log
      @return [string] filename or [boolean] false
    */
    public function getLogLocation()
    {
        $default = ini_get('error_log');
        if (is_writable($default)) {
            return realpath($default);
        }

        $temp = sys_get_temp_dir() . '/fannie.log';
        if (is_writable($temp)) {
            return realpath($temp);
        }

        return false;
    }

    private function printStack($stack)
    {
        $ret = '--Stacktrace: ' . "\n";
        $i = 1;
        foreach ($stack as $frame) {
            $ret .= str_repeat('-', $i*2) . 'Frame #' . $i . "\n";
            $line = isset($frame['line']) ? $frame['line'] : 0;
            $file = isset($frame['file']) ? $frame['file'] : 'Unknown file';
            $args = isset($frame['args']) ? $frame['args'] : array();
            $function = isset($frame['function']) ? $frame['function'] : 'Unknown function';
            if (isset($frame['class'])) {
                $function = $frame['class'] . '::' . $function;
            }
            $ret .= str_repeat('-', ($i*2)+1) . 'File ' . $file . ', Line ' . $line 
                . ', function ' . $function . "\n";
            $ret .= str_repeat('-', ($i*2)+1) . 'Args:';
            foreach ($args as $a) {
                $ret .= $a . ' ';
            }
            $ret .= "\n";
            $i++;
        }
    }
}

if (interface_exists('\Psr\Log\LoggerInterface')) {
    class FannieLogger extends FannieBaseLogger implements \Psr\Log\LoggerInterface 
    {
        public function log($level, $message, array $context = array())
        {
            switch ($level) {
                case \Psr\Log\EMERGENCY:
                    $this->emergency($message, $context);
                    break;
                case \Psr\Log\ALERT:
                    $this->alert($message, $context);
                    break;
                case \Psr\Log\CRITICAL:
                    $this->critical($message, $context);
                    break;
                case \Psr\Log\ERROR:
                    $this->error($message, $context);
                    break;
                case \Psr\Log\WARNING:
                    $this->warning($message, $context);
                    break;
                case \Psr\Log\NOTICE:
                    $this->notice($message, $context);
                    break;
                case \Psr\Log\INFO:
                    $this->info($message, $context);
                    break;
                case \Psr\Log\DEBUG:
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
            switch ($level) {
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
