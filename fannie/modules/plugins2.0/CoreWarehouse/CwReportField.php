<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

    This file is part of IT CORE.

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

namespace COREPOS\Fannie\Plugin\CoreWarehouse {

/**
  @class CwReportField
  Object representation of a <form> field.
  Used with CwReportDataSource when a data source
  supports additional fields beyond what is offered
  in the standard report
*/
class CwReportField
{
    const FIELD_TYPE_SELECT = 'select';
    const FIELD_TYPE_TEXT = 'text';

    /**
      Name of the form field
    */
    public $name;

    /**
      Text label for form field
    */
    public $label;

    /**
      Type of field; use one of the constants
    */
    public $type;

    /**
      Field default value
    */
    public $default;

    /**
      Array of options for FIELD_TYPE_SELECT fields
    */
    public $options;

    public function __construct()
    {
        $this->name = md5(rand());
        $this->label = 'Warehouse Field';
        $this->type = self::FIELD_TYPE_TEXT;
        $this->default = '';
        $this->options = array();
    }

    /**
      Convert field to HTML representation
      @return [string] HTML
    */
    public function toHTML()
    {
        switch ($this->type) {
            case self::FIELD_TYPE_TEXT:
                return $this->textToHTML();
            case self::FIELD_TYPE_SELECT:
                return $this->selectToHTML();
            default:
                return false;
        }
    }

    /**
      Render <select> field as HTML
      @return [string] HTML
    */
    private function selectToHTML()
    {
        $ret = '<label>' . $this->label . '</label> ';
        $ret .= '<select name="' . $this->name . '" class="form-control cw-field">';
        $keys = array_keys($this->options);
        $sequential_keys = range(0, count($this->options)-1);
        $use_values = ($keys != $sequential_keys) ? true : false;
        foreach ($this->options as $value => $label) {
            if (!$use_values) {
                $value = $label;
            }
            $ret .= sprintf('<option %s value="%s">%s</option>',
                ($value == $this->default ? 'selected' : 'foo'),
                $value, $label);
        }
        $ret .= '</select>';

        return $ret;
    }

    /**
      Render <input> field as HTML
      @return [string] HTML
    */
    private function textToHTML()
    {
        return sprintf('
            <label>%s</label>
            <input type="text" class="form-control cw-field" name="%s" value="%s" />',
            $this->label,
            $this->name, $this->default);
    }
}

}

