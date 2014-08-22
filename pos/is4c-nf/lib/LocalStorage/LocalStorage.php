<?php

/**
  @class LocalStorage

  A module for storing persistent values  
  This is handled via PHP sessions by
  default.

  Interface class. Must be subclassed
  to do anything.
*/

class LocalStorage 
{

    protected $immutables = array();

    /**
      Constructor
    */
    public function LocalStorage()
    {

    }

    /**
      Get the value stored with the given key
      @param $key A unique key string
      @return The value (mixed)

      The value can be any PHP type that the
      underlying mechanism can store.

      If the key is not found, the return
      value will be an empty string.
    */
    public function get($key)
    {

    }

    /**
      Save the value with the given key
      @param $key A unique key string
      @param $val The value (mixed)
      @param $immutable the value is a constant

      The value can be any PHP type that the
      underlying mechanism can store.
    */
    public function set($key,$val,$immutable=false)
    {
        debug($key);
    }

    /**
      Store an immutable value by key
      @param $key A unique key string
      @param $val The value (mixed)

      Values are saved in LocalStorage::immutables
    */
    protected function immutableSet($key,$val)
    {
        $this->immutables[$key] = $val;
    }
    
    /**
      Check if a key is present in immutables
      @param $key A unique key string
      @return bool
    */
    public function isImmutable($key)
    {
        if (isset($this->immutables[$key])) {
            return true;
        } else {
            return false;
        }
    }

    /**
      Log state changes if debugging is enabled
      
      Call this from your set method
    */
    protected function debug()
    {
        if($this->get("Debug_CoreLocal") == 1) {
            $stack = debug_backtrace();
            $log = realpath(dirname(__FILE__).'/../../log/').'/core_local.log';
            $fp = @fopen($log,'a');
            if ($fp) {
                foreach($stack as $s) {
                    if ($s['function']=='set'&&$s['class']==get_class($this)) {
                        ob_start();
                        echo date('r').': Changed value for '.$s['args'][0]."\n";
                        echo 'New value: ';
                        print_r($s['args'][1]);
                        echo "\n"; 
                        echo 'URL ' . $_SERVER['PHP_SELF'] . "\n";
                        echo 'Line '.$s['line'].', '.$s['file']."\n\n";
                        $out = ob_get_clean();
                        fwrite($fp,$out);
                        break;    
                    }
                }
                fclose($fp);
            }
        }
    }
}

