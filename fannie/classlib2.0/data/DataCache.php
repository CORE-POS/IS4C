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
      Look for cached data
    
      Data is stored in the archive database, reportDataCache table.

      The key column is an MD5 hash of the current URL (minus the excel
      parameter, if present). This means your forms should use type GET
      if caching is enabled.

      The data is stored as a serialized, gzcompressed string.
    */
    static public function check()
    {
        global $FANNIE_ARCHIVE_DB;
        $dbc = FannieDB::get($FANNIE_ARCHIVE_DB);
        $table = $FANNIE_ARCHIVE_DB.$dbc->sep()."reportDataCache";
        $hash = $_SERVER['REQUEST_URI'];
        $hash = str_replace("&excel=xls","",$hash);
        $hash = str_replace("&excel=csv","",$hash);
        $hash = md5($hash);
        $query = $dbc->prepare_statement("SELECT report_data FROM $table WHERE
            hash_key=? AND expires >= ".$dbc->now());
        $result = $dbc->exec_statement($query,array($hash));
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
      @return True or False based on success

      See check() for details
    */
    static public function freshen($data, $ttl='day')
    {
        global $FANNIE_ARCHIVE_DB;
        $dbc = FannieDB::get($FANNIE_ARCHIVE_DB);
        if ($ttl != 'day' && $ttl != 'month') {
            return false;
        }
        $table = $FANNIE_ARCHIVE_DB.$dbc->sep()."reportDataCache";
        $hash = $_SERVER['REQUEST_URI'];
        $hash = str_replace("&excel=xls","",$hash);
        $hash = str_replace("&excel=csv","",$hash);
        $hash = md5($hash);
        $expires = '';
        if ($ttl == 'day') {
            $expires = date('Y-m-d',mktime(0,0,0,date('n'),date('j')+1,date('Y')));
        } elseif ($this->report_cache == 'month') {
            $expires = date('Y-m-d',mktime(0,0,0,date('n')+1,date('j'),date('Y')));
        }

        $delQ = $dbc->prepare_statement("DELETE FROM $table WHERE hash_key=?");
        $dbc->exec_statement($delQ,array($hash));
        $saveStr = gzcompress(serialize($data));
        if (strlen($saveStr) > 65535) {
            // too big to store, probably
            echo "error ".strlen($saveStr);
            return false;
        }
        $upQ = $dbc->prepare_statement("INSERT INTO $table (hash_key, report_data, expires)
            VALUES (?,?,?)");
        $dbc->exec_statement($upQ, array($hash, $saveStr, $expires));

        return true;
    }

}

