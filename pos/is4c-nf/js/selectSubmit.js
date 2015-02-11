/**
  Standard javascript for handling keypress events on
  a <select> object. Pressing <enter> submits the form.
  Pressing "CL" immediately prior to <enter> submits
  the form with the value blank.
  @param selector [string] valid jquery selector for the <select> element
  @param myform [string] valid jquery selector for the <form> element
*/
function selectSubmit(selector, myform) {

    var enterDown = 0;
    var enterUp = 0;
    var prevKey = 0;
    var prevPrevKey = 0;

    $(selector).keydown(function (e){
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
    });

    $(selector).keyup(function (e){
        var jsKey; 
        if (e.which) {
            jsKey = e.which;
        } else if (e.keyCode) {
            jsKey = e.keyCode;
        }

        if (jsKey == 13 && enterDown == 1 && enterUp == 0) {
            enterUp = 1;
            if ( (prevPrevKey == 99 || prevPrevKey == 67) && (prevKey == 108 || prevKey == 76) ) {
                $(selector+' option:selected').val('');
            }
            $(myform).submit();
            console.log('submitting');
            console.log($(myform).length);
        }

        /* Ignore Shift (16) and CapsLock (20) */
        if (jsKey > 31) {
            prevPrevKey = prevKey;
            prevKey = jsKey;
        }
    });
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

