<?php

class WhiteSpaceTest extends PHPUnit_Framework_TestCase
{
	public function testWhiteSpace()
    {
        $ignore = array(
            'ini.php',
        );
        $search = function($path) use (&$search, $ignore) {
            if (is_file($path) && substr($path,-4) == '.php' && !in_array(basename($path), $ignore)) {
                return array($path);
            } elseif (is_dir($path)) {
                $dh = opendir($path);
                $files = array();
                while (($file=readdir($dh)) !== false) {
                    if ($file[0] == '.') {
                        continue;
                    }
                    $dir_files = $search($path . '/' . $file);
                    $files = array_merge($files, $dir_files);
                }
                return $files;
            } else {
                return array();
            }
        };

        $top = realpath(dirname(__FILE__) . '/../');
        $phpfiles = $search($top);

        foreach ($phpfiles as $file) {
            $content = file_get_contents($file);
            $tabs = preg_match('/\\t/', $content);
            $returns = preg_match('/\\r/', $content);
            $this->assertEquals(0, $tabs, $file . ' contains tabs');
            $this->assertEquals(0, $returns, $file . ' contains carriage returns');
        }
    }
}

