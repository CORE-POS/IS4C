/**
  Standard javascript for handling keypress events on
  a <select> object. Pressing <enter> submits the form.
  Pressing "CL" immediately prior to <enter> submits
  the form with the value blank.
  @param selector [string] valid jquery selector for the <select> element
  @param myform [string] valid jquery selector for the <form> element
  $param filter_selector [string, optional] valid jquery selector for displaying
    current filter string
*/
function selectSubmit(selector, myform, filter_selector) {

    var enterDown = 0;
    var enterUp = 0;
    var prevKey = 0;
    var prevPrevKey = 0;
    var filter_string = '';
    var disabled = true;

    $(selector).keydown(function (e){
        if (disabled) {
            return;
        }
        var jsKey; 
        if (e.which) {
            jsKey = e.which;
        } else if (e.keyCode) {
            jsKey = e.keyCode;
        }

        if (jsKey == 13) {
            enterDown = 1;
        } else {
            enterDown = 0;
        }

        /**
          Prevent backspace is probably good in
          all cases, but we need it in keyup
          to edit the filtering string
        */
        if (jsKey == 8) {
            e.preventDefault();
            e.stopPropagation();
        }

    });

    $(selector).keyup(function (e){
        if (disabled) {
            return;
        }
        var jsKey; 
        if (e.which) {
            jsKey = e.which;
        } else if (e.keyCode) {
            jsKey = e.keyCode;
        }

        if (jsKey == 13 && enterDown == 1 && enterUp == 0) {
            enterUp = 1;
            if ( (prevPrevKey == 99 || prevPrevKey == 67) && (prevKey == 108 || prevKey == 76) ) {
                /**
                  Filtering may have hidden ALL options in the select
                  Add one back if necessary
                */
                if ($(selector+' option').length == 0) {
                    var opt = $('<option>').val('');
                    $(selector).append(opt);
                } else {
                    $(selector+' option:first').val('');
                }
                $(selector).val('');
            }
            $(myform).submit();
        } else if (filter_selector) {
            /**
              Filter options in the select
            */
            var filter_changed = false;
            if (isFilterKey(jsKey)) {
                filter_string += String.fromCharCode(jsKey);
                filter_changed = true;
            } else if (jsKey == 8) {
                e.preventDefault();
                e.stopPropagation();
                if (filter_string.length > 0) {
                    filter_string = filter_string.substring(0, filter_string.length-1);
                    filter_changed = true;
                }
            }
            if (filter_changed && filter_string.length > 0) {
                $(filter_selector).html('Filter: ' + filter_string);
                var re = new RegExp(filter_string, "i");
                /**
                  Add a hidden select field and move all the options
                  out of the visible select into the hidden select.
                  Then do the filter search and copy matches into
                  the visible select.

                  Merely hiding elements in the visible select
                  interferes with how arrow keys work. It will "scroll"
                  through all the hidden options.
                */
                if ($('#hidden-filter-select').length == 0) {
                    var new_select = $('<select id="hidden-filter-select">').css('display','none');
                    $('body').append(new_select);
                    $(selector+' option').each(function(){
                        $('#hidden-filter-select').append($(this));
                    });
                }
                $(selector).empty();
                $('#hidden-filter-select option').each(function(){
                    if ($(this).html().match(re)) {
                        $(selector).append($(this).clone());
                    }
                });
                $(selector).val($(selector+' option:first').val());
            } else if (filter_changed) {
                $(filter_selector).html('');
                $(selector).empty();
                $('#hidden-filter-select option').each(function(){
                    $(selector).append($(this).clone());
                });
                $(selector).val($(selector+' option:first').val());
            }
        }

        /* Ignore Shift (16) and CapsLock (20) */
        if (jsKey > 31) {
            prevPrevKey = prevKey;
            prevKey = jsKey;
        }
    });

    setTimeout(function(){disabled=false;}, 500);
}

function isFilterKey(keyCode)
{
    if (keyCode >= 65 && keyCode <= 90) {
        return true;
    } else if (keyCode >= 97 && keyCode <= 122) {
        return true;
    } else if (keyCode >= 48 && keyCode <= 57) {
        return true; // digits
    } else if (keyCode == 32) {
        return true; // space
    } else if (keyCode == 44) {
        return true; // comma
    } else if (keyCode == 46) {
        return true; // period
    } else {
        return false;
    }
}

function scrollUp(selector)
{
    var rawElement = $(selector)[0];
    if (rawElement.selectedIndex > 0) {
        rawElement.selectedIndex = rawElement.selectedIndex - 1;
    }
    $(selector).focus();
}

function scrollDown(selector)
{
    var rawElement = $(selector)[0];
    var max = $(selector + ' option').length - 1;
    if (rawElement.selectedIndex < max) {
        rawElement.selectedIndex = rawElement.selectedIndex + 1;
    }
    $(selector).focus();
}

function pageUp(selector)
{
    var rawElement = $(selector)[0];
    var viewportSize = rawElement.size;
    if (rawElement.selectedIndex - viewportSize < 0) {
        rawElement.selectedIndex = 0;
    } else {
        rawElement.selectedIndex = rawElement.selectedIndex - viewportSize;
    }
    $(selector).focus();
}

function pageDown(selector)
{
    var rawElement = $(selector)[0];
    var viewportSize = rawElement.size;
    var max = $(selector + ' option').length - 1;
    if (rawElement.selectedIndex + viewportSize > max) {
        rawElement.selectedIndex = max;
    } else {
        rawElement.selectedIndex = rawElement.selectedIndex + viewportSize;
    }
    $(selector).focus();
}

