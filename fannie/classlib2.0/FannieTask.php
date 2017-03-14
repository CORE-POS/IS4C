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

/**
  @class FannieTask

  Base class for scheduled tasks
*/
class FannieTask 
{
    public $name = 'Fannie Task';

    public $description = 'Information about the task';    

    public $default_schedule = array(
        'min' => 0,
        'hour' => 0,
        'day' => 1,
        'month' => 1,
        'weekday' => '*',
    );

    public $schedulable = true;

    /**
      Normally the start and stop time of a task is
      automatically logged. This can be helpful to
      verify a task a) actually ran and b) completed
      without crashing. However for tasks that run
      very frequently this can generate excess log
      noise.
    */
    public $log_start_stop = true;

    protected $error_threshold  = 99;

    const TASK_NO_ERROR         = 0;
    const TASK_TRIVIAL_ERROR    = 1;
    const TASK_SMALL_ERROR      = 2;
    const TASK_MEDIUM_ERROR     = 3;
    const TASK_LARGE_ERROR      = 4;
    const TASK_WORST_ERROR      = 5;

    protected $config = null;

    protected $logger = null;

    protected $options = array();
    protected $arguments = array();
    protected $test_mode = false;

    public function setThreshold($t)
    {
        $this->error_threshold = $t;
    }

    public function setConfig(FannieConfig $fc)
    {
        $this->config = $fc;
    }

    public function setLogger($fl)
    {
        $this->logger = $fl;
    }

    public function setOptions($o)
    {
        $this->options = $o;
    }

    public function setArguments($a)
    {
        $this->arguments = $a;
    }

    public function testMode($t)
    {
        $this->test_mode = $t;
    }

    /**
      Implement task functionality here
    */
    public function run()
    {

    }

    private function psrSeverity($s)
    {
        switch($s) {
            case 0:
                return 'emergency';
            case 1:
                return 'alert';
            case 2:
                return 'critical';
            case 3:
                return 'error';
            case 4:
                return 'warning';
            case 5:
                return 'notice';
            case 6:
                return 'info';
            case 7:
            default:
                return 'debug';
        }
    }

    /**
      Write message to log and if necessary raise it to stderr
      to trigger an email
      @param $str message string
      @param $severity [optional, default 6/info] message importance
      @return empty string
    */
    public function cronMsg($str, $severity=6)
    {
        $info = new ReflectionClass($this);
        $msg = date('r').': '.$info->getName().': '.$str."\n";
        $log_level = $this->psrSeverity($severity);

        $this->logger->log($log_level, $info->getName() . ': ' . $str); 

        // raise message into stderr
        if ($severity <= $this->error_threshold) {
            file_put_contents('php://stderr', $msg, FILE_APPEND);
        }

        return '';
    }

    /**
      getopt style parsing. not fully posix compliant.
      @param $argv [array] of options and arguments
      @return [array]
        - options [array] of option names and values
        - arguments [array] of non-option arguments

      Example:
      php FannieTask.php SomeTask -v --verbose -h 1 --host=1 something else

      lazyGetOpt returns
        - options
          "-v" => true
          "--verbose" => true
          "-h" => 1
          "--host" => 1
        - arguments
          0 => "something"
          1 => "else"
    */
    public function lazyGetOpt($argv)
    {
        $options = array();
        $nonopt = array();

        for ($i=0; $i<count($argv); $i++) {
            $arg = $argv[$i];
            if ($this->isValueOption($arg)) {
                $options[$this->getOptionName($arg)] = $this->getOptionValue($arg);
            } elseif ($this->isBareOption($arg)) {
                if ($i+1 < count($argv) && substr($argv[$i+1],0,1) != '-') {
                    $options[$this->getOptionName($arg)] = $argv[$i+1];
                    $i++;
                } else {
                    $options[$this->getOptionName($arg)] = true;
                }
            } else {
                $nonopt[] = $arg;
            }
        }

        return array(
            'options' => $options,
            'arguments' => $nonopt,
        );
    }

    private function isBareOption($opt)
    {
        return preg_match('/^-\w$/', $opt) || preg_match('/^--\w+$/', $opt);
    }

    private function isValueOption($opt)
    {
        return preg_match('/^-\w=.+$/', $opt) || preg_match('/^--\w+=.+$/', $opt);
    }

    private function getOptionName($opt)
    {
        $opt = ltrim($opt, '-');
        $parts = explode('=', $opt, 2);

        return $parts[0];
    }

    private function getOptionValue($opt)
    {
        $parts = explode('=', $opt, 2);

        return $parts[1];
    }
}

if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {

    if ($argc < 2) {
        echo "Usage: php FannieTask.php <Task Class Name>\n";    
        return 1;
    }

    include(dirname(__FILE__).'/../config.php');
    include(dirname(__FILE__).'/FannieAPI.php');

    $config = FannieConfig::factory();
    $logger = FannieLogger::factory();
    COREPOS\common\ErrorHandler::setLogger($logger);
    COREPOS\common\ErrorHandler::setErrorHandlers();

    // prepopulate autoloader
    $preload = FannieAPI::listModules('FannieTask');

    $class = $argv[1];
    if (!class_exists($class)) {
        echo "Error: class '$class' does not exist\n";
        return 1;
    }

    $obj = new $class();
    if (!is_a($obj, 'FannieTask')) {
        echo "Error: invalid class. Must be subclass of FannieTask\n";
        return 1;
    }

    if (is_numeric($config->get('TASK_THRESHOLD'))) {
        $obj->setThreshold($config->get('TASK_THRESHOLD'));
    }
    $obj->setConfig($config);
    $obj->setLogger($logger);

    /**
      Parse & set extra options and arguments
    */
    if ($argc > 2) {
        $remainder = array_slice($argv, 2);
        $parsed = $obj->lazyGetOpt($remainder);
        $obj->setOptions($parsed['options']);
        $obj->setArguments($parsed['arguments']);
    }

    if ($obj->log_start_stop) {
        $logger->info('Starting task: ' . $class);
    }
    $obj->run();
    if ($obj->log_start_stop) {
        $logger->info('Finished task: ' . $class);
    }
}

