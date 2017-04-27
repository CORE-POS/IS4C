<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op.

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

namespace COREPOS\pos\lib\Notifiers;
use COREPOS\pos\lib\Notifier;
use \CoreLocal;

/**
  Display a 10-key touchscreen UI
*/
class NumPad extends Notifier 
{
    /**
      Display the notification
      @return [string] html
    */
    public function draw()
    {
        if (!CoreLocal::get('touchscreen')) {
            return '';
        }

        return <<<HTML
<script type="text/javascript">
/**
  This tries to keep track of which input
  element most recently had focus and keep
  focus on that element even though it's
  temporarily lost when a number button
  is pressed.
*/
var numpad = (function ($) {
    var mod = {};
    
    var inputElement = null;
    var getInput = function() {
        if (inputElement === null) {
            inputElement = $(':input:focus');
        }
        return inputElement;
    };

    mod.setInput = function(elem) {
        inputElement = elem;
    };

    mod.write = function(text) {
        var elem = getInput();
        console.log(elem);
        if (elem.prop('tagName') === 'INPUT') {
            elem.val(elem.val().toString() + text);
        }
        elem.focus();
    };

    mod.enter = function() {
        if (typeof 'pos2.submitWrapper' == 'function') {
            pos2.submitWrapper();
        } else if (typeof 'submitWrapper' == 'function') {
            submitWrapper();
        } else {
            getInput().closest('form').submit();
        }
    };

    mod.clear = function() {
        var elem = getInput();
        if (elem.prop('tagName') == 'SELECT') {
            elem.append('<option value="" />').val('');
        } else {
            elem.val('CL');
        }
        mod.enter();
    };

    return mod;
})(jQuery);
$(document).ready(function() {
    $(':input').focus(function() {
        if ($(this).prop('tagName') !== 'BUTTON') {
            numpad.setInput($(this));
        }
    });
});
</script>
<div class="numpad">
    <div class="numpad-row">
        <button class="pos-button numpad-btn" onclick="numpad.write('7');">7</button>
        <button class="pos-button numpad-btn" onclick="numpad.write('8');">8</button>
        <button class="pos-button numpad-btn" onclick="numpad.write('9');">9</button>
    </div>
    <div class="numpad-row">
        <button class="pos-button numpad-btn" onclick="numpad.write('4');">4</button>
        <button class="pos-button numpad-btn" onclick="numpad.write('5');">5</button>
        <button class="pos-button numpad-btn" onclick="numpad.write('6');">6</button>
    </div>
    <div class="numpad-row">
        <button class="pos-button numpad-btn" onclick="numpad.write('1');">1</button>
        <button class="pos-button numpad-btn" onclick="numpad.write('2');">2</button>
        <button class="pos-button numpad-btn" onclick="numpad.write('3');">3</button>
    </div>
    <div class="numpad-row">
        <button class="pos-button numpad-btn" onclick="numpad.write('0');">0</button>
        <button class="pos-button numpad-btn" onclick="numpad.write('00');">00</button>
        <button class="pos-button numpad-btn" onclick="numpad.write('.');">.</button>
    </div>
    <div class="numpad-row">
        <button class="pos-button numpad-wide" onclick="numpad.enter();">Enter</button>
    </div>
    <div class="numpad-row">
        <button class="pos-button numpad-wide" onclick="numpad.clear();">Clear</button>
    </div>
</div>
HTML;
    }
}

