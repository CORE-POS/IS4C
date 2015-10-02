<?php

if (!class_exists("LocalStorage")) {
    include_once(realpath(dirname(__FILE__).'/LocalStorage.php'));
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

    public function __construct()
    {
        $this->db = $this->conn();
        $result = sqlite_query($this->db, "SELECT name FROM sqlite_master WHERE type='table' AND name='is4c_local'");

        if (sqlite_num_rows($result) == 0) {
            $result = sqlite_query($this->db, "CREATE TABLE is4c_local (keystr varchar(255), valstr varchar(255),
                        PRIMARY KEY (keystr) )");
        }
    }    

    public function get($key)
    {
        if ($this->isImmutable($key)) {
            return $this->immutables[$key];
        }

        $row = sqlite_array_query($this->db, "SELECT valstr FROM is4c_local WHERE keystr='$key'");
        if (!$row) {
            return "";
        }
        $row = $row[0];
        if (strstr($row[0],chr(255))) {
            return explode(chr(255),$row[0]);
        } elseif ($row[0] === 'FALSE') {
            return false;
        } elseif ($row[0] === 'TRUE') {
            return true;
        } elseif (preg_match('/^\d+$/', $row[0])) {
            return (int)$row[0];
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
                sqlite_query($this->db, "DELETE FROM is4c_local WHERE keystr='$key'");
            } else {
                if (is_array($val)) {
                    $temp = "";
                    foreach($val as $v) {
                        $temp.=$v.chr(255);
                    }
                    $val = substr($temp,0,strlen($temp)-1);
                } elseif ($val === false) {
                    $val = 'FALSE';
                } elseif ($val === true) {
                    $val = 'TRUE';
                }
                $check = sqlite_query($this->db, "SELECT valstr FROM is4c_local WHERE keystr='$key'");
                if (sqlite_num_rows($check) == 0) {
                    sqlite_query($this->db, "INSERT INTO is4c_local VALUES ('$key','$val')");
                } else {
                    sqlite_query($this->db, "UPDATE is4c_local SET valstr='$val' WHERE keystr='$key'");
                }
            }
        }
        $this->debug();
    }

    public function iteratorKeys()
    {
        $data = sqlite_array_query($this->db, 'SELECT keystr FROM is4c_local', SQLITE_NUM);
        $keys = array();
        foreach ($data as $row) {
            $keys[] = $row[0];
        }

        return array_merge(parent::iteratorKeys(), $keys);
    }

    private function conn()
    {
        return sqlite_open(dirname(__FILE__).'/SQLiteDB/db',0666);
    }
}

