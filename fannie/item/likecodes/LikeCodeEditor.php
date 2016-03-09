<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (!function_exists('checkLogin')) {
    include_once($FANNIE_ROOT.'auth/login.php');
}

class LikeCodeEditor extends FanniePage {
    protected $title = "Fannie : Like Codes";
    protected $header = "Like Codes";
    protected $must_authenticate = True;
    protected $auth_classes = array('manage_likecodes');
    private $msgs = "";

    public $description = '[Like Code Editor] creates and deletes like codes.';
    public $themed = true;

    function preprocess(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $msg = '';
        $msg_type = '';
        if (FormLib::get_form_value('submit') !== ''){
            $lc = $_REQUEST['newlc'];
            $lc = FormLib::get_form_value('newlc',0);
            $name = FormLib::get_form_value('newlcname','');

            if (!is_numeric($lc)) {
                $msg .= $lc . " is not a number";
                $msg_type = 'danger';
            } else {
                $chkP = $dbc->prepare('SELECT * FROM likeCodes WHERE likeCode=?');
                $chk = $dbc->execute($chkP,array($lc));
                if ($dbc->num_rows($chk) > 0){
                    $upP = $dbc->prepare("UPDATE likeCodes SET
                        likeCodeDesc=?
                        WHERE likeCode=?");
                    $upR = $dbc->execute($upP,array($name,$lc));
                    $msg .= "LC #$lc renamed $name";
                    $msg_type .= 'success';
                } else {
                    $insP = $dbc->prepare('INSERT INTO likeCodes 
                            (likeCode,likeCodeDesc) VALUES (?,?)');
                    $insR = $dbc->execute($insP,array($lc,$name));
                    $msg .= "LC #$lc ($name) created";
                    $msg_type = 'success';
                }
            }
        } elseif (FormLib::get_form_value('submit2') !== '') {
            $lc = $_REQUEST['lcselect'];
            $lc = FormLib::get_form_value('lcselect',0);

            $q1 = $dbc->prepare('DELETE FROM likeCodes WHERE likeCode=?');
            $q2 = $dbc->prepare('DELETE FROM upcLike WHERE likeCode=?');
            $dbc->execute($q1,array($lc));
            $dbc->execute($q2,array($lc));
    
            $msg .= "LC #$lc has been deleted<br />";
            $msg_type = 'success';
        }

        if (!empty($msg_type)) {
            $alert = '<div class="alert alert-' . $msg_type . '" role="alert">'
                . '<button type="button" class="close" data-dismiss="alert">'
                . '<span>&times;</span></button>'
                . $msg . '</div>';
            $this->add_onload_command("\$('div.navbar-default').after('{$alert}');");
        }

        return true;
    }


    function javascript_content(){
        ob_start();
        ?>
function loadlc(id){
    $.ajax({
        url: 'LikeCodeAjax.php',
        type: 'get',
        data: 'id='+id
    }).fail(function(request,error){
        console.log(request);
        console.log(error);
    }).done(function(resp){
        $('#rightdiv').html(resp);
    });
}
        <?php
        return ob_get_clean();
    }

    function body_content(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $opts = "";
        $p = $dbc->prepare("SELECT likeCode,likeCodeDesc FROM likeCodes ORDER BY likeCode");
        $res = $dbc->execute($p);
        while ($row = $dbc->fetch_row($res)) {
            $opts .= "<option value=\"$row[0]\">$row[0] $row[1]</option>";
        }

        ob_start();
        ?>
        <div id="leftdiv" class="col-sm-6">
            <form action="LikeCodeEditor.php" method="get"
                class="form form-horizontal">
            <select id="lcselect" name="lcselect" 
                class="form-control"
                size=15 onchange="loadlc(this.value);">
            <?php echo $opts; ?>
            </select><p />
            <div class="form-group col-sm-3">
                <label class="col-sm-4 control-label">#</label>
                <div class="col-sm-8">
                <input class="form-control" type=text name=newlc value="" />
                </div>
            </div>
            <div class="form-group col-sm-9">
                <label class="col-sm-4 control-label">Name</label>
                <div class="col-sm-8">
                <input class="form-control" type=text name=newlcname value="" />
                </div>
            </div>
            <p>
                <button type="submit" name="submit" value="1" class="btn btn-default">Add/Rename LC</button>
                <button type="submit" name="submit2" value="1"
                    onclick="return confirm('Are you sure you want to delete LC #'+$('#lcselect').val()+'?');"
                    class="btn btn-default">
                    Delete Selected LC</button>
            </p>
            </form>
        </div>
        <div id="rightdiv" class="col-sm-6">
        </div>
        <?php

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>Like codes are used to group multiple items
            together and treat them as a single item. Editing any
            item that belongs to a like code will update all items
            that belong to that like code. A common use case is produce
            where the same fruit or vegetable is sourced from multiple
            vendors with differing PLUs and/or UPCs. Using like code
            ensures consistency since changes are automatically applied
            to all items in the like code.</p>
            <p>This tool is just for creating, renaming, and deleting
            like codes. Use the item editor to assign a particular item
            to a like code.</p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
        $phpunit->assertNotEquals(0, strlen($this->javascript_content()));
    }
}

FannieDispatch::conditionalExec();

