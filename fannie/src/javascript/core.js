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
  Select chaining. When a super department is selected,
  lookup child departments and populate field(s). Any
  non-existant department field selector is ignored
  @param ws_url [string] webservices URL
  @param super_id [int] super department ID
  @param dept_multi [jQuery selector] multiple <select> element
  @param dept_start_s [jQuery selector] single <select> for range start
  @param dept_end_s [jQuery selector] single <select> for range end
  @param dept_start_t [jQuery selector] single <input> for numeric range start
  @param dept_end_t [jQuery selector] single <input> for numeric range end
*/
function chainSuperDepartment(ws_url, super_id, dept_multi, dept_start_s, dept_end_s, dept_start_t, dept_end_t, callback)
{
    if (super_id === '' || super_id === '0') {
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
                if ($(dept_multi).length > 0) {
                    $(dept_multi).empty();
                }
                if ($(dept_start_s).length > 0) {
                    $(dept_start_s).empty();
                }
                if ($(dept_end_s).length > 0) {
                    $(dept_end_s).empty();
                }
                for (var i=0; i<resp.result.length; i++) {
                    var opt = $('<option>').val(resp.result[i]['id'])
                        .html(resp.result[i]['id'] + ' ' + resp.result[i]['name']);
                    if ($(dept_multi).length > 0) {
                        $(dept_multi).append(opt.clone().prop('selected', true));
                    }
                    if ($(dept_start_s).length > 0) {
                        $(dept_start_s).append(opt.clone());
                    }
                    if ($(dept_end_s).length > 0) {
                        $(dept_end_s).append(opt);
                    }
                }
            }

            // selecting the blank entry should reset the form to its
            // initial state with both department selects containing the
            // full list and set to one
            if (resp.result.length > 0 && super_id != -1) {
                if ($(dept_start_s).length > 0 && $(dept_start_t).length > 0) {
                    $(dept_start_s).val(resp.result[0]['id']);
                    $(dept_start_t).val(resp.result[0]['id']);
                }
                if ($(dept_end_s).length > 0 && $(dept_end_t).length > 0) {
                    $(dept_end_s).val(resp.result[0]['id']);
                    $(dept_end_t).val(resp.result[0]['id']);
                }
            } else if (resp.result.length > 0) {
                if ($(dept_start_s).length > 0 && $(dept_start_t).length > 0) {
                    $(dept_start_t).val($(dept_start_s).val());
                }
                if ($(dept_end_s).length > 0 && $(dept_end_t).length > 0) {
                    $(dept_end_t).val($(dept_end_s).val());
                }
            }

            if (typeof callback == 'function') {
                callback();
            }
        }
    });
}

/**
  Select chaining. When a department is selected,
  lookup child subdepartments and populate field(s). Any
  non-existant subdepartment field selector is ignored
  @param ws_url [string] webservices URL
  @param super_s [jQuery selector] super department ID
  @param dept_start [jQuery selector] start of department range
  @param dept_end [jQuery selector] end of department range
  @param sub_multi [jQuery selector] multiple <select> for sub departments
  @param sub_start [jQuery selector] single <select> for sub range start
  @param sub_end [jQuery selector] single <select> for sub range end
*/
function chainSubDepartments(ws_url, super_s, dept_start, dept_end, sub_multi, sub_start, sub_end, callback)
{
    var range = [ $(dept_start).val(), $(dept_end).val() ];
    var sID = $(super_s).val();
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
                if ($(sub_multi).length > 0) {
                    $(sub_multi).empty();
                }
                if ($(sub_start).length > 0) {
                    $(sub_start).empty();
                    $(sub_start).append($('<option value="">Select sub department</option>'));
                }
                if ($(sub_end).length > 0) {
                    $(sub_end).empty();
                    $(sub_end).append($('<option value="">Select sub department</option>'));
                }
                for (var i=0; i<resp.result.length; i++) {
                    var opt = $('<option>').val(resp.result[i]['id'])
                        .html(resp.result[i]['id'] + ' ' + resp.result[i]['name']);
                    if ($(sub_multi).length > 0) {
                        $(sub_multi).append(opt.clone().prop('selected', true));
                    }
                    if ($(sub_start).length > 0) {
                        $(sub_start).append(opt.close());
                    }
                    if ($(sub_end).length > 0) {
                        $(sub_end).append(opt);
                    }
                }
            }

            if (typeof callback == 'function') {
                callback();
            }
        }
    });
}

