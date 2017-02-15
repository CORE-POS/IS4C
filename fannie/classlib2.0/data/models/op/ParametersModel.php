<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

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
  @class ParametersModel
*/
class ParametersModel extends BasicModel
{

    protected $name = "parameters";
    protected $preferred_db = 'op';

    protected $columns = array(
    'store_id' => array('type'=>'SMALLINT', 'primary_key'=>true),
    'lane_id' => array('type'=>'SMALLINT', 'primary_key'=>true),
    'param_key' => array('type'=>'VARCHAR(100)', 'primary_key'=>true),
    'param_value' => array('type'=>'VARCHAR(255)'),
    'is_array' => array('type'=>'TINYINT'),
    );

    /**
      Get the parameter's effective value by
      transforming it into an array or boolean
      if appropriate
      @return [mixed] param_value as correct PHP type
    */
    public function materializeValue()
    {
        $value = $this->param_value();
        if ($this->is_array()) {
            if ($value === '') {
                $value = array();
            } else {
                $value = explode(',', $value);
            }
            if (isset($value[0]) && strstr($value[0], '=>')) {
                $tmp = array();
                foreach ($value as $pair) {
                    list($key, $val) = explode('=>', $pair, 2);
                    $tmp[$key] = $val;
                }
                $value = $tmp;
            }
        } elseif (strtoupper($value) === 'TRUE') {
            $value = true;
        } elseif (strtoupper($value) === 'FALSE') {
            $value = false;
        }

        return $value;
    }

    public function doc()
    {
        return '
Use:
Partial replacement for ini.php.
This differs from the lane_config table.
This contains actual values where as lane_config
contains PHP code snippets that can
be written to a file.

Values with store_id=0 (or NULL) and lane_id=0 (or NULL)
are applied first, then values with the lane\'s own
lane_id are applied second as local overrides. A similar
precedent level based on store_id may be added at a later date.
        ';
    }
}

