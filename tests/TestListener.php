<?php

class TestListener extends PHPUnit_Framework_BaseTestListener
{
    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        if ($suite->getName() == "InstallTest") {
            include(dirname(__FILE__) . '/../pos/is4c-nf/unit-tests/bootstrap.php');
        }
    }
}

