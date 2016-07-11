<?php

namespace COREPOS\pos\install\conf;

class PhpConf
{
    /**
      Save entry to ini.php
      This is called by Conf::save() if ini.php exists.
      Should not be called directly
      @param $key string key
      @param $value string value
      @return boolean success
    */
    static public function save($key, $value)
    {
        $path_global = __DIR__ . '/../../ini.php';
        $writeable_global = is_writable($path_global);

        if (!$writeable_global) {
            return false;
        }

        $orig_global = file_get_contents($path_global);
        $orig_global = self::addOpenTag($orig_global);

        $written_global = self::rewritePhpSetting($key, $value, $orig_global, $path_global);

        if ($written_global) {
            return true;    // successfully written somewhere relevant
        } else {
            return self::addPhpSetting($key, $value, $orig_global, $path_global);
        }
    }

    static private function addOpenTag($str)
    {
        if (strstr($str,'<?php') === false) {
            $str = "<?php\n" . $str;
        }
        return $str;
    }

    static private function rewritePhpSetting($key, $value, $content, $file)
    {
        $orig_setting = '|\$CORE_LOCAL->set\([\'"]'.$key.'[\'"],\s*(.+)\);[\r\n]|';
        $new_setting = "\$CORE_LOCAL->set('{$key}',{$value}, True);\n";
        $found = false;
        $new = preg_replace($orig_setting, $new_setting, $content,
                    -1, $found);

        if ($found) {
            preg_match($orig_setting, $content, $matches);
            if ($matches[1] === $value.', True') {// found with exact same value
                return true;    // no need to bother rewriting it
            } elseif (is_writable($file)) {
                return file_put_contents($file, $new) ? true : false;
            }
        } else {
            return false;
        }
    }

    static private function addPhpSetting($key, $value, $content, $file)
    {
        $new_setting = "\$CORE_LOCAL->set('{$key}',{$value}, True);\n";
        $found = false;
        $new = preg_replace("|(\?>)?\s*$|", $new_setting.'?>', $content,
                        1, $found);
        if (preg_match('/^<\?php[^\s]/', $new)) {
            $new = preg_replace('/^<\?php/', "<?php\n", $new);
        }

        return file_put_contents($file, $new) ? true : false;
    }

    /**
      Remove a value from ini.php
      Called by Conf::remove() if ini.php exists.
      Should not be called directly.
      @param $key string key
      @return boolean success
    */
    static public function remove($key)
    {
        $path_global = dirname(__FILE__) . '/../../ini.php';
        $ret = false;

        $orig_setting = '|\$CORE_LOCAL->set\([\'"]'.$key.'[\'"],\s*(.+)\);[\r\n]|';
        $file = $path_global;
        $current_conf = file_get_contents($file);
        if (preg_match($orig_setting, $current_conf) == 1) {
            $removed = preg_replace($orig_setting, '', $current_conf);
            $ret = file_put_contents($file, $removed);
        }

        return ($ret === false) ? false : true;
    }

    static public function initWritableFile($fptr)
    {
        fwrite($fptr,"<?php\n");
    }
}

