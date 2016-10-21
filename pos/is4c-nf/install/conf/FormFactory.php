<?php

namespace COREPOS\pos\install\conf;

use COREPOS\pos\install\conf\Conf;
use COREPOS\pos\install\conf\ParamConf;

class FormFactory
{
    private $dbc;

    // @hintable
    public function __construct($sql)
    {
        $this->dbc = $sql;
    }

    // @hintable
    public function setDB($sql)
    {
        $this->dbc = $sql;
    }

    /**
      Render configuration variable as an <input> tag
      Process any form submissions
      Write configuration variable to config.php

      @param $name [string] name of the variable
      @param $default_value [mixed, default empty string] default value for the setting
      @param $quoted [boolean, default true] write value to config.php with single quotes
      @param $attributes [array, default empty] array of <input> tag attribute names and values

      @return [string] html input field
    */
    // @hintable
    public function textField($name, $default_value='', $storage=Conf::EITHER_SETTING, $quoted=true, $attributes=array(), $area=false)
    {
        $current_value = $this->getCurrentValue($name, $default_value, $quoted);

        // sanitize values:
        if (!$quoted) {
            // unquoted must be a number or boolean
            // arrays of unquoted values only allow numbers
            if (is_array($current_value)) {
                for ($i=0; $i<count($current_value); $i++) {
                    if (!is_numeric($current_value[$i])) {
                        $current_value[$i] = (int)$current_value[$i];
                    }
                }
            } elseif (!is_numeric($current_value) && strtolower($current_value) !== 'true' && strtolower($current_value) !== false) {
                $current_value = (int)$current_value;
            }
        } else if ($quoted && !is_array($current_value)) {
            $current_value = $this->sanitizeString($current_value);
        }

        \CoreLocal::set($name, $current_value, true);
        if ($storage == Conf::INI_SETTING) {
            if (is_array($current_value)) {
                $out_value = 'array(' . implode(',', $current_value) . ')';
                Conf::save($name, $out_value);
            } elseif (!is_numeric($current_value) && strtolower($current_value) !== 'true' && strtolower($current_value !== 'false')) {
                Conf::save($name, "'" . $current_value . "'");
            } else {
                Conf::save($name, $current_value);
            }
        } else {
            ParamConf::save($this->dbc, $name, $current_value);
        }

        if (is_array($current_value)) {
            $current_value = implode(', ', $current_value);
        }
        
        $attributes['title'] = $this->storageAttribute($name, $storage);

        if ($area) {
            $ret = sprintf('<textarea name="%s"', $name);
            $ret .= $this->attributesToStr($attributes);
            $ret .= '>' . $current_value . '</textarea>';
        } else {
            $ret = sprintf('<input name="%s" value="%s"',
                $name, $current_value);
            if (!isset($attributes['type'])) {
                $attributes['type'] = 'text';
            }
            $ret .= $this->attributesToStr($attributes);
            $ret .= " />\n";
        }

        return $ret;
    }

    /**
      Render configuration variable as an <select> tag
      Process any form submissions
      Write configuration variable to config.php
      
      @param $name [string] name of the variable
      @param $options [array] list of options
        This can be a keyed array in which case the keys
        are what is written to config.php and the values
        are what is shown in the user interface, or it
        can simply be an array of valid values.
      @param $default_value [mixed, default empty string] default value for the setting
      @param $quoted [boolean, default true] write value to config.php with single quotes

      @return [string] html select field
    */
    // @hintable
    public function selectField($name, $options, $default_value='', $storage=Conf::EITHER_SETTING, $quoted=true, $attributes=array())
    {
        $current_value = $this->getCurrentValue($name, $default_value, $quoted);

        $is_array = false;
        if (isset($attributes['multiple'])) {
            $is_array = true;
            if (!isset($attributes['size'])) {
                $attributes['size'] = 5;
            }
            // with multi select, no value means no POST
            if (count($_POST) > 0 && !isset($_REQUEST[$name])) {
                $current_value = array();
            }
        }

        // sanitize values:
        $current_value = $this->sanitizeValue($current_value, $is_array, $quoted);
        
        \CoreLocal::set($name, $current_value, true);
        $this->writeInput($name, $current_value, $storage);

        $attributes['title'] = $this->storageAttribute($name, $storage);

        $ret = '<select name="' . $name . ($is_array ? '[]' : '') . '" ';
        $ret .= $this->attributesToStr($attributes);
        $ret .= ">\n";
        // array has non-numeric keys
        // if the array has meaningful keys, use the key value
        // combination to build <option>s with labels
        $has_keys = ($options === array_values($options)) ? false : true;
        foreach ($options as $key => $value) {
            $selected = '';
            if ($is_array && $has_keys) {
                foreach ($current_value as $cv) {
                    if ($this->endMatch($key, $cv)) {
                        $selected = 'selected';
                    }
                }
            } elseif ($is_array && !$has_keys) {
                foreach ($current_value as $cv) {
                    if ($this->endMatch($value, $cv)) {
                        $selected = 'selected';
                    }
                }
            } elseif ($has_keys && ($current_value == $key || $this->endMatch($key, $current_value))) {
                $selected = 'selected';
            } elseif (!$has_keys && ($current_value == $value || $this->endMatch($value, $current_value))) {
                $selected = 'selected';
            }
            $optval = $has_keys ? $key : $value;

            $ret .= sprintf('<option value="%s" %s>%s</option>',
                $optval, $selected, $value);
            $ret .= "\n";
        }
        $ret .= '</select>' . "\n";

        return $ret;
    }

    private function endMatch($full, $end)
    {
        if (strstr($end, '\\')) {
            $tmp = explode('\\', $end);
            $end = $tmp[count($tmp)-1];
        }
        $len = strlen($end);
        return substr($full, -1*$len) === $end;
    }

    // @hintable
    public function checkboxField($name, $label, $default_value=0, $storage=Conf::EITHER_SETTING, $choices=array(0, 1), $attributes=array())
    {
        $current_value = $this->getCurrentValue($name, $default_value, false);

        // sanitize
        if (!is_array($choices) || count($choices) != 2) {
            $choices = array(0, 1);
        }
        if (!in_array($current_value, $choices)) {
            $current_value = $default_value;
        }

        if (count($_POST) > 0 && !isset($_REQUEST[$name])) {
            $current_value = $choices[0];
        }

        \CoreLocal::set($name, $current_value, true);
        $this->writeInput($name, $current_value, $storage);

        $attributes['title'] = $this->storageAttribute($name, $storage);

        $ret = '<fieldset class="toggle">' . "\n";
        $ret .= sprintf('<input type="checkbox" name="%s" id="%s" value="%s" %s />',
                    $name, $name, $choices[1],
                    ($current_value == $choices[1] ? 'checked' : '')
        );
        $ret .= "\n";
        $ret .= sprintf('<label for="%s" onclick="">%s: </label>', $name, $label);
        $ret .= "\n";
        $ret .= '<span class="toggle-button" style="border: solid 3px black;"></span></fieldset>' . "\n";

        return $ret;
    }

    private function getCurrentValue($name, $default_value, $quoted)
    {
        $current_value = \CoreLocal::get($name);
        if ($current_value === '') {
            $current_value = $default_value;
        }
        if (isset($_REQUEST[$name])) {
            $current_value = $_REQUEST[$name];
            /**
              If default is array, value is probably supposed to be an array
              Split quoted values on whitespace, commas, and semicolons
              Split non-quoted values on non-numeric characters
            */
            if (is_array($default_value) && !is_array($current_value)) {
                if ($current_value === '') {
                    $current_value = array();
                } elseif ($quoted) {
                    $current_value = preg_split('/[\s,;]+/', $current_value); 
                } else {
                    $current_value = preg_split('/\D+/', $current_value); 
                }
            }
        }

        return $current_value;
    }

    private function storageAttribute($name, $storage)
    {
        if ($storage == Conf::INI_SETTING) {
            return _('Stored in ') . Conf::file();
        } else {
            return _('Stored in ') . 'opdata.parameters';
        }
    }

    // @hintable
    private function attributesToStr($attributes)
    {
        $ret = '';
        foreach ($attributes as $name => $value) {
            if ($name == 'name' || $name == 'value') {
                continue;
            }
            $ret .= ' ' . $name . '="' . $value . '"';
        }

        return $ret;
    }

    private function sanitizeValue($current_value, $is_array, $quoted)
    {
        if (!$is_array && !$quoted) {
            // unquoted must be a number or boolean
            if (!is_numeric($current_value) && strtolower($current_value) !== 'true' && strtolower($current_value) !== 'false') {
                $current_value = (int)$current_value;
            }
        } elseif (!$is_array && $quoted) {
            $current_value = $this->sanitizeString($current_value);
        } elseif ($is_array && !is_array($current_value)) {
            $current_value = $default_value;
        }

        return $current_value;
    }

    private function sanitizeString($current_value)
    {
        // quoted must not contain single quotes
        $current_value = str_replace("'", '', $current_value);
        // must not start with backslash
        while (strlen($current_value) > 0 && substr($current_value, 0, 1) == "\\") {
            $current_value = substr($current_value, 1);
        }
        // must not end with backslash
        while (strlen($current_value) > 0 && substr($current_value, -1) == "\\") {
            $current_value = substr($current_value, 0, strlen($current_value)-1);
        }

        return $current_value;
    }

    private function writeInput($name, $current_value, $storage)
    {
        if ($storage == Conf::INI_SETTING) {
            if (!is_numeric($current_value) && strtolower($current_value) !== 'true' && strtolower($current_value !== 'false')) {
                Conf::save($name, "'" . $current_value . "'");
            } else {
                Conf::save($name, $current_value);
            }
        } else {
            ParamConf::save($this->dbc, $name, $current_value);
        }
    }

}

