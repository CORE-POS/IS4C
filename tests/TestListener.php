<?php

class TestListener extends PHPUnit_Framework_BaseTestListener
{
    public function startTestSuit(PHPUnit_Framework_TestSuite $suite)
    {
        if ($suite->getName() == "pos") {
            var_dump("re-bootstrap");
            include(dirname(__FILE__) . '/../pos/is4c-nf/unit-tests/bootstrap.php');
        } else {
            var_dump($suite->getName());
        }
    }
}

