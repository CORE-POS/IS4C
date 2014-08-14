<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/**
  @class DataCache

  Store data for later. This is a more generic caching option
  based on FannieReportPage. Data is automatically keyed by
  URL (hint: use GET parameters instead of POST). In theory
  you can cache any serializable data structure. It's typically
  used for caching query results.
*/
class DataCache
{

    /**
      Generate default hash value for caching.
      Uses request URI excluding output formatting options

      @return md5 string
    */
    static public function genKey()
    {
        $hash = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];
        $hash = str_replace("&excel=xls", "", $hash);
        $hash = str_replace("&excel=csv", "", $hash);
        $hash = md5($hash);

        return $hash;
    }

    /**
      Look for cached data
    
      Data is stored in the archive database, reportDataCache table.

      The default key column is an MD5 hash of the current URL (minus the excel
      parameter, if present). This means your forms should use type GET
      if caching is enabled. If a key argument is provided, that key
      is hashed instead.

      The data is stored as a serialized, gzcompressed string.
    */
    static public function check($key=false)
    {
        global $FANNIE_ARCHIVE_DB;
        $dbc = FannieDB::get($FANNIE_ARCHIVE_DB, $current_db);
        $table = $FANNIE_ARCHIVE_DB.$dbc->sep()."reportDataCache";
        $hash = $key ? $key : self::genKey();
        $query = $dbc->prepare_statement("SELECT report_data FROM $table WHERE
            hash_key=? AND expires >= ".$dbc->now());
        $result = $dbc->exec_statement($query,array($hash));
        if (!empty($current_db)) {
            // restore selected database
            $dbc = FannieDB::get($current_db);
        }
        if ($dbc->num_rows($result) > 0) {
            $ret = $dbc->fetch_row($result);
            $serial = gzuncompress($ret[0]);
            if ($serial === false) {
                return false;
            } else {
                return unserialize($serial);
            }
        } else {
            return false;
        }
    }

    /**
      Store data in the cache
      @param $data the data
      @param $ttl how long data is valid. Options are 'day' and 'month'
      @param $key [optional] custom lookup key
      @return True or False based on success

      See check() for details
    */
    static public function freshen($data, $ttl='day', $key)
    {
        global $FANNIE_ARCHIVE_DB;
        $dbc = FannieDB::get($FANNIE_ARCHIVE_DB, $current_db);
        if ($ttl != 'day' && $ttl != 'month') {
            return false;
        }
        $table = $FANNIE_ARCHIVE_DB.$dbc->sep()."reportDataCache";
        $hash = $key ? $key : self::genKey();
        $expires = '';
        if ($ttl == 'day') {
            $expires = date('Y-m-d',mktime(0,0,0,date('n'),date('j')+1,date('Y')));
        } elseif ($ttl == 'month') {
            $expires = date('Y-m-d',mktime(0,0,0,date('n')+1,date('j'),date('Y')));
        }

        $delQ = $dbc->prepare_statement("DELETE FROM $table WHERE hash_key=?");
        $dbc->exec_statement($delQ,array($hash));
        $saveStr = gzcompress(serialize($data));
        $ret = true;
        if (strlen($saveStr) > 65535) {
            // too big to store, probably
            $ret = false;
        } else {
            $upQ = $dbc->prepare_statement("INSERT INTO $table (hash_key, report_data, expires)
                VALUES (?,?,?)");
            $dbc->exec_statement($upQ, array($hash, $saveStr, $expires));
        }

        if (!empty($current_db)) {
            // restore selected database
            $dbc = FannieDB::get($current_db);
        }

        return $ret;
    }

    /**
      Get info from filesystem cache
      @param $ttl [string] daily or monthly
      @param $key [optional] use custom key
      @return cached content or false
    */
    static public function getFile($ttl, $key=false)
    {
        $type = strtolower($ttl);
        if ($type[0] == 'm') {
            $type = 'monthly';
        } else if ($type[0] == 'd') {
            $type = 'daily';
        } else {
            return false;
        }

        $key = ($key !== false) ? md5($key) : self::genKey();

        $cache_dir = self::fileCacheDir($type);
        if ($cache_dir && file_exists($cache_dir . '/' . $key)) {
            return file_get_contents($cache_dir . '/' . $key);
        } else {
            return false;
        }
    }

    /**
      Store info to filesystem cache
      @param $ttl [string] monthly or daily
      @param $content [string] content to cache
      @param $key [optional] custom key
      @return [boolean] true or false
    */
    static public function putFile($ttl, $content, $key=false)
    {
        $type = strtolower($ttl);
        if ($type[0] == 'm') {
            $type = 'monthly';
        } else if ($type[0] == 'd') {
            $type = 'daily';
        } else {
            return false;
        }

        $key = ($key !== false) ? md5($key) : self::genKey();

        $cache_dir = self::fileCacheDir($type);
        if ($cache_dir) {
            $fp = fopen($cache_dir . '/' . $key, 'w');
            fwrite($fp, $content);
            fclose($fp);

            return true;
        } else {
            return false;
        }
    }

    /**
      Get filesystem path for storing cache data
      Auto-creates directories as needed
      @param $type [string] monthly or daily
      @return [string] path or false
    */
    static public function fileCacheDir($type)
    {
        if ($type !== 'monthly' && $type !== 'daily') {
            return false;
        }

        $tmp = sys_get_temp_dir();
        if (!is_dir($tmp . '/fannie_cache/')) {
            if (!mkdir($tmp . '/fannie_cache')) {
                return false;
            }
        }
        if (!is_dir($tmp . '/fannie_cache/' . $type)) {
            if (!mkdir($tmp . '/fannie_cache/' . $type)) {
                return false;
            }
        }

        return realpath($tmp . '/fannie_cache/' . $type);
    }
}

