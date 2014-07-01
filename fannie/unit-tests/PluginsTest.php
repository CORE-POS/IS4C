<?php

/**
 * @backupGlobals disabled
 */
class PluginsTest extends PHPUnit_Framework_TestCase
{
    public function testPlugins()
    {
        $plugin_path = dirname(__FILE__) . '/../modules/plugins2.0/';
        $files = FannieAPI::listFiles($plugin_path);
        $functions = get_defined_functions();

        foreach ($files as $file) {
            $file = realpath($file);
            $class_name = substr(basename($file), 0 , strlen(basename($file))-4);
            if (class_exists($class_name, false)) {
                // may have already been included
                $reflect = new ReflectionClass($class_name);
                $this->assertEquals($file, $reflect->getFileName(), 
                        $class_name . ' is defined by ' . $file . ' AND ' . $reflect->getFileName()); 
            } else {
                ob_start();
                include($file);
                $output = ob_get_clean();

                $this->assertEquals('', $output, $file . ' produces output when included');

                $current_functions = get_defined_functions();
                $this->assertEquals(count($functions['user']), count($current_functions['user']), 
                                $file . ' has defined additional functions: ' 
                                . $this->detailedFunctionDiff($current_functions['user'], $functions['user'])
                );

                $classes = get_declared_classes();
                $this->assertContains($class_name, $classes, $file . ' does not define class ' . $class_name);
            }
        }
    }

    private function detailedFunctionDiff($functions1, $functions2)
    {
        $new_functions = array_diff($functions1, $functions2);
        $ret = '';
        foreach ($new_functions as $f) {
            $reflect = new ReflectionFunction($f);
            $ret .= $f . '(' . $reflect->getFileName() . ') '; 
        }

        return $ret;
    }
}

