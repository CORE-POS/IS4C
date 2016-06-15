<?php

namespace COREPOS\pos\install\conf;
use COREPOS\pos\lib\JsonLib;

class JsonConf
{
    /**
      Save entry to ini.json
      This is called by Conf::save() if ini.json exists.
      Should not be called directly
      @param $key string key
      @param $value string value
      @return boolean success
    */
    static public function save($key, $value)
    {
        $ini_json = dirname(__FILE__) . '/../../ini.json';
        if (!is_writable($ini_json)) {
            return false;
        }

        $json = json_decode(file_get_contents($ini_json), true);
        if (!is_array($json)) {
            if (trim(file_get_contents($ini_json)) === '') {
                $json = array();
            } else {
                return false;
            }
        }

        /**
          ini.php expects string delimiters. Take them
          off if present.
        */
        if (strlen($value) >= 2 && substr($value, 0, 1) == "'" && substr($value, -1) == "'") {
            $value = substr($value, 1, strlen($value)-2);
        }

        $json[$key] = $value;
        $saved = file_put_contents($ini_json, JsonLib::prettyJSON(json_encode($json)));

        return ($saved === false) ? false : true;
    }

    /**
      Remove a value from ini.json
      Called by Conf::remove() if ini.json exists.
      Should not be called directly.
      @param $key string key
      @return boolean success
    */
    static public function remove($key)
    {
        $ini_json = dirname(__FILE__) . '/../../ini.json';
        if (!is_writable($ini_json)) {
            return false;
        }

        $json = json_decode(file_get_contents($ini_json), true);
        if (!is_array($json)) {
            return false;
        }

        unset($json[$key]);
        $saved = file_put_contents($ini_json, JsonLib::prettyJSON(json_encode($json)));

        return ($saved === false) ? false : true;
    }

    static public function initWritableFile($fptr)
    {
        fwrite($fptr, \CoreLocal::convertIniPhpToJson());
    }
}

