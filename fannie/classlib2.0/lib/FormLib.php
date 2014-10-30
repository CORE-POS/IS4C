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

        $extra_opts = sprintf('
            <div class="panel panel-default">
                <div class="panel-heading">Other dates</div>
                <div class="panel-body">
                <table class="table">
                <tr><td>
                <label>
                    <input class="radio-inline" id="od10" type="radio" name="other_dates" 
                        onclick="$(\'#%s\').val(\'%s\');$(\'#%s\').val(\'%s\')" /> 
                    Today
                </label>
                </td><td>
                <label>
                    <input class="radio-inline" id="od11" type="radio" name="other_dates" 
                        onclick="$(\'#%s\').val(\'%s\');$(\'#%s\').val(\'%s\')" />
                    This week
                </label>
                </td><td>
                <label>
                    <input class="radio-inline" id="od12" type="radio" name="other_dates" 
                        onclick="$(\'#%s\').val(\'%s\');$(\'#%s\').val(\'%s\')" />
                    This month
                </label>
                </td></tr>
                <tr><td>
                <label>
                    <input class="radio-inline" id="od20" type="radio" name="other_dates" 
                        onclick="$(\'#%s\').val(\'%s\');$(\'#%s\').val(\'%s\')" />
                    Yesterday
                </label>
                </td><td>
                <label>
                    <input class="radio-inline" id="od21" type="radio" name="other_dates" 
                        onclick="$(\'#%s\').val(\'%s\');$(\'#%s\').val(\'%s\')" />
                    Last week
                </label>
                </td><td>
                <label>
                    <input class="radio-inline" id="od22" type="radio" name="other_dates" 
                        onclick="$(\'#%s\').val(\'%s\');$(\'#%s\').val(\'%s\')" />
                    Last month
                </label>
                </td></tr>
                </table>
            </div>
            </div>',
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
        $ret = '<select name="' . $field_name . '" class="form-control">';
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

    /**
      Generate a very standard form with date and department fields
      @param $departments [array] of DepartmentModels
      @param $supers [array] of SuperDeptNamesModels
        [optional] default empty array
      @param $fake_supers [boolean] include -1 and -2 as All Retail and All
        [optional] default false
      @return [string] html form
    */
    public static function dateAndDepartmentForm($departments, $supers=array(), $fake_supers=false)
    {
        ob_start();
        ?>
<?php if (count($supers) > 0) { ?>
<div class="well">Selecting a Buyer/Dept overrides Department Start/Department End, but not Date Start/End.
        To run reports for a specific department(s) leave Buyer/Dept or set it to 'blank'
</div>
<?php } ?>
<form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="form-horizontal">
<div class="row">
    <div class="col-sm-5">
    <?php if (count($supers) > 0) { ?>
        <div class="form-group">
            <label class="control-label col-sm-4">Select Buyer/Dept</label>
            <div class="col-sm-8">
            <select id="buyer-select" name="buyer" class="form-control">
                <option value=""></option>
                <?php foreach ($supers as $s) { ?>
                <option value="<?php echo $s->superID(); ?>"><?php echo $s->super_name(); ?></option>
                <?php } ?>
                <?php if ($fake_supers) { ?>
                <option value=-2 >All Retail</option>
                <option value=-1 >All</option>
                <?php } ?>
            </select>
            </div>
        </div>
    <?php } ?>
        <div class="form-group">
            <label class="control-label col-sm-4">Department Start</label>
            <div class="col-sm-6">
            <select onchange="$('#dept-start').val(this.value);" class="form-control">
            <?php foreach ($departments as $d) { ?>
            <option value="<?php echo $d->dept_no(); ?>"><?php echo $d->dept_no(); ?>
                <?php echo $d->dept_name(); ?></option>
            <?php } ?>
            </select>
            </div>
            <div class="col-sm-2">
            <input type="number" name="deptStart" id="dept-start" value="1" class="form-control" />
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-sm-4">Department End</label>
            <div class="col-sm-6">
            <select onchange="$('#dept-end').val(this.value);" class="form-control">
            <?php foreach ($departments as $d) { ?>
            <option value="<?php echo $d->dept_no(); ?>"><?php echo $d->dept_no(); ?>
                <?php echo $d->dept_name(); ?></option>
            <?php } ?>
            </select>
            </div>
            <div class="col-sm-2">
            <input type="number" name="deptEnd" id="dept-end" value="1" class="form-control" />
            </div>
        </div>
        <div id="date-dept-form-left-col"></div>
    </div>
    <div class="col-sm-5">
        <div class="form-group">
            <label class="col-sm-4 control-label">Start Date</label>
            <div class="col-sm-8">
                <input type="text" id="date1" name="date1" class="form-control" required />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-4 control-label">End Date</label>
            <div class="col-sm-8">
                <input type="text" id="date2" name="date2" class="form-control" required />
            </div>
        </div>
        <div class="form-group">
            <?php echo FormLib::date_range_picker(); ?>                            
        </div>
    </div>
</div>
<p>
    <button type="submit" class="btn btn-default">Submit</button>
    <button type="reset" class="btn btn-default">Start Over</button>
</p>
        <?php
        return ob_get_clean();
    }

}

