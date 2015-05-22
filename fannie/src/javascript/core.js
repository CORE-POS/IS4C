/**
  Show a bootstrap style alert div
  @param selector [string] valid jQuery selector
    specifying where alert is drawn
  @param type [string] danger/warning/info/success
  @param msg [string] content of alert
*/
function showBootstrapAlert(selector, type, msg)
{
    var alertbox = '<div class="alert alert-' + type + '" role="alert">';
    alertbox += '<button type="button" class="close" data-dismiss="alert">';
    alertbox += '<span>&times;</span></button>';
    alertbox += msg + '</div>';
    $(selector).append(alertbox);
}

/**
  Show bootstrap popup message adjacent to element
  @param element [jQuery element object] where popover
    will be shown. Should generally be an <input>
  @param original_value [string] element value is restored
    to this if an error occurs
  @param error_message [string] error message. Empty string
    indicates no error.
*/
function showBootstrapPopover(element, original_value, error_message)
{
    var timeout = 1500;
    if (error_message == '') {
        error_message = 'Saved!';
    } else {
        element.val(original_value);
        timeout = 3000;
    }
    var t = element.attr('title');
    element.attr('title', '');
    element.popover({
        html: true,
        content: error_message,
        placement: 'auto bottom'
    });
    element.popover('show');
    setTimeout(function(){element.popover('destroy');element.attr('title', t);}, timeout);
}

/**
  Examine input element's value, process operands
  like +,-,*,/,etc to calculate numeric value.
  @param element [DOM element]
*/
function mathField(elem)
{
    try {
        var newval = calculator.parse(elem.value);
        elem.value = newval;
    } catch (e) { }
}

/**
  Bind actions to elements based on type
  and CSS class(es)

  Currently:
  * Adds jQueryUI datepicker to input.date-field
  * Adds matchField (above) to input.math-field
*/
function standardFieldMarkup()
{
    $('input.date-field').datepicker({
        dateFormat: 'yy-mm-dd',    
        changeYear: true,
        yearRange: "c-10:c+10",
    });
    $('input.math-field').change(function (event) {
        mathField(event.target);
    });
}

/**
  Check whether the properties are present in the
  object and valid jQuery selectors
  @param obj [object]
  @param properties [array] list of property names 
*/
function validateSelectorProperties(obj, properties)
{
    for (var i=0; i<properties.length; i++) {
        var prop = properties[i];
        if (prop in obj) {
            if ($(obj[prop]).length == 0) {
                delete obj[prop];
            }
        }
    }

    return obj;
}

/**
  Select chaining. When a super department is selected,
  lookup child departments and populate field(s). Any
  non-existant department field selector is ignored
  @param ws_url [string] webservices URL
  @param super_id [int] super department ID
  @param params [object] containing additional named parameters.
    - dept_multiple [jQuery selector] multiple <select>
    - dept_start [jQuery selector] single start <select>
    - dept_end [jQuery selector] single end <select>
    - dept_start_id [jQuery selector] single start <input> 
    - dept_end_id [jQuery selector] single end <input>
    - callback [function] called after chaining
*/
function chainSuperDepartment(ws_url, super_id, params)
{
    if (typeof params != 'object') {
        throw "chainSuperDepartment: 3rd parameter must be an object";
    }

    params = validateSelectorProperties(params, ['dept_multiple', 'dept_start', 'dept_end', 'dept_start_id', 'dept_end_id']);

    if (!('dept_multiple' in params) && !('dept_start' in params) && !('dept_end' in params)) {
        throw "chainSuperDepartmet: must specify dept_multiple, dept_start, or dept_end";
    }

    if ('callback' in params && typeof params.callback != 'function') {
        delete params.callback;
    }

    if (super_id === '') {
        super_id = -1;
    }

    var req = {
        jsonrpc: '2.0',
        method: '\\COREPOS\\Fannie\\API\\webservices\\FannieDeptLookup',
        id: new Date().getTime(),
        params: {
            'type' : 'children',
            'superID' : super_id
        }
    };

    $.ajax({
        url: ws_url,
        type: 'post',
        data: JSON.stringify(req),
        dataType: 'json',
        contentType: 'application/json',
        success: function(resp) {
            if (resp.result) {
                ['dept_multiple', 'dept_start', 'dept_end'].forEach(function(p){
                    if (p in params) {
                        $(params[p]).empty();
                    }
                });
                for (var i=0; i<resp.result.length; i++) {
                    var opt = $('<option>').val(resp.result[i]['id'])
                        .html(resp.result[i]['id'] + ' ' + resp.result[i]['name']);
                    if ('dept_multiple' in params) {
                        $(params.dept_multiple).append(opt.clone().prop('selected', true));
                    }
                    if ('dept_start' in params) {
                        $(params.dept_start).append(opt.clone());
                    }
                    if ('dept_end' in params) {
                        $(params.dept_end).append(opt);
                    }
                }
            }

            // selecting the blank entry should reset the form to its
            // initial state with both department selects containing the
            // full list and set to one
            if (resp.result.length > 0 && super_id != -1) {
                if ('dept_start' in params && 'dept_start_id' in params) {
                    $(params.dept_start_id).val($(params.dept_start).val());
                }
                if ('dept_end' in params) {
                    $(params.dept_end).val(resp.result[resp.result.length-1]['id']);
                    if ('dept_end_id' in params) {
                        $(params.dept_end_id).val($(params.dept_end).val());
                    }
                }
            } else if (resp.result.length > 0) {
                if ('dept_start' in params && 'dept_start_id' in params) {
                    $(params.dept_start_id).val($(params.dept_start).val());
                }
                if ('dept_end' in params && 'dept_end_id' in params) {
                    $(params.dept_end_id).val($(params.dept_end).val());
                }
            }

            if ('callback' in params) {
                params.callback();
            }
        }
    });
}

/**
  Select chaining. When a department is selected,
  lookup child subdepartments and populate field(s). Any
  non-existant subdepartment field selector is ignored
  @param ws_url [string] webservices URL
  @param params [object] with additional named parameters
    - super_id [jQuery selector] super department ID
    - dept_start [jQuery selector] start of department range
    - dept_end [jQuery selector] end of department range
    - sub_multiple [jQuery selector] multiple <select> for sub departments
    - sub_start [jQuery selector] single <select> for sub range start
    - sub_end [jQuery selector] single <select> for sub range end
    - callback [function] called after chaining
*/
function chainSubDepartments(ws_url, params)
{
    params = validateSelectorProperties(params, ['super_id', 'dept_start', 'dept_end', 'sub_multiple', 'sub_start', 'sub_end']);
    if (!('super_id' in params)) {
        throw "chainSubDepartments: super_id parameter is required";
    }
    if (!('dept_start' in params)) {
        throw "chainSubDepartments: dept_start parameter is required";
    }
    if (!('dept_end' in params)) {
        throw "chainSubDepartments: dept_end parameter is required";
    }
    if (!('sub_multiple' in params) && !('sub_start' in params) && !('sub_end' in params)) {
        throw "chainSubDepartments: sub_multiple, sub_start, or sub_end is required";
    }
    if ('callback' in params && typeof params.callback != 'function') {
        delete params.callback;
    }

    var range = [ $(params.dept_start).val(), $(params.dept_end).val() ];
    var sID = $(params.super_id).val();
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
        url: ws_url,
        type: 'post',
        data: JSON.stringify(req),
        dataType: 'json',
        contentType: 'application/json',
        success: function(resp) {
            if (resp.result) {
                ['sub_multiple', 'sub_start', 'sub_end'].forEach(function(p){
                    if (p in params) {
                        $(params[p]).empty();
                        if (p != 'sub_multiple') {
                            $(params[p]).append($('<option value="">Select sub department</option>'));
                        }
                    }
                });
                for (var i=0; i<resp.result.length; i++) {
                    var opt = $('<option>').val(resp.result[i]['id'])
                        .html(resp.result[i]['id'] + ' ' + resp.result[i]['name']);
                    if ('sub_multiple' in params) {
                        $(params.sub_multiple).append(opt.clone());
                    }
                    if ('sub_start' in params) {
                        $(params.sub_start).append(opt.clone());
                    }
                    if ('sub_end' in params) {
                        $(params.sub_end).append(opt);
                    }
                }
            }

            if ('callback' in params) {
                params.callback();
            }
        }
    });
}

