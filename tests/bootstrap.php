<?php
include(dirname(__FILE__) . '/pos/bootstrap.php');
include(dirname(__FILE__) . '/fannie/bootstrap.php');

// patch in PHPUnit compatibility
if (class_exists('PHPUnit\Framework\TestCase')) {
    class PHPUnit_Framework_TestCase extends PHPUnit\Framework\TestCase
    {
    }
}

