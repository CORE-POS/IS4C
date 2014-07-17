<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of Fannie.

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
  @class FormLib
*/
class FormLib 
{

    /**
      Safely fetch a form value
      @param $name the field name
      @param $default default value if the form value doesn't exist
      @return The form value, if available, otherwise the default.
    */
    public static function getFormValue($name, $default='')
    {
        return (isset($_REQUEST[$name])) ? $_REQUEST[$name] : $default;
    }

    public static function get_form_value($name, $default='')
    {
        return self::getFormValue($name, $default);
    }

    public static function get($name, $default='')
    {
        return self::getFormValue($name, $default);
    }

    /**
      Get form input as a formatted date
      @param $name [string] form field
      @param $default [mixed, optional] default value if
        form input is omitted or invalid
      @param $format [string, optional] date format string.
        Default is Y-m-d.
    */
    public static function getDate($name, $default='', $format='Y-m-d')
    {
        $input_value = self::getFormValue($name, $default);
        $timestamp = strtotime($input_value);
        if ($timestamp === false || $timestamp === -1) {
            // input is invalid
            // if default is invalid that's the caller's problem
            return $default;
        } else {
            return date($format, $timestamp);
        }
    }

    /**
      Get a fieldset to select certain date ranges
      Requires JQquery
      @param $one id for date input (default 'date1')
      @param $two id for date input (default 'date2')
      @param $week_start day number (default 1/Monday)
      @return HTML string
    */
    public static function dateRangePicker($one='date1',$two='date2',$week_start=1)
    {
        /**
          calculate all the applicable dates in PHP
        */

        $today = array(date('Y-m-d'),date('Y-m-d'));

        // find begining of the week
        $monday = time();
        while(date('N',$monday) != $week_start) {
            $monday = mktime(0,0,0,date('n',$monday),date('j',$monday)-1,date('Y',$monday));
        }
        $this_week = array(date('Y-m-d',$monday),
            date('Y-m-d',mktime(0,0,0,date('n',$monday),date('j',$monday)+6,date('Y',$monday)))
        );

        $this_month = array(date('Y-m-01'),date('Y-m-t'));

        $y = mktime(0,0,0,date('n'),date('j')-1,date('Y'));    
        $yesterday = array(date('Y-m-d',$y),date('Y-m-d',$y));

        // go back a week
        $monday = mktime(0,0,0,date('n',$monday),date('j',$monday)-7,date('Y',$monday));
        $last_week = array(date('Y-m-d',$monday),
            date('Y-m-d',mktime(0,0,0,date('n',$monday),date('j',$monday)+6,date('Y',$monday)))
        );

        $lm = mktime(0,0,0,date('n')-1,1,date('Y'));
        $last_month = array(date('Y-m-01',$lm),date('Y-m-t',$lm));

        $extra_opts = sprintf("
            <fieldset style='border:330066;'>
            <legend>Other dates</legend>
            <table style='margin: 0em 0em 0em 0em;'>
            <tr style='vertical-align:top;'><td style='margin: 0em 1.0em 0em 0em;'>
            <input id='od10' type='radio' name='other_dates' 
                onclick=\"\$('#%s').val('%s');\$('#%s').val('%s')\" > 
                <label for='od10'>Today</label></br >
            </td><td>
            <input id='od11' type='radio' name='other_dates' 
                onclick=\"\$('#%s').val('%s');\$('#%s').val('%s')\" >
                <label for='od11'>This week</label></br >
            </td><td>
            <input id='od12' type='radio' name='other_dates' 
                onclick=\"\$('#%s').val('%s');\$('#%s').val('%s')\" >
                <label for='od12'>This month</label></br >
            </td>
            </tr><tr>
            <td rowspan='1'>
            <input id='od20' type='radio' name='other_dates' value='yesterday'
                onclick=\"\$('#%s').val('%s');\$('#%s').val('%s')\" >
                <label for='od20'>Yesterday</label></br >
            </td><td>
            <input id='od21' type='radio' name='other_dates' value='last_week'
                onclick=\"\$('#%s').val('%s');\$('#%s').val('%s')\" >
                <label for='od21'>Last week</label></br >
            </td><td>
            <input id='od22' type='radio' name='other_dates' value='last_month'
                onclick=\"\$('#%s').val('%s');\$('#%s').val('%s')\" >
                <label for='od22'>Last month</label></br >
            </td>
            </tr>
            </table>
            </fieldset>",
            $one,$today[0],$two,$today[1],
            $one,$this_week[0],$two,$this_week[1],
            $one,$this_month[0],$two,$this_month[1],
            $one,$yesterday[0],$two,$yesterday[1],
            $one,$last_week[0],$two,$last_week[1],
            $one,$last_month[0],$two,$last_month[1]
        );

        return $extra_opts;
    }

    public static function date_range_picker($one='date1',$two='date2',$week_start=1)
    {
        return self::dateRangePicker($one, $two, $week_start);
    }

    /**
      Get <select> box for the store ID
      @param $field_name [string] select.name (default 'store')
      @return keyed [array]
        - html => [string] select box
        - names => [array] store names
    */
    public static function storePicker($field_name='store')
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB, $previous);

        $stores = new StoresModel($dbc);
        $current = FormLib::get($field_name, 0);
        $labels = array(0 => _('All Stores'));
        $ret = '<select name="' . $field_name . '">';
        $ret .= '<option value="0">' . $labels[0] . '</option>';
        foreach($stores->find('storeID') as $store) {
            $ret .= sprintf('<option %s value="%d">%s</option>',
                    ($store->storeID() == $current ? 'selected' : ''),
                    $store->storeID(),
                    $store->description()
            );
            $labels[$store->storeID()] = $store->description();
        }
        $ret .= '</select>';

        // restore previous selected database
        if ($previous != $FANNIE_OP_DB) {
            FannieDB::get($previous);
        }

        return array(
            'html' => $ret,
            'names' => $labels, 
        );
    }

}

