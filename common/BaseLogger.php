<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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
  @class BaseLogger
  Base class defining common logging functionality
  Handles syslog RFC log levels and output format
  Also supports remote syslog.

  Child classes should at minimum override 
  the getLogLocation method to specify a proper
  output file. They may also override the
  verboseDebugging method and program_name property
  if needed.
*/
class BaseLogger
{
    /**
      Map log level integers to
      text level names
    */
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

    /** 
      Remote syslog settings
    */
    protected $syslog_host = false;
    protected $syslog_port = 514;
    protected $syslog_protocol = 'udp';

    /**
      Name of program in log lines
    */
    protected $program_name = 'corepos';

    /**
      Log level constants
    */
    const EMERGENCY     = 0;
    const ALERT         = 1;
    const CRITICAL      = 2;
    const ERROR         = 3;
    const WARNING       = 4;
    const NOTICE        = 5;
    const INFO          = 6;
    const DEBUG         = 7;

    /**
      Normalize log level argument
      @param $level [int or string] log level
      @return [string] log level description
        as lowercase
    */
    protected function normalizeLevel($level)
    {
        if (isset($this->log_level_map[$level])) {
            return $this->log_level_map[$level];
        } else {
            return strtolower($level);
        }
    }

    /**
      Set values for remote syslog
      @param $host [string] host name or IP
      @param $port [int, optional] default 514
      @param $protocol [string, optional] default 'udp'.
        Alternative is 'tcp'.
    */
    public function setRemoteSyslog($host, $port=514, $protocol='udp')
    {
        $this->syslog_host = $host;
        $this->syslog_port = $port;
        $this->syslog_protocol = $protocol;
    }

    protected function getSyslogSocket()
    {
        if (!function_exists('socket_create')) {
            throw new \Exception('Sockets extension required for remote logging');
        }

        $socket = false;
        if (strtolower($this->syslog_protocol) === 'udp') {
            $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        } elseif (strtolower($this->syslog_protocol) === 'tcp') {
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        }
        if ($socket === false) {
            throw new \Exception('Remote logging socket error: ' . socket_last_error());
        }

        return $socket;
    }

    /**
      Send message to remote syslog
      @param $message [string] log line
      @return [boolean] success
    */
    protected function syslogRemote($message)
    {
        $context = array('skip_remote' => true);
        
        try {
            $socket = $this->getSyslogSocket();
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

            socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 5, 'usec' => 0)); 
            if (!socket_connect($socket, $this->syslog_host, $this->syslog_port)) {
                $this->debug('Unable to connect to ' . $this->syslog_host . ':' . $this->syslog_port
                    . ' for remote logging. Error: ' . socket_last_error(), $context);
            }

            socket_write($socket, $message);
            socket_close($socket);
        } catch (\Exception $ex) {
            $this->debug($ex->getMessage(), $context);
        }

        return true;
    }

    /**
      PSR-3 Interface method.
    */
    public function emergency($message, array $context = array())
    {
        $this->writeLog($message, $context, self::EMERGENCY);
    }

    /**
      PSR-3 Interface method.
    */
    public function alert($message, array $context = array())
    {
        $this->writeLog($message, $context, self::ALERT);
    }

    /**
      PSR-3 Interface method.
    */
    public function critical($message, array $context = array())
    {
        $this->writeLog($message, $context, self::CRITICAL);
    }

    /**
      PSR-3 Interface method.
    */
    public function error($message, array $context = array())
    {
        $this->writeLog($message, $context, self::ERROR);
    }

    /**
      PSR-3 Interface method.
    */
    public function warning($message, array $context = array())
    {
        $this->writeLog($message, $context, self::WARNING);
    }

    /**
      PSR-3 Interface method.
    */
    public function notice($message, array $context = array())
    {
        $this->writeLog($message, $context, self::NOTICE);
    }

    /**
      PSR-3 Interface method.
    */
    public function info($message, array $context = array())
    {
        $this->writeLog($message, $context, self::INFO);
    }

    /**
      PSR-3 Interface method.
    */
    public function debug($message, array $context = array())
    {
        $this->writeLog($message, $context, self::DEBUG);
    }

    /**
      PSR-3 Interface method.
    */
    public function log($level, $message, array $context = array())
    {
    }

    /**
      Write a message to log(s)
      @param $message [string] log message
      @param $context [array] optional contextual info
      @param $int_level [int] log level
    */
    private function writeLog($message, array $context, $int_level)
    {
        $file = $this->getLogLocation($int_level);
        $verbose_debug = $this->verboseDebugging();

        /**
          The 'logfile' context value just exists for testing
          purposes. Calling code should not rely on this
          behavior 
        */
        if (isset($context['logfile'])) {
            $file = $context['logfile'];
        }
        if (isset($context['verbose'])) {
            $verbose_debug = true;
        }
        if ($file) {
            $fptr = fopen($file, 'a');
            fwrite($fptr, $this->rfcLogLine($message, $int_level) . "\n");
            if ($this->syslog_host && !isset($context['skip_remote'])) {
                $this->syslogRemote($log_line);
            }
            if ($int_level === self::DEBUG && $verbose_debug) {
                $stack = array();
                if (isset($context['exception']) && $context['exception'] instanceof \Exception) {
                    $stack = $this->stackTrace($context['exception']->getTrace());
                } else {
                    $stack = $this->stackTrace(debug_backtrace());
                }
                foreach ($stack as $frame) {
                    fwrite($fptr, $this->rfcLogLine($frame, $int_level) . "\n");
                }
            }
            fclose($fptr);
        }
    }

    private function rfcLogLine($line, $int_level)
    {
        $date = date('M j H:i:s');
        $host = gethostname() ? gethostname() : 'localhost';
        $pid = getmypid() ? getmypid() : 0;

        return sprintf('%s %s %s[%d]: (%s) %s',
            $date, $host, $this->program_name, $pid,
            $this->log_level_map[$int_level],
            $line);
    }

    /**
      Convert a stack trace array into readable text
      @param $stack [array] stack trace
      @return [array] of log lines
    */
    private function stackTrace($stack)
    {
        $counter = count($stack);
        $lines = array();
        foreach ($stack as $frame) {
            $ret = 'Frame #' . $counter . ' - ';
            $line = isset($frame['line']) ? $frame['line'] : 0;
            $file = isset($frame['file']) ? $frame['file'] : 'Unknown file';
            $args = isset($frame['args']) ? $frame['args'] : array();
            $ret .= 'File ' . $file . ', Line ' . $line 
                . ', function ' . $this->frameToFunction($frame);
            $lines[] = $ret;
            $counter--;
        }

        return $lines;
    }

    private function frameToFunction($frame)
    {
        $function = isset($frame['function']) ? $frame['function'] : 'Unknown function';
        if (isset($frame['class'])) {
            $function = $frame['class'] . '::' . $function;
        }

        return $function;
    }

    /**
      Get filename for log
      @param [integer] log level constant
      @return [string] filename or [boolean] false
    */
    public function getLogLocation($int_level)
    {
        return stristr(PHP_OS, 'WIN') ? 'nul' : '/dev/null';
    }

    /**
      Include stack traces on error logging
      @return [boolean]
    */
    public function verboseDebugging()
    {
        return false;
    }
}

