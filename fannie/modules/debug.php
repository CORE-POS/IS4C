<?php
/**
  This script is for locating broken or problematic PHP files
  that cause crashes when FannieAPI is enumerating classes.

  By default it enumerates all instances of FanniePage. An
  alternate base class can be specified as a command line
  argument.

  Messages that a file cannot be included because a class
  already exists are not errors. They typically indicate
  a depedency between classes where something included earlier
  caused the file to be included already.
*/
include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    ini_set('display_errors', 'on');
    $base_class = isset($argv) && isset($argv[1]) ? $argv[1] : 'FanniePage';
    $files = FannieAPI::listModules($base_class, false, true);
    echo "===============================================================\n";
    echo "INCLUDING FILES\n";
    echo "===============================================================\n";
    for ($i=0; $i<count($files); $i++) {
        echo "Attempting file " . ($i+1) . " " . $files[$i] . "\n";
        $class = basename($files[$i]);
        $class = substr($class, 0, strlen($class)-4);
        $ns_class = FannieAPI::pathToClass($files[$i]);
        if (class_exists($class, false)) {
            echo "\tCannot test include because class $class already exists\n";
        } elseif (class_exists($ns_class, false)) {
            echo "\tCannot test include because class $ns_class already exists\n";
        } else {
            include_once($files[$i]);
        }
    }
    echo "===============================================================\n";
    echo "DONE\n";
    echo "===============================================================\n";
}

