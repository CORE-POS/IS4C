<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

namespace COREPOS\common;

/**
  @class CoreTemplate
  Braindead templating engine that should
  be replaced by something like Twig. Avoiding
  additional dependencies and forcing Composer
  usage for the moment.
*/
class CoreTemplate
{
    protected $markup;

    public function __construct($str='')
    {
        $this->markup = $str;
    }

    /**
      Set template string if not via
      constructor
    */
    public function setContent($str)
    {
        $this->markup = $str;
    }

    public function getContent()
    {
        return $this->markup;
    }

    /**
      Execute templating "engine"
      @return [string] rendered content
    */
    public function render($data=array())
    {
        $str = $this->markup;
        $str = $this->intl($str);
        $str = $this->loop($str, $data);
        $str = $this->vars($str, $data);

        return $str;
    }

    /**
      Pass strings delimited with {_ _} through gettext
    */
    private function intl($str)
    {
        $all = preg_match_all('/\{_\s*(.*?)\s*_\}/', $str, $matches);
        if (!$all) {
            return $str;
        }

        $with_tags = $matches[0];
        $strings = $matches[1];
        for ($i=0; $i<count($strings); $i++) {
            $translated = _($strings[$i]);
            $str = str_replace($with_tags[$i], $translated, $str);
        }

        return $str;
    }

    /**
      Replace values delimited with {{ }} using the provided data
      For example, {{ foo }} is replaced by $data['foo'] or $data->foo.
    */
    private function vars($str, $data)
    {
        $all = preg_match_all('/\{\{\s*(.*?)\s*\}\}/', $str, $matches);
        if (!$all) {
            return $str;
        }

        $with_tags = $matches[0];
        $strings = $matches[1];
        for ($i=0; $i<count($strings); $i++) {
            try {
                $str = str_replace($with_tags[$i], $this->dataValue($data, $strings[$i]), $str);
            } catch (\Exception $ex) {
            }
        }
        
        return $str;
    }

    private function dataValue($data, $key)
    {
        if (is_array($data) && array_key_exists($key, $data)) {
            return $data[$key];
        } elseif (is_object($data) && property_exists($data, $key)) {
            return $data->$key;
        } elseif (is_object($data) && method_exists($data, $key)) {
            return $data->$key();
        }

        throw new \Exception('missing data value: ' . $key);
    }

    /**
      Loop lines delimited with {% %} and fill in the provided data
      Substitions in loops must place a period between the 
      variable name and field name, e.g. {{ item.upc }}
      Loops only work with a single variable and almost certainly don't
      nest correctly.
    */
    private function loop($str, $data)
    {
        $all = preg_match_all('/\{%\s*(.*?)\s%\}/s', $str, $matches);
        if (!$all) {
            return $str;
        }

        $with_tags = $matches[0];
        $strings = $matches[1];
        for ($i=0; $i<count($strings); $i++) {
            $repeated = $strings[$i];
            $unrolled = '';
            $all = preg_match_all('/\{\{\s(.*?)\.(.*?)\s\}\}/s', $repeated, $line_matches);
            if (!$all) {
                $str = str_replace($with_tags[$i], $strings[$i], $str); 
                continue;
            }
            $line_tags = $line_matches[0];
            $line_objects = $line_matches[1];
            $line_properties = $line_matches[2];
            if (is_object($data) && !property_exists($data, $line_objects[0])) {
                $str = str_replace($with_tags[$i], $strings[$i], $str); 
                continue;
            } elseif (is_array($data) && !isset($data[$line_objects[0]])) {
                $str = str_replace($with_tags[$i], $strings[$i], $str); 
                continue;
            } elseif (!is_object($data) && !is_array($data)) {
                $str = str_replace($with_tags[$i], $strings[$i], $str); 
                continue;
            }
            if (is_object($data)) {
                $line_obj = $data->{$line_objects[0]};
            } else {
                $line_obj = $data[$line_objects[0]];
            }
            foreach ($line_obj as $obj) {
                $line = $repeated;
                for ($j=0; $j<count($line_properties); $j++) {
                    $prop = $line_properties[$j];
                    try {
                        $line = str_replace($line_tags[$j], $this->dataValue($obj, $prop), $line);
                    } catch (\Exception $ex) {
                    }
                }
                $unrolled .= $line;
            }
            $str = str_replace($with_tags[$i], $unrolled, $str);
        }

        return $str;
    }
}


