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
include('ajax.php');

class TenderEditor extends FanniePage {

    protected $title = "Fannie : Tenders";
    protected $header = "Tenders";
    protected $must_authenticate = True;
    protected $auth_classes = array('tenders');
    public $description = '[Tenders] creates and updates tender types.';

    function javascript_content(){
        ob_start();
        ?>
function saveCode(val,t_id){
    $.ajax({url:'ajax.php',
        cache:false,
        data: 'saveCode='+val+'&id='+t_id,
        success: function(data){
            if (data != "")
                alert(data);
        }   
    });
}
function saveName(val,t_id){
    $.ajax({url:'ajax.php',
        cache:false,
        data: 'saveName='+val+'&id='+t_id,
        success: function(data){
            if (data != "")
                alert(data);
        }   
    });
}
function saveType(val,t_id){
    $.ajax({url:'ajax.php',
        cache:false,
        data: 'saveType='+val+'&id='+t_id,
        success: function(data){
            if (data != "")
                alert(data);
        }   
    });
}
function saveCMsg(val,t_id){
    $.ajax({url:'ajax.php',
        cache:false,
        data: 'saveCMsg='+val+'&id='+t_id,
        success: function(data){
            if (data != "")
                alert(data);
        }   
    });
}
function saveMin(val,t_id){
    $.ajax({url:'ajax.php',
        cache:false,
        data: 'saveMin='+val+'&id='+t_id,
        success: function(data){
            if (data != "")
                alert(data);
        }   
    });
}
function saveMax(val,t_id){
    $.ajax({url:'ajax.php',
        cache:false,
        data: 'saveMax='+val+'&id='+t_id,
        success: function(data){
            if (data != "")
                alert(data);
        }   
    });
}
function saveRLimit(val,t_id){
    $.ajax({url:'ajax.php',
        cache:false,
        data: 'saveRLimit='+val+'&id='+t_id,
        success: function(data){
            if (data != "")
                alert(data);
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

    function body_content(){
        $ret = '<div id="mainDisplay">';
        $ret .= getTenderTable();
        $ret .= '</div>';
        return $ret;
    }
}

FannieDispatch::conditionalExec(false);

?>
