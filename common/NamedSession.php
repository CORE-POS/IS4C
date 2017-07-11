<?php

namespace COREPOS\common;

/**
  Lightweight wrapper over $_SESSION superglobal
  that adds a prefix to all keys. This is to prevent
  collisions when multiple copies of a piece of software
  are installed side by side.
*/
class NamedSession
{
    private $name;
    public function __construct($name)
    {
        // security is not the goal here.
        // hashing just serves to normalize length.
        $this->name = sha1($name);
        if (ini_get('session.auto_start')==0 && !headers_sent() && php_sapi_name() != 'cli' && session_id() === '') {
            session_start();
        }
    }

    public function __get($key)
    {
        $realKey = $this->name . '-' . $key;
        return isset($_SESSION[$realKey]) ? $_SESSION[$realKey] : '';
    }

    public function __set($key, $val)
    {
        $realKey = $this->name . '-' . $key;
        $_SESSION[$realKey] = $val;

        return $this;
    }

    public function __isset($key)
    {
        $realKey = $this->name . '-' . $key;
        return isset($_SESSION[$realKey]);
    }

    public function __unset($key)
    {
        $realKey = $this->name . '-' . $key;
        unset($_SESSION[$realKey]);
    }
}

