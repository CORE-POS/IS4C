<?php

namespace COREPOS\pos\install\conf;

use COREPOS\pos\install\conf\PhpConf;
use COREPOS\pos\install\conf\JsonConf;

class Conf
{
    const INI_SETTING   = 1;
    const PARAM_SETTING  = 2;
    const EITHER_SETTING = 3;

    static public function whoami()
    {
        if (function_exists('posix_getpwuid')){
            $chk = posix_getpwuid(posix_getuid());
            return $chk['name'];
        }

        return get_current_user();
    }

    static private function initWritableFile($filename, $template)
    {
        $fptr = fopen($filename,'w');
        if ($fptr) {
            if ($template !== False) {
                switch($template) {
                    case 'PHP':
                        PhpConf::initWritableFile($fptr);
                        break;
                    case 'JSON':
                        JsonConf::initWritableFile($fptr);
                        break;
                }
            }
            fclose($fptr);
        }
    }

    static public function checkWritable($filename, $optional=False, $template=False)
    {
        $basename = basename($filename);
        $failure = ($optional) ? 'blue' : 'red';
        $status = ($optional) ? 'Optional' : 'Warning';

        if (!file_exists($filename) && !$optional) {
            self::initWritableFile($filename, $template);
        }

        $realFile = realpath(dirname($filename)) . DIRECTORY_SEPARATOR . $basename;

        if (!file_exists($filename)) {
            echo "<span style=\"color:$failure;\"><b>$status</b>: $basename " . _('does not exist') . "</span><br />";
            if (!$optional) {
                echo "<b>" . _('Advice') . "</b>: <div style=\"font-face:mono;background:#ccc;padding:8px;\">
                    touch \"". $realFile ."\"<br />
                    chown ".self::whoami()." \"". $realFile ."\"</div>";
            }
        } elseif (is_writable($filename)) {
            echo "<span style=\"color:green;\">$basename " . _('is writeable') . "</span><br />";
        } else {
            echo "<span style=\"color:red;\"><b>" . _('Warning') . "</b>: $basename " . _('is not writeable') . "</span><br />";
            echo "<b>Advice</b>: <div style=\"font-face:mono;background:#ccc;padding:8px;\">
                chown ".self::whoami()." \"". $realFile ."\"<br />
                chmod 600 \"". $realFile ."\"</div>";
        }
    }

    static public function file()
    {
        if (file_exists(__DIR__ . '/../../ini.json')) {
            return 'ini.json';
        } elseif (file_exists(__DIR__ . '/../../ini.php')) {
            return 'ini.php';
        }

        return _('ini file is missing!');
    }

    /**
      Save entry to config file(s)
      @param $key string key
      @param $value string value
      @return boolean success

      Values are written to a file and must be valid
      PHP code. For example, a PHP string should include
      single or double quote delimiters in $value.
    */
    static public function save($key, $value)
    {
        // lane #0 is the server config editor
        // no ini.php file to write values to
        if (\CoreLocal::get('laneno') == 0) {
            return null;
        }

        $iniPhp = dirname(__FILE__) . '/../../ini.php';
        $iniJson = dirname(__FILE__) . '/../../ini.json';
        $savePhp = null;
        $saveJson = null;
        if (file_exists($iniJson)) {
            $saveJson = JsonConf::save($key, $value);
        }
        if (file_exists($iniPhp)) {
            $savePhp = PhpConf::save($key, $value);
        }

        return self::savedEither($savePhp, $saveJson);
    }

    /**
      Remove a value from config file(s)
      @param $key string key
      @return boolean success
    */
    static public function remove($key)
    {
        $iniPhp = dirname(__FILE__) . '/../../ini.php';
        $iniJson = dirname(__FILE__) . '/../../ini.json';
        $savePhp = null;
        $saveJson = null;
        if (file_exists($iniPhp)) {
            $savePhp = PhpConf::remove($key);
        }
        if (file_exists($iniJson)) {
            $saveJson = JsonConf::remove($key);
        }
        
        return self::savedEither($savePhp, $saveJson);
    }

    static private function savedEither($php, $json)
    {
        if ($php === false || $json === false) {
            // error occurred saving
            return false;
        } elseif ($php === null || $json === null) {
            // neither config file found
            return false;
        }

        return true;
    }

    static public function getLocales()
    {
        $path = __DIR__ . '/../../locale';
        $dir = opendir($path);
        $ret = array();
        while (($file=readdir($dir)) !== false) {
            if (file_exists($path . '/' . $file . '/LC_MESSAGES/pos-nf.mo')) {
                $ret[] = $file;
            }
        }

        return empty($ret) ? array('en_US') : $ret;
    }
}

