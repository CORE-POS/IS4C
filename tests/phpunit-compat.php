<?php

class PHPUnit_Framework_TestCase extends PHPUnit\Framework\TestCase
{
    public static function assertInternalType(string $type, $var, string $message=''): void
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
