<?php
include(dirname(__FILE__) . '/pos/bootstrap.php');
include(dirname(__FILE__) . '/fannie/bootstrap.php');

// patch in PHPUnit compatibility if needed
if (!class_exists('PHPUnit_Framework_TestCase') && class_exists('PHPUnit\Framework\TestCase')) {
    include(__DIR__ . '/phpunit-compat.php');
}

