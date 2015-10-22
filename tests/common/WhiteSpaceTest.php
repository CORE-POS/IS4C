<?php

class WhiteSpaceTest extends PHPUnit_Framework_TestCase
{
    public function testWhiteSpace()
    {
        $ignore = array(
            'files'=> array(
                'ini.php',
                'config.php',
            ),
            'directories' => array(
                'adodb5',
                'documentation',
                'cc-modules',
                'noauto',
                'vendor',
                'pi_food_net',
            ),
        );
        $count = 0;
        $search = $this->searchMethod($ignore, $count);

        $top = realpath(dirname(__FILE__) . '/../../');
        $phpfiles = $search($top);

        foreach ($phpfiles as $file) {
            $content = file_get_contents($file);
            $tabs = preg_match('/\\t/', $content);
            $returns = preg_match('/\\r/', $content);
            $this->assertEquals(0, $tabs, $file . ' contains tabs');
            $this->assertEquals(0, $returns, $file . ' contains carriage returns');
        }
    }

    // super lazy refactor
    private function searchMethod($ignore, $count)
    {
        $search = function($path) use (&$search, $ignore, $count) {
            if (is_file($path) && substr($path,-4) == '.php' && !in_array(basename($path), $ignore['files'])) {
                return array($path);
            } elseif (is_dir($path)) {
                $dh = opendir($path);
                $files = array();
                while (($file=readdir($dh)) !== false) {
                    if ($file[0] == '.') {
                        continue;
                    }
                    if (is_dir($path . '/' . $file) && in_array($file, $ignore['directories'])) {
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

        return $search;
    }
}

