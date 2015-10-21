<?php

class TestListener extends PHPUnit_Framework_BaseTestListener
{
    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        if ($suite->getName() == "pos") {
            include(dirname(__FILE__) . '/../pos/is4c-nf/unit-tests/bootstrap.php');
        } else {
            var_dump('SUITE: ' . $suite->getName());
        }
    }

    public function startTest(PHPUnit_Framework_Test $test)
    {
        var_dump('TEST: ' . $test->getName());
    }
}

