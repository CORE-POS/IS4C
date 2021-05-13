<?php

class FannieConfig
{
    static private $instance = null;

    private $vars = array();

    /**
      Generate a singleton FannieConfig instance as needed
    */
    static public function factory()
    {
        if (!(self::$instance instanceof FannieConfig))
        {
            self::$instance = new FannieConfig();
            self::$instance->reload();
        }

        return self::$instance;
    }

    /**
      Reload configuration values from file
      Loads from config.php by default. Also supports
      a config.json alternative.
    */
    public function reload()
    {
        $this->vars = array();
        if (file_exists(dirname(__FILE__) . '/../config.php')) {
            include(dirname(__FILE__) . '/../config.php');
            $defined_vars = get_defined_vars();
            foreach ($defined_vars as $name => $val) {
                $this->vars[$name] = $val;
            }
        } elseif (file_exists(dirname(__FILE__) . '/../config.json')) {
            $json = json_decode(file_get_contents(dirname(__FILE__) . '/../config.json'));
            if ($json) {
                foreach ($json as $name => $val) {
                    $this->vars[$name] = $val;
                }
            }
        }
        if (file_exists(__DIR__ . '/../DEV_MODE')) {
            $this->vars['FANNIE_DEV_MODE'] = true;
        }
    }

    /**
      Utility: write current configuration in JSON format
      Not super helpful since it needs lint-ing
    */
    public function toJSON()
    {
        return json_encode($this->vars);
    }

    /**
      Get configuration value
      @param $name [string] name of setting
      @param $default [optional, empty string] value returned
        if the setting does not exist

      For brevity, $name can omit the FANNIE_ prefix. For instance,
      "FANNIE_URL" and "URL" will return the same value.
    */
    public function get($name, $default='')
    {
        if (isset($this->vars[$name])) {
            return $this->vars[$name];
        } elseif (isset($this->vars['FANNIE_' . $name])) {
            return $this->vars['FANNIE_' . $name];
        } else {
            return $default;
        }
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function override($values)
    {
        foreach ($values as $key => $val) {
            if (isset($this->vars[$key])) {
                $this->vars[$key] = $val;
            }
            if (isset($this->vars['FANNIE_' . $key])) {
                $this->vars['FANNIE_' . $key] = $val;
            }
        }
    }

    public static function config($name, $default='')
    {
        $obj = self::factory();

        return $obj->get($name, $default);
    }

    /**
      This method is provided solely for manipulating the
      environment during unit tests. Values set this way
      will not be stored permanently or persist beyond
      the end of the current script
    */
    public function set($name, $value)
    {
        $this->vars[$name] = $value;
    }

    public function production()
    {
        return $this->get('DEV_MODE') === true;
    }

}

