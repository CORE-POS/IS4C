<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

    function preprocess(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        if (FormLib::get_form_value('submit') !== ''){
            $lc = $_REQUEST['newlc'];
            $lc = FormLib::get_form_value('newlc',0);
            $name = FormLib::get_form_value('newlcname','');

            if (!is_numeric($lc))
                $this->msgs .= "<div style=\"color:red;\">Error: $lc is not a number</div>";
            else {
                $chkP = $dbc->prepare_statement('SELECT * FROM likeCodes WHERE likeCode=?');
                $chk = $dbc->exec_statement($chkP,array($lc));
                if ($dbc->num_rows($chk) > 0){
                    $upP = $dbc->prepare_statement("UPDATE likeCodes SET
                        likeCodeDesc=?
                        WHERE likeCode=?");
                    $upR = $dbc->exec_statement($upP,array($name,$lc));
                    $this->msgs .= "LC #$lc renamed $name<br />";
                }
                else {
                    $insP = $dbc->prepare_statement('INSERT INTO likeCodes 
                            (likeCode,likeCodeDesc) VALUES (?,?)');
                    $insR = $dbc->exec_statement($insP,array($lc,$name));
                    $this->msgs .= "LC #$lc ($name) created<br />";
                }
            }
        }
        elseif (FormLib::get_form_value('submit2') !== ''){
            $lc = $_REQUEST['lcselect'];
            $lc = FormLib::get_form_value('lcselect',0);

            $q1 = $dbc->prepare_statement('DELETE FROM likeCodes WHERE likeCode=?');
            $q2 = $dbc->prepare_statement('DELETE FROM upcLike WHERE likeCode=?');
            $dbc->exec_statement($q1,array($lc));
            $dbc->exec_statement($q2,array($lc));
    
            $this->msgs .= "LC #$lc has been deleted<br />";
        }
        return True;
    }


    function javascript_content(){
        ob_start();
        ?>
function loadlc(id){
    $.ajax({
        url: 'ajax.php',
        type: 'get',
        data: 'lc='+id+'&action=fetch',
        error: function(request,error){
            console.log(request);
            console.log(error);
        },
        success: function(resp){
            $('#rightdiv').html(resp);
        }
    });
}
        <?php
        return ob_get_clean();
    }

    function body_content(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $opts = "";
        $p = $dbc->prepare_statement("SELECT likeCode,likeCodeDesc FROM likeCodes ORDER BY likeCode");
        $res = $dbc->exec_statement($p);
        while($row = $dbc->fetch_row($res))
            $opts .= "<option value=\"$row[0]\">$row[0] $row[1]</option>";

        $ret = '';
        if (!empty($msgs)){
            $ret .= "<blockquote style=\"border:solid 1px black;
                padding:4px;\">$msgs</blockquote>";
        }
        ob_start();
        ?>
        <form action="LikeCodeEditor.php" method="get">
        <div style="width: 100%;">
            <div id="leftdiv" style="float: left;">
            <select id="lcselect" name="lcselect" 
                size=15 onchange="loadlc(this.value);">
            <?php echo $opts; ?>
            </select><p />
            <b>#</b>: <input type=text size=2 name=newlc value="" />
            <b>Name</b>: <input type=text size=6 name=newlcname value="" />
            <input type=submit name=submit value="Add/Rename LC" /><p />
            <input type=submit name=submit2 
                onclick="return confirm('Are you sure you want to delete LC #'+$('#lcselect').val()+'?');"
                value="Delete Selected LC" />
            </div>
            <div id="rightdiv" style="float: left; margin-left: 10px; font-size:85%;">
            </div>
        </div>
        <div style="clear:left;"></div>
        </form>
        <?php
        $ret .= ob_get_clean();
        return $ret;
    }

}

FannieDispatch::conditionalExec(false);

?>
