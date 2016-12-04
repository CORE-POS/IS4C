<?php

/**
 * @backupGlobals disabled
 */
class PluginsTest extends PHPUnit_Framework_TestCase
{
    public function testAll()
    {
        $path = dirname(__FILE__) . '/../../pos/is4c-nf/plugins';
        $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::CHILD_FIRST, 2);
        // a handful of need to be checked early to avoid duplicate definition
        // since another file includes it
        $first = array('Plugin'=>'', 'xmlData'=>'', 'PaycardLib'=>'', 'PaycardProcessPage'=>'', 'BasicCCModule'=>'', 'quickkey'=>'', 'PaycardRequest'=>'');
        $files = array();
        foreach ($iter as $file) {
            if (is_dir($file)) {
                continue;
            }
            if (substr($file, -4) != '.php') {
                continue;
            }
            $name = basename($file);
            $name = substr($name, 0, strlen($name)-4);
            if (isset($first[$name])) {
                $first[$name] = $file->getPathname();
            } else {
                $files[$file->getPathname()] = $name;
            } 
        }
        foreach (array_merge($first, $files) as $name => $file) {
            if ($name === 'Plugin') {
                continue;
            }
            if (!is_file($file) && is_file($name)) {
                $tmp = $file;
                $file = $name;
                $name = $tmp;
            }
            list(,$path) = explode('plugins', $file, 2);
            $path = ltrim($path, '/');
            $ns_class = 'COREPOS\\pos\\plugins\\';
            foreach (explode('/', $path) as $piece) {
                $ns_class .= $piece . '\\';
            }
            // 5 => .php\
            $ns_class = substr($ns_class, 0, strlen($ns_class)-5);
            if (class_exists($name, false) || class_exists($ns_class, false)) {
                // class already defined
                continue;
            }
            ob_start();
            include($file);
            $output = ob_get_clean();

            $this->assertEquals($output, '', $file . ' is not include-safe');

            if (preg_match('/^\d+$/', $name)) {
                // old-style QuickKey / QuickMenu definitions
                continue;
            } elseif ($name == 'ajax-paycard-auth') {
                // ajax callbacks are not class-based yet
                continue;
            } elseif ($name == 'QuickLookupsModel') {
                continue;
            }

            $provides_class = class_exists($name, false);
            //$this->assertEquals($provides_class, true, 'Missing class definition ' . $name);
        }
    }
}
