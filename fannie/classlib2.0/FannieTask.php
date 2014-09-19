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

    protected $error_threshold  = 99;

    const TASK_NO_ERROR         = 0;
    const TASK_TRIVIAL_ERROR    = 1;
    const TASK_SMALL_ERROR      = 2;
    const TASK_MEDIUM_ERROR     = 3;
    const TASK_LARGE_ERROR      = 4;
    const TASK_WORST_ERROR      = 5;

    public function setThreshold($t)
    {
        $this->error_threshold = $t;
    }

    /**
      Implement task functionality here
    */
    public function run()
    {

    }

    /**
      Format message with date information
      and task's class name
      @param $str message string
      @param $severity [optional, default zero] message importance
      @return formatted string
    */
    public function cronMsg($str, $severity=0)
    {
        $info = new ReflectionClass($this);
        $msg = date('r').': '.$info->getName().': '.$str."\n";

        // raise message into stderr
        if ($severity >= $this->error_threshold) {
            file_put_contents('php://stderr', $msg, FILE_APPEND);
        }

        return $msg;
    }
}

if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {

    if ($argc < 2) {
        echo "Usage: php FannieTask.php <Task Class Name>\n";    
        exit;
    }

    include(dirname(__FILE__).'/../config.php');
    include(dirname(__FILE__).'/FannieAPI.php');

    // prepopulate autoloader
    $preload = FannieAPI::listModules('FannieTask');

    $class = $argv[1];
    if (!class_exists($class)) {
        echo "Error: class '$class' does not exist\n";
        exit;
    }

    $obj = new $class();
    if (!is_a($obj, 'FannieTask')) {
        echo "Error: invalid class. Must be subclass of FannieTask\n";
        exit;
    }

    if (isset($FANNIE_TASK_THRESHOLD) && is_numeric($FANNIE_TASK_THRESHOLD)) {
        $obj->setThreshold($FANNIE_TASK_THRESHOLD);
    }

    $obj->run();
}

