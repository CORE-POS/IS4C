<?php

/**
 * @backupGlobals disabled
 */
class PluginFannieTest extends PHPUnit_Framework_TestCase
{
    public function testPlugins()
    {
        $dbc = FannieDB::forceReconnect(FannieConfig::config('OP_DB'));
        $plugin_path = dirname(__FILE__) . '/../../fannie/modules/plugins2.0/';
        $first = array('CwReportDataSource'=>'');
        $files = array();
        foreach (FannieAPI::listFiles($plugin_path) as $file) {
            $class = substr(basename($file), 0, strlen(basename($file))-4); 
            if (isset($first[$class])) {
                $first[$class] = $file;
            } else {
                $files[] = $file;
            }
        }
        foreach ($first as $class => $file) {
            array_unshift($files, $file);
        }
        $functions = get_defined_functions();

        $sniffer = null;
        $standard = dirname(__FILE__) . '/CodingStandard/CORE_PSR1/';
        if (getenv('TRAVIS') === false && class_exists('PHP_CodeSniffer')) {
            $sniffer = new PHP_CodeSniffer();
            $sniffer->initStandard($standard);
            $sniffer->cli->setCommandLineValues(array('--report=Json'));
            $sniffer->cli->setCommandLineValues($files);
            $sniffer->processFiles($files);
            ob_start();
            $sniffer->reporting->printReport('Json', true, $sniffer->cli->getCommandLineValues(), null);
            $json = ob_get_clean();
            $json = json_decode($json, true);
            $errors = 0;
            $errorMsg = '';
            $json = $json['files'];
            foreach ($json as $filename => $jsonfile) {
                foreach ($jsonfile['messages'] as $message) {
                    if ($message['type'] == 'ERROR') {
                        $errors++;
                        $errorMsg .= $filename . ': ' . $message['message'] . "\n";
                    } else {
                        echo "Coding Standard Warning: " . $filename . ': ' . $message['message'] . "\n";
                    }
                }
            }
            $this->assertEquals(0, $errors, $errorMsg);
        } else {
            echo "PHP_CodeSniffer is not installed. This test will be less effective.\n";
            echo "Use composer to install it.\n";
        }

        foreach ($files as $file) {
            $file = realpath($file);
            $class_name = substr(basename($file), 0 , strlen(basename($file))-4);
            $namespaced_class_name = FannieAPI::pathToClass($file);
            if (class_exists($class_name, false)) {
                // may have already been included
                $reflect = new ReflectionClass($class_name);
                $this->assertEquals($file, $reflect->getFileName(), 
                        $class_name . ' is defined by ' . $file . ' AND ' . $reflect->getFileName()); 
            } elseif (class_exists($namespaced_class_name, false)) {
                // may have already been included
                $reflect = new ReflectionClass($namespaced_class_name);
                $this->assertEquals($file, $reflect->getFileName(), 
                        $namespaced_class_name . ' is defined by ' . $file . ' AND ' . $reflect->getFileName()); 
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
                $this->assertThat($classes, $this->logicalOr(
                        $this->contains($class_name),
                        $this->contains($namespaced_class_name),
                        $this->contains(ltrim($namespaced_class_name, '\\'))
                    ),
                    $file . ' does not define ' . $class_name . ' or ' . $namespaced_class_name
                );
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

