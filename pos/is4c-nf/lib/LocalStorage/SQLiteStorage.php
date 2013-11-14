<?php

if (!class_exists("LocalStorage")) {
    include_once($_SESSION["INCLUDE_PATH"]."/lib/LocalStorage/LocalStorage.php");
}

/**
  @class SQLiteStorage
  A LocalStorage implementation using SQLite

  Mostly a sample of what an alternative might
  look like. Performance is decidedly subpar.
  Not recommended for production use.
*/
class SQLiteStorage extends LocalStorage 
{
    private $db;

    public function SQLiteStorage()
    {
        $this->db = $this->conn();
        $result = sqlite_query("SELECT name FROM sqlite_master WHERE type='table' AND name='is4c_local'", $this->db);

        if (sqlite_num_rows($result) == 0) {
            $result = sqlite_query("CREATE TABLE is4c_local (keystr varchar(255), valstr varchar(255),
                        PRIMARY KEY (keystr) )",$this->db);
        }
    }    

    public function get($key)
    {
        if ($this->isImmutable($key)) {
            return $this->immutables[$key];
        }

        $row = sqlite_array_query("SELECT valstr FROM is4c_local WHERE keystr='$key'",$this->db);
        if (!$row) {
            return "";
        }
        $row = $row[0];
        if (strstr($row[0],chr(255))) {
            return explode(chr(255),$row[0]);
        } else {
            return $row[0];
        }
    }

    public function set($key,$val,$immutable=false)
    {
        if ($immutable) {
            $this->immutableSet($key,$val);
        } else {
            if (empty($val)) {
                sqlite_query("DELETE FROM is4c_local WHERE keystr='$key'",$this->db);
            } else {
                if (is_array($val)) {
                    $temp = "";
                    foreach($val as $v) {
                        $temp.=$v.chr(255);
                    }
                    $val = substr($temp,0,strlen($temp)-1);
                }
                $check = sqlite_query("SELECT valstr FROM is4c_local WHERE keystr='$key'",$this->db);
                if (sqlite_num_rows($check) == 0) {
                    //echo "INSERT INTO is4c_local VALUES ('$key','$val')";
                    sqlite_query("INSERT INTO is4c_local VALUES ('$key','$val')",$this->db);
                } else {
                    sqlite_query("UPDATE is4c_local SET valstr='$val' WHERE keystr='$key'",$this->db);
                }
            }
        }
        $this->debug();
    }

    private function conn()
    {
        return sqlite_open(dirname(__FILE__).'/SQLiteDB/db',0666);
    }
}

