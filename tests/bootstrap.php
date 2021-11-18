<?php
include(dirname(__FILE__) . '/pos/bootstrap.php');
include(dirname(__FILE__) . '/fannie/bootstrap.php');

// patch in PHPUnit compatibility if needed
if (!class_exists('PHPUnit_Framework_TestCase') && class_exists('PHPUnit\Framework\TestCase')) {
    class PHPUnit_Framework_TestCase extends PHPUnit\Framework\TestCase
    {
        public function internalTypeWrapper($type, $var, $message='')
        {
            if (method_exists($this, 'assertIsObject')) {
                switch (strtolower($type)) {
                    case 'object':
                        $this->assertIsObject($var, $message);
                        break;
                    case 'array':
                        $this->assertIsArray($var, $message);
                        break;
                    case 'string':
                        $this->assertIsStrong($var, $message);
                        break;
                    case 'boolean':
                    case 'bool':
                        $this->assertIsBool($var, $message);
                        break;
                    case 'integer':
                    case 'int':
                        $this->assertIsInt($var, $message);
                        break;
                }
            } else {
                $this->assertInternalType($type, $var, $message);
            }
        }
    }
}

