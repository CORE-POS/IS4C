<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (!function_exists('getTenderTable')) {
    include('ajax.php');
}

class TenderEditor extends FanniePage 
{

    protected $title = "Fannie : Tenders";
    protected $header = "Tenders";
    protected $must_authenticate = True;
    protected $auth_classes = array('tenders');
    public $description = '[Tenders] creates and updates tender types.';
    public $themed = true;

    function javascript_content(){
        ob_start();
        ?>
function saveCode(val,t_id){
    var elem = $(this);
    var orig = this.defaultValue;
    $.ajax({url:'ajax.php',
        cache:false,
        data: 'saveCode='+val+'&id='+t_id,
        success: function(data){
            var timeout=1500;
            if (data == "") {
                data = 'Saved!';
            } else {
                elem.val(orig);
                timeout = 3000;
            }
            elem.popover({
                html: true,
                content: data,
                placement: 'auto bottom'
            });
            elem.popover('show');
            setTimeout(function(){elem.popover('destroy') }, timeout);
        }   
    });
}
function saveName(val,t_id){
    var elem = $(this);
    var orig = this.defaultValue;
    $.ajax({url:'ajax.php',
        cache:false,
        data: 'saveName='+val+'&id='+t_id,
        success: function(data){
            var timeout=1500;
            if (data == "") {
                data = 'Saved!';
            } else {
                elem.val(orig);
                timeout = 3000;
            }
            elem.popover({
                html: true,
                content: data,
                placement: 'auto bottom'
            });
            elem.popover('show');
            setTimeout(function(){elem.popover('destroy') }, timeout);
        }
    });
}
function saveType(val,t_id){
    var elem = $(this);
    var orig = this.defaultValue;
    $.ajax({url:'ajax.php',
        cache:false,
        data: 'saveType='+val+'&id='+t_id,
        success: function(data){
            var timeout=1500;
            if (data == "") {
                data = 'Saved!';
            } else {
                elem.val(orig);
                timeout = 3000;
            }
            elem.popover({
                html: true,
                content: data,
                placement: 'auto bottom'
            });
            elem.popover('show');
            setTimeout(function(){elem.popover('destroy') }, timeout);
        }   
    });
}
function saveCMsg(val,t_id){
    var elem = $(this);
    var orig = this.defaultValue;
    $.ajax({url:'ajax.php',
        cache:false,
        data: 'saveCMsg='+val+'&id='+t_id,
        success: function(data){
            var timeout=1500;
            if (data == "") {
                data = 'Saved!';
            } else {
                elem.val(orig);
                timeout = 3000;
            }
            elem.popover({
                html: true,
                content: data,
                placement: 'auto bottom'
            });
            elem.popover('show');
            setTimeout(function(){elem.popover('destroy') }, timeout);
        }   
    });
}
function saveMin(val,t_id){
    var elem = $(this);
    var orig = this.defaultValue;
    $.ajax({url:'ajax.php',
        cache:false,
        data: 'saveMin='+val+'&id='+t_id,
        success: function(data){
            var timeout=1500;
            if (data == "") {
                data = 'Saved!';
            } else {
                elem.val(orig);
                timeout = 3000;
            }
            elem.popover({
                html: true,
                content: data,
                placement: 'auto bottom'
            });
            elem.popover('show');
            setTimeout(function(){elem.popover('destroy') }, timeout);
        }   
    });
}
function saveMax(val,t_id){
    var elem = $(this);
    var orig = this.defaultValue;
    $.ajax({url:'ajax.php',
        cache:false,
        data: 'saveMax='+val+'&id='+t_id,
        success: function(data){
            var timeout=1500;
            if (data == "") {
                data = 'Saved!';
            } else {
                elem.val(orig);
                timeout = 3000;
            }
            elem.popover({
                html: true,
                content: data,
                placement: 'auto bottom'
            });
            elem.popover('show');
            setTimeout(function(){elem.popover('destroy') }, timeout);
        }   
    });
}
function saveRLimit(val,t_id){
    var elem = $(this);
    var orig = this.defaultValue;
    $.ajax({url:'ajax.php',
        cache:false,
        data: 'saveRLimit='+val+'&id='+t_id,
        success: function(data){
            var timeout=1500;
            if (data == "") {
                data = 'Saved!';
            } else {
                elem.val(orig);
                timeout = 3000;
            }
            elem.popover({
                html: true,
                content: data,
                placement: 'auto bottom'
            });
            elem.popover('show');
            setTimeout(function(){elem.popover('destroy') }, timeout);
        }   
    });
}
function addTender(){
    $.ajax({url:'ajax.php',
        cache: false,
        data:'newTender=yes',
        success: function(data){
            $('#mainDisplay').html(data);
        }
    });
}
        <?php
        return ob_get_clean();
    }

    function body_content()
    {
        $ret = '<div id="alert-area"></div>';
        $ret .= '<div id="mainDisplay">';
        $ret .= getTenderTable();
        $ret .= '</div>';
        return $ret;
    }

    public function helpContent()
    {
        return '<p>Tenders are different kinds of payment the store accepts.
            Each field saves when changed.</p>
            <ul>
                <li><em>Code</em> is the two letter code used by cashiers to enter
                the tender. These codes must be unique. While they are editable, using
                the defaults defined in sample tenders is recommended. In particular,
                changing CA, MI, CP, IC, EF, or FS could lead to oddities.</li>
                <li><em>Name</em> appears on screen and on receipt.</li>
                <li><em>Change Type</em> is the tender code used when the amount tendered
                exceeds the amount due resulting in a change line. Cash (CA) is
                most common.</li>
                <li><em>Change Msg</em> appears on screen and receipts for change lines.</li>
                <li><em>Min</em> and <em>Max</em> are soft limits. Attempting to tender 
                an amount outside that range results in a warning.</li>
                <li><em>Refund Limit</em> is a soft limit on the maximum allowed refund.
                Attempting to refund a larger amount results in a warning.</li>
            </ul>';
    }
}

FannieDispatch::conditionalExec(false);

?>
