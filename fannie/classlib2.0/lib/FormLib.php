<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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
        $op_db = FannieConfig::config('OP_DB');
        $dbc = FannieDB::get($op_db, $previous);

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
        if ($previous != $op_db) {
            FannieDB::get($previous);
        }

        return array(
            'html' => $ret,
            'names' => $labels, 
        );
    }

    public static function chainDeptFields($supers, $departments, $subs, $fake_supers=false)
    {
        $url_stem = FannieConfig::config('URL');
        ob_start();
        ?>
        <div class="form-group">
            <label class="control-label col-sm-4">Select SuperDept</label>
            <div class="col-sm-8">
                <select name="super-id" id="cdf-super-id" class="form-control"
                    onchange="chainDeptFieldsSuper(this.value);">
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
        <div class="form-group">
            <label class="control-label col-sm-4">Department Start</label>
            <div class="col-sm-6">
            <select id="cdf-dept-start-select" 
                onchange="$('#cdf-dept-start').val(this.value);$('#cdf-dept-end-select').val(this.value);$('#cdf-dept-end').val(this.value);" 
                class="form-control">
            <option value="">
            <?php foreach ($departments as $d) { ?>
            <option value="<?php echo $d->dept_no(); ?>"><?php echo $d->dept_no(); ?>
                <?php echo $d->dept_name(); ?></option>
            <?php } ?>
            </select>
            </div>
            <div class="col-sm-2">
            <input type="number" name="deptStart" id="cdf-dept-start" value="" class="form-control" />
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-sm-4">Department End</label>
            <div class="col-sm-6">
            <select id="cdf-dept-end-select" onchange="$('#cdf-dept-end').val(this.value);" class="form-control">
            <option value="">
            <?php foreach ($departments as $d) { ?>
            <option value="<?php echo $d->dept_no(); ?>"><?php echo $d->dept_no(); ?>
                <?php echo $d->dept_name(); ?></option>
            <?php } ?>
            </select>
            </div>
            <div class="col-sm-2">
            <input type="number" name="deptEnd" id="cdf-dept-end" value="" class="form-control" />
            </div>
        </div>
        <script type="text/javascript">
        function chainDeptFieldsSuper(superID)
        {
            if (superID === '') {
                superID = -1;
            }
            var req = {
                jsonrpc: '2.0',
                method: '\\COREPOS\\Fannie\\API\\webservices\\FannieDeptLookup',
                id: new Date().getTime(),
                params: {
                    'type' : 'children',
                    'superID' : superID
                }
            };
            $.ajax({
                url: '<?php echo $url_stem; ?>ws/',
                type: 'post',
                data: JSON.stringify(req),
                dataType: 'json',
                contentType: 'application/json',
                success: function(resp) {
                    if (resp.result) {
                        $('#cdf-dept-start-select').empty();
                        $('#cdf-dept-end-select').empty();
                        for (var i=0; i<resp.result.length; i++) {
                            var opt = $('<option>').val(resp.result[i]['id'])
                                .html(resp.result[i]['id'] + ' ' + resp.result[i]['name']);
                            $('#cdf-dept-start-select').append(opt.clone());
                            $('#cdf-dept-end-select').append(opt);
                        }
                        if (resp.result.length > 0) {
                            $('#cdf-dept-start-select').val(resp.result[0]['id']);
                            $('#cdf-dept-start').val(resp.result[0]['id']);
                            $('#cdf-dept-end-select').val(resp.result[resp.result.length-1]['id']);
                            $('#cdf-dept-end').val(resp.result[resp.result.length-1]['id']);
                        } else {
                            $('#cdf-dept-start').val('');
                            $('#cdf-dept-end').val('');
                        }
                    }
                }
            });
        }
        </script>
        <?php
        return ob_get_clean();
    }

    /**
      Generate a very standard form with date and department fields
      @return [string] html form
    */
    public static function dateAndDepartmentForm()
    {
        ob_start();
        ?>
<form method="get" class="form-horizontal">
<div class="row">
    <div class="col-sm-6">
        <?php echo self::standardDepartmentFields('buyer'); ?>
        <div id="date-dept-form-left-col"></div> 
    </div>
    <?php echo self::standardDateFields(); ?>
</div>
<p>
    <button type="submit" class="btn btn-default">Submit</button>
    <button type="reset" class="btn btn-default"
        onclick="$('#super-id').val('').trigger('change');">Start Over</button>
</p>
        <?php
        return ob_get_clean();
    }

    /**
      Build a standardized set of for querying items
      Currently includes tabs for:
      * department / superdepartment
      * brand
      * vendor
      * like code
    */
    public static function standardItemFields()
    {
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
        $url_stem = FannieConfig::config('URL');
        ob_start();
        ?>
        <script type="text/javascript">
        function filterDepartments(superID) {
            if (superID === '') {
                superID = -1;
            }
            var req = {
                jsonrpc: '2.0',
                method: '\\COREPOS\\Fannie\\API\\webservices\\FannieDeptLookup',
                id: new Date().getTime(),
                params: {
                    'type' : 'children',
                    'superID' : superID
                }
            };
            $.ajax({
                url: '<?php echo $url_stem; ?>ws/',
                type: 'post',
                data: JSON.stringify(req),
                dataType: 'json',
                contentType: 'application/json',
                success: function(resp) {
                    if (resp.result) {
                        $('#dept-start-select').empty();
                        $('#dept-end-select').empty();
                        for (var i=0; i<resp.result.length; i++) {
                            var opt = $('<option>').val(resp.result[i]['id'])
                                .html(resp.result[i]['id'] + ' ' + resp.result[i]['name']);
                            $('#dept-start-select').append(opt.clone());
                            $('#dept-end-select').append(opt);
                        }
                        if (resp.result.length > 0) {
                            $('#dept-start-select').val(resp.result[0]['id']);
                            $('#deptStart').val(resp.result[0]['id']);
                            $('#dept-end-select').val(resp.result[resp.result.length-1]['id']);
                            $('#deptEnd').val(resp.result[resp.result.length-1]['id']);
                            filterSubs();
                        } else {
                            $('#deptStart').val('');
                            $('#deptEnd').val('');
                        }
                    }
                }
            });
        }
        function filterSubs()
        {
            var range = [ $('#deptStart').val(), $('#deptEnd').val() ];
            var sID = $('#super-id').val();
            var req = {
                jsonrpc: '2.0',
                method: '\\COREPOS\\Fannie\\API\\webservices\\FannieDeptLookup',
                id: new Date().getTime(),
                params: {
                    'type' : 'children',
                    'dept_no' : range,
                    'superID' : sID
                }
            };
            $.ajax({
                url: '<?php echo $url_stem; ?>ws/',
                type: 'post',
                data: JSON.stringify(req),
                dataType: 'json',
                contentType: 'application/json',
                success: function(resp) {
                    if (resp.result) {
                        $('#sub-start').empty();
                        $('#sub-end').empty();
                        $('#sub-start').append($('<option value="">Select Sub</option>'));
                        $('#sub-end').append($('<option value="">Select Sub</option>'));
                        for (var i=0; i<resp.result.length; i++) {
                            var opt = $('<option>').val(resp.result[i]['id'])
                                .html(resp.result[i]['id'] + ' ' + resp.result[i]['name']);
                            $('#sub-start').append(opt.clone());
                            $('#sub-end').append(opt);
                        }
                    }
                }
            });
        }
        </script>
        <div class="col-sm-5">
            <ul class="nav nav-tabs" role="tablist">
                <li class="active"><a href="#dept-tab" data-toggle="tab"
                    onclick="$('#supertype').val('dept');">Department</a></li>
                <li><a href="#manu-tab" data-toggle="tab"
                    onclick="$('#supertype').val('manu');">Brand</a></li>
                <li><a href="#vendor-tab" data-toggle="tab"
                    onclick="$('#supertype').val('vendor');">Vendor</a></li>
                <li><a href="#likecode-tab" data-toggle="tab"
                    onclick="$('#supertype').val('likecode');">Like Code</a></li>
            </ul>
            <input id="supertype" name="lookup-type" type="hidden" value="dept" />
            <div class="tab-content"><p>
                <div class="tab-pane active" id="dept-tab">
                    <div class="row form-group form-horizontal">
                        <label class="control-label col-sm-3">Buyer (SuperDept)</label>
                        <div class="col-sm-8">
                            <select name=super-dept id="super-id" class="form-control" onchange="filterDepartments(this.value);">
                                <option value=""></option>
                                <?php
                                $supers = $dbc->query('
                                    SELECT superID, super_name
                                    FROM superDeptNames
                                    ORDER BY superID');         
                                while ($row = $dbc->fetch_row($supers)) {
                                    printf('<option value="%d">%s</option>',
                                        $row['superID'], $row['super_name']);
                                }
                                ?>
                                <option value="-2">All Retail</option>
                                <option value="-1">All</option>
                            </select>
                        </div>
                    </div>
                    <div class="row form-group form-horizontal">
                        <label class="control-label col-sm-3">Dept. Start</label>
                        <div class="col-sm-6">
                            <select onchange="$('#deptStart').val(this.value); filterSubs();" 
                                id="dept-start-select" class="form-control input-sm">
                            <?php
                            $depts = array();
                            $res = $dbc->query('
                                SELECT dept_no, dept_name
                                FROM departments
                                ORDER BY dept_no');
                            while ($w = $dbc->fetch_row($res)) {
                                $depts[$w['dept_no']] = $w['dept_name'];
                            }
                            foreach ($depts as $id => $name) {
                                printf('<option value="%d">%d %s</option>',
                                    $id, $id, $name);
                            }
                            ?>
                            </select>
                        </div>
                        <div class="col-sm-2">
                            <input type=text id=deptStart name=dept-start 
                                class="form-control input-sm" value=1>
                        </div>
                    </div>
                    <div class="form-group form-horizontal row">
                        <label class="control-label col-sm-3">Dept. End</label>
                        <div class="col-sm-6">
                            <select onchange="$('#deptEnd').val(this.value); filterSubs();"
                                id="dept-end-select" class="form-control input-sm">
                            <?php
                            foreach ($depts as $id => $name) {
                                printf('<option value="%d">%d %s</option>',
                                    $id, $id, $name);
                            }
                            ?>
                            </select>
                        </div>
                        <div class="col-sm-2">
                            <input type=text id=deptEnd name=dept-end 
                                class="form-control input-sm" value=1>
                        </div>
                    </div>
                    <div class="form-group form-horizontal row">
                        <label class="control-label col-sm-3">Sub Start</label>
                        <div class="col-sm-6">
                            <select id="sub-start" name="sub-start" class="form-control">
                            <option value="">Select dept</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group form-horizontal row">
                        <label class="control-label col-sm-3">Sub End</label>
                        <div class="col-sm-6">
                            <select id="sub-end" name="sub-end" class="form-control">
                            <option value="">Select dept</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="tab-pane" id="manu-tab">
                    <div class="form-group form-inline">
                        <label><?php echo _('Brand'); ?></label>
                        <input type=text name=manufacturer class="form-control" />
                    </div>
                    <div class="form-group form-inline">
                        <label><input type=radio name=mtype value=prefix checked />
                            UPC prefix</label>
                        <label><input type=radio name=mtype value=name />
                            <?php echo _('Brand name'); ?></label>
                    </div>
                </div>
                <div class="tab-pane" id="vendor-tab">
                    <div class="form-group form-inline">
                        <label>Vendor</label>
                        <select name="vendor" class="form-control">
                        <?php
                        $vendors = $dbc->query('
                            SELECT vendorID, vendorName
                            FROM vendors
                            ORDER BY vendorName');
                        while ($w = $dbc->fetch_row($vendors)) {
                            printf('<option value="%d">%s</option>',
                                $w['vendorID'], $w['vendorName']);
                        }
                        ?>
                        </select>
                    </div>
                </div>
                <div class="tab-pane" id="likecode-tab">
                    <div class="row form-group form-horizontal">
                        <label class="control-label col-sm-3">Likecode Start</label>
                        <div class="col-sm-6">
                            <select onchange="$('#lc-start').val(this.value);" class="form-control input-sm">
                            <?php
                            $likecodes = $dbc->query('
                                SELECT likeCode, likeCodeDesc
                                FROM likeCodes
                                ORDER BY likeCode');
                            $lc_list = array();
                            while ($w = $dbc->fetch_row($likecodes)) {
                                $lc_list[$w['likeCode']] = $w['likeCodeDesc'];
                            }
                            foreach ($lc_list as $id => $name) {
                                printf('<option value="%d">%d %s</option>',
                                    $id, $id, $name);
                            }
                            ?>
                            </select>
                        </div>
                        <div class="col-sm-2">
                            <input type=text id=lc-start name=lc-start 
                                class="form-control input-sm" value=1>
                        </div>
                    </div>
                    <div class="row form-group form-horizontal">
                        <label class="control-label col-sm-3">Likecode End</label>
                        <div class="col-sm-6">
                            <select onchange="$('#lc-end').val(this.value);" class="form-control input-sm">
                            <?php
                            foreach ($lc_list as $id => $name) {
                                printf('<option value="%d">%d %s</option>',
                                    $id, $id, $name);
                            }
                            ?>
                            </select>
                        </div>
                        <div class="col-sm-2">
                            <input type=text id=lc-end name=lc-end 
                                class="form-control input-sm" value=1>
                        </div>
                    </div>
                </div>
            </p></div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
      Draw a standard set of bootstrap'd department fields
      with javascript chaining as they change
      @param $super [string, default 'super'] name of the super department <select>
      @param $multi [string, default 'departments'] name of the department multi <select>
      @param $start [string, default 'deptStart'] name of the department start single <select>
      @param $end [string, default 'deptEnd'] name of the department end single <select>
      @param $subs [string, default 'subdepts'] name of the subdepartment multi <select>
      @return [string] HTML
    */
    public static function standardDepartmentFields($super='super',$multi='departments',$start='deptStart',$end='deptEnd', $subs='subdepts')
    {
        /**
          Precalculate options for superdept and dept selects
        */
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
        $superR = $dbc->query('SELECT superID, super_name FROM superDeptNames');
        $super_opts = '';
        while ($w = $dbc->fetchRow($superR)) {
            $super_opts .= sprintf('<option value="%d">%s</option>',
                $w['superID'], $w['super_name']) . "\n";
        }
        $deptR = $dbc->query('SELECT dept_no, dept_name FROM departments ORDER BY dept_no');
        $dept_opts = '';
        while ($w = $dbc->fetchRow($deptR)) {
            $dept_opts .= sprintf('<option value="%d">%d %s</option>',
                $w['dept_no'], $w['dept_no'], $w['dept_name']) . "\n";
        }

        /**
          Store javascript chaining function calls in variables
          the sub chaining one is repeated a bunch. The super chaining
          one depends which type of department <select>s are shown
          They're also ridiculously long argument lists.
        */
        $url = FannieConfig::config('URL');
        $chainsubs = "chainSubDepartments('{$url}ws/', {super_id:'#super-id', dept_start:'#dept-start-txt', dept_end:'#dept-end-txt', sub_multiple:'#subdepts'})";
        $onchange = "chainSuperDepartment('{$url}ws/', this.value, {dept_start:'#dept-start',dept_end:'#dept-end',dept_start_id:'#dept-start-txt',dept_end_id:'#dept-end-txt',callback:function(){ $chainsubs; }})";
        if (FannieConfig::config('REPORT_DEPT_MODE') == 'multi') {
            $onchange = "chainSuperDepartment('{$url}ws/', this.value, {departments:'#departments'})";
        }

        /**
          The rest of this method uses HEREDOC style strings with
          {{PLACEHOLDERS}} and substitutes PHP variables in after the
          fact. This is an uncommon coding style in the overall project
          but the HTML is easier to read
        */

        $ret = <<<HTML
<div class="form-group">
    <label class="col-sm-4 control-label">Super Department</label>
    <div class="col-sm-8">
        <select name="{{SUPER_FIELD_NAME}}" id="super-id" class="form-control" onchange="{{SUPER_ONCHANGE}};">
            <option value="">Select super department</option>
            {{SUPER_OPTS}}
            <option value="-2">All Retail</option><option value="-1">All</option>
        </select>
    </div>
</div>
HTML;
        $ret = str_replace('{{SUPER_FIELD_NAME}}', $super, $ret);
        $ret = str_replace('{{SUPER_ONCHANGE}}', $onchange, $ret);
        $ret = str_replace('{{SUPER_OPTS}}', $super_opts, $ret);

        if (FannieConfig::config('REPORT_DEPT_MODE') == 'multi') {
            $ret .= <<<HTML
<div class="form-group">
    <label class="col-sm-4 control-label">Department(s)</label>
    <div class="col-sm-8">
        <select id="departments" name="{{DEPT_MULTI}}[]" class="form-control" 
            multiple size="10" onchange="{{DEPT_ONCHANGE}};">';
            {{DEPT_OPTS}}
        </select>
    </div>
</div>
HTML;
            $ret = str_replace('{{DEPT_MULTI}}', $multi, $ret);
        } else {
            $ret .= <<<HTML
<div class="form-group">
    <label class="col-sm-4 control-label">Department Start</label>
    <div class="col-sm-6">
        <select id="dept-start" class="form-control"
            onchange="$('#dept-start-txt').val(this.value); {{DEPT_ONCHANGE}};">
            {{DEPT_OPTS}}
        </select>
    </div>
    <div class="col-sm-2">
        <input type="text" name="{{DEPT_START}}" id="dept-start-txt" 
            onchange="$('#dept-start').val(this.value); {{DEPT_ONCHANGE}};"
            class="form-control" value="1" />
    </div>
</div>
<div class="form-group">
    <label class="col-sm-4 control-label">Department End</label>
    <div class="col-sm-6">
        <select id="dept-end" class="form-control"
            onchange="$('#dept-end-txt').val(this.value); {{DEPT_ONCHANGE}};">
            {{DEPT_OPTS}}
        </select>
    </div>
    <div class="col-sm-2">
    <input type="text" name="{{DEPT_END}}" id="dept-end-txt" 
        onchange="$('#dept-end').val(this.value);' {{DEPT_ONCHANGE}}';"
        class="form-control" value="1" />
    </div>
</div>
HTML;
            $ret = str_replace('{{DEPT_START}}', $start, $ret);
            $ret = str_replace('{{DEPT_END}}', $end, $ret);
        }
        $ret = str_replace('{{DEPT_OPTS}}', $dept_opts, $ret);
        $ret = str_replace('{{DEPT_ONCHANGE}}', $chainsubs, $ret);

        $ret .= <<<HTML
<div class="form-group">
    <label class="col-sm-4 control-label">Sub Dept(s)</label>
    <div class="col-sm-8">
        <select id="subdepts" name="{{SUBS_NAME}}[]" class="form-control" multiple size="5">
        </select>
    </div>
</div>
HTML;
        $ret = str_replace('{{SUBS_NAME}}', $subs, $ret);

        return $ret;
    }

    /**
      Generate standard date fields with date_range_picker
    */
    public static function standardDateFields()
    {
        return '
        <div class="col-sm-5 form-horizontal">
            <div class="form-group">
                <label class="col-sm-4 control-label">Start Date</label>
                <div class="col-sm-8">
                    <input type="text" id="date1" name="date1" class="form-control date-field" required />
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-4 control-label">End Date</label>
                <div class="col-sm-8">
                    <input type="text" id="date2" name="date2" class="form-control date-field" required />
                </div>
            </div>
            <div class="form-group">
                ' . self::date_range_picker() . '
            </div>
        </div>
        ';
    }

    /**
      Generate FROM and WHERE clauses with appropriate parameters
      and joins based on the standard form submissions.
      @return [keyed array]
      - query [string] from and where clauses
      - args [array] corresponding parameters
    */
    static public function standardItemFromWhere()
    {
        $op_db = FannieConfig::config('OP_DB');
        $dbc = FannieDB::get($op_db);
        $start_date = self::getDate('date1', date('Y-m-d'));
        $end_date = self::getDate('date2', date('Y-m-d'));
        $dlog = DTransactionsModel::selectDlog($start_date, $end_date);
        $lookupType = self::get('lookup-type', 'dept');

        $query = '
            FROM ' . $dlog . ' AS t 
                LEFT JOIN departments AS d ON t.department=d.dept_no
                LEFT JOIN products AS p ON t.upc=p.upc 
                LEFT JOIN MasterSuperDepts AS m ON t.department=m.dept_ID 
                LEFT JOIN subdepts AS b ON t.department=b.dept_ID
                LEFT JOIN vendors AS v ON p.default_vendor_id=v.vendorID
                LEFT JOIN prodExtra AS x ON t.upc=x.upc ';
        $args = array();
        switch ($lookupType) {
            case 'dept':
                $super = FormLib::get('super-dept');
                if ($super !== '' && $super >= 0) {
                    $query .= ' LEFT JOIN superdepts AS s ON t.department=s.dept_ID ';
                }
                break;
            case 'manu':
                break;
            case 'vendor':
                $query .= ' LEFT JOIN vendors AS z ON x.distributor=z.vendorName ';
                break;
            case 'likecode':
                $query .= ' LEFT JOIN upcLike AS u ON t.upc=u.upc ';
                break;
        }

        $query .= ' WHERE t.tdate BETWEEN ? AND ? ';
        $args[] = $start_date . ' 00:00:00';
        $args[] = $end_date . ' 23:59:59';

        switch ($lookupType) {
            case 'dept':
                $super = FormLib::get('super-dept');
                if ($super !== '' && $super >= 0) {
                    $query .= ' AND s.superID=? ';
                    $args[] = $super;
                    if (FormLib::get('dept-start') !== '' && FormLib::get('dept-end') !== '') {
                        $query .= ' AND t.department BETWEEN ? AND ? ';
                        $args[] = FormLib::get('dept-start');
                        $args[] = FormLib::get('dept-end');
                    }
                } elseif ($super !== '' && $super == -2) {
                    $query .= ' AND m.superID <> 0 ';
                    if (FormLib::get('dept-start') !== '' && FormLib::get('dept-end') !== '') {
                        $query .= ' AND t.department BETWEEN ? AND ? ';
                        $args[] = FormLib::get('dept-start');
                        $args[] = FormLib::get('dept-end');
                    }
                } elseif ($super === '') {
                    $query .= ' AND t.department BETWEEN ? AND ? ';
                    $args[] = FormLib::get('dept-start', 1);
                    $args[] = FormLib::get('dept-end', 1);
                }
                if (FormLib::get('sub-start') !== '' && FormLib::get('sub-end') !== '') {
                    $query .= ' AND b.subdept_no BETWEEN ? AND ? 
                                AND p.subdept=b.subdept_no ';
                    $args[] = FormLib::get('sub-start');
                    $args[] = FormLib::get('sub-end');
                }
                break;
            case 'manu':
                $mtype = FormLib::get('mtype');
                if ($mtype == 'prefix') {
                    $query .= ' AND t.upc LIKE ? ';
                    $args[] = '%' . FormLib::get('manufacturer') . '%';
                } else {
                    $query .= ' AND (p.brand LIKE ? OR x.manufacturer LIKE ?) ';
                    $manu = '%' . FormLib::get('manufacturer') . '%';
                    $args[] = $manu;
                    $args[] = $manu;
                    $optimizeP = $dbc->prepare('
                        SELECT p.department
                        FROM products AS p
                            LEFT JOIN prodExtra AS x ON p.upc=x.upc
                        WHERE (p.brand LIKE ? OR x.manufacturer LIKE ?)
                        GROUP BY p.department');
                    $optimizeR = $dbc->execute($optimizeP, array($manu, $manu));
                    $dept_in = '';
                    while ($optimizeW = $dbc->fetch_row($optimizeR)) {
                        $dept_in .= '?,';
                        $args[] = $optimizeW['department'];
                    }
                    if ($dept_in !== '') {
                        $dept_in = substr($dept_in, 0, strlen($dept_in)-1);
                        $query .= ' AND t.department IN (' . $dept_in . ') ';
                    }
                }
                break;
            case 'vendor':
                $query .= ' AND (p.default_vendor_id=? OR z.vendorID=?) ';
                $vID = FormLib::get('vendor', 1);
                $args[] = $vID;
                $args[] = $vID;
                $optimizeP = $dbc->prepare('
                    SELECT p.department
                    FROM products AS p
                        LEFT JOIN prodExtra AS x ON p.upc=x.upc
                        LEFT JOIN vendors AS v ON x.distributor=v.vendorName
                    WHERE (p.default_vendor_id=? OR v.vendorID=?
                    GROUP BY p.department');
                $optimizeR = $dbc->execute($optimizeP, array($vID, $vID));
                $dept_in = '';
                while ($optimizeW = $dbc->fetch_row($optimizeR)) {
                    $dept_in .= '?,';
                    $args[] = $optimizeW['department'];
                }
                if ($dept_in !== '') {
                    $dept_in = substr($dept_in, 0, strlen($dept_in)-1);
                    $query .= ' AND t.department IN (' . $dept_in . ') ';
                }
                break;
            case 'likecode':
                $query .= ' AND u.likeCode BETWEEN ? AND ? ';
                $args[] = FormLib::get('lc-start', 1);
                $args[] = FormLib::get('lc-end', 1);
                $optimizeP = $dbc->prepare('
                    SELECT p.department
                    FROM products AS p
                        INNER JOIN upcLike AS u ON p.upc=u.upc
                    WHERE u.likeCode BETWEEN ? AND ?
                    GROUP BY p.department');
                $optimizeR = $dbc->execute($optimizeP, array(FormLib::get('lc-start', 1), FormLib::get('lc-end', 1)));
                $dept_in = '';
                while ($optimizeW = $dbc->fetch_row($optimizeR)) {
                    $dept_in .= '?,';
                    $args[] = $optimizeW['department'];
                }
                if ($dept_in !== '') {
                    $dept_in = substr($dept_in, 0, strlen($dept_in)-1);
                    $query .= ' AND t.department IN (' . $dept_in . ') ';
                }
                break;
            case 'u':
                $upcs = FormLib::get('u', array());
                if (count($upcs) == 0) {
                    $upcs[] = 'NOTREALUPC';
                }
                $query .= ' AND t.upc IN (';
                foreach ($upcs as $u) {
                    $query .= '?,';
                    $args[] = BarcodeLib::padUPC($u);
                }
                $query = substr($query, 0, strlen($query)-1) . ') ';
                break;
        }

        return array('query'=>$query, 'args'=>$args);
    }

    /**
      Method gets a value from container or returns
      a default if the value does not exist
      @c [object] container for values
      @field [string] field name
      @default [mixed] default value
      @retun field value OR default value
    */
    public static function extract(\COREPOS\common\mvc\ValueContainer $c, $field, $default='')
    {
        try {
            return $c->$field;
        } catch (Exception $ex) {
            return $default;
        }
    }

}

