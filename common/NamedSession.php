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
    private $changed = false;
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
        if (isset($_SESSION[$realKey]) && $_SESSION[$realKey] != $val) {
            $this->changed = true;
        }
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
        if (isset($_SESSION[$realKey])) {
            $this->changed = true;
        }
        unset($_SESSION[$realKey]);
    }

    public function changed()
    {
        return $this->changed;
    }

    public function perma()
    {
        $args = func_get_args();
        switch (count($args)) {
            case 1:
                return $this->__get('__' . $args[0]);
            case 2:
                return $this->__set('__' . $args[0], $args[1]);
            default:
                throw new \Exception('perma() takes 1 or 2 arguments');
        }
    }

    public function getPerma()
    {
        $ret = array();
        $len = strlen($this->name) + 1;
        foreach ($_SESSION as $key => $val) {
            if (substr($key, 0, $len) != $this->name . '-') {
                continue;
            }
            $key = substr($key, $len);
            if (substr($key, 0, 2) == '__') {
                $ret[$key] = $val;
            }
        }

        return $ret;
    }
}

