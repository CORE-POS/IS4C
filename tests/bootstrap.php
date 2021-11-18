<?php
include(dirname(__FILE__) . '/pos/bootstrap.php');
include(dirname(__FILE__) . '/fannie/bootstrap.php');

// patch in PHPUnit compatibility if needed
if (!class_exists('PHPUnit_Framework_TestCase') && class_exists('PHPUnit\Framework\TestCase')) {
    class PHPUnit_Framework_TestCase extends PHPUnit\Framework\TestCase
    {
        public static function assertInternalType($type, $var, $message='')
        {
            switch (strtolower($type)) {
                case 'object':
                    self::assertIsObject($var, $message);
                    break;
                case 'array':
                    self::assertIsArray($var, $message);
                    break;
                case 'string':
                    self::assertIsStrong($var, $message);
                    break;
                case 'boolean':
                case 'bool':
                    self::assertIsBool($var, $message);
                    break;
                case 'integer':
                case 'int':
                    self::assertIsInt($var, $message);
                    break;
                default:
                    parent::assertInternalType($type, $var, $message);
                    break;
            }
        }
    }
}

