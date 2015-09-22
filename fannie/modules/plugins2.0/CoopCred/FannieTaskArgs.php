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
/* 5(6)Mar2015 From the upstream development branch, instead of run($args)
*/

/* Needed if this extends FannieTask rather than is FannieTask
 */
if (!class_exists('FannieTask')) {
    include_once(dirname(__FILE__).'/../../../classlib2.0/FannieTask.php');
    // If in $FCL2
    //include_once(dirname(__FILE__).'/FannieTask.php');
}

/**
  @class FannieTaskArgs

  Base class for scheduled tasks
*/
class FannieTaskArgs extends FannieTask
{
    public $name = 'Fannie Task With Args';

}

if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {

    if ($argc < 2) {
        echo "Usage: php FannieTask.php <Task Class Name>\n";    
        exit;
    }

    include(dirname(__FILE__).'/../../../config.php');
    include_once(dirname(__FILE__).'/../../../classlib2.0/FannieAPI.php');
    /* if in $FCL2
    include(dirname(__FILE__).'/../config.php');
    include(dirname(__FILE__).'/FannieAPI.php');
    */

    $config = FannieConfig::factory();
    $logger = new FannieLogger();

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

    $obj->run();
}


