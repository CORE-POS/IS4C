<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

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

include_once(dirname(__FILE__).'/../../config.php');
include_once(dirname(__FILE__).'/../../classlib2.0/FannieAPI.php');

class LikeCodeModule extends ItemModule {

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        global $FANNIE_URL;
        $dbc = $this->db();
        $p = $dbc->prepare_statement('SELECT likeCode FROM upcLike WHERE upc=?');
        $r = $dbc->exec_statement($p,array($upc));
        $myLC = -1;     
        if ($dbc->num_rows($r) > 0) {
            $w = $dbc->fetch_row($r);
            $myLC = $w['likeCode'];
        }
        $ret = '<fieldset id="LikeCodeFieldSet">';
        $ret .=  "<legend onclick=\"\$('#LikeCodeFieldsetContent').toggle();\">
                <a href=\"\" onclick=\"return false;\">Likecode</a>
                </legend>";
        $style = '';
        if ($expand_mode == 1) {
            $style = '';
        } else if ($expand_mode == 2 && $myLC != -1) {
            $style = '';
        } else {
            $style = 'display:none;';
        }
        $ret .= '<div id="LikeCodeFieldsetContent" style="' . $style . '">';


        $ret .= "<table border=0><tr><td><b>Like code</b> <button type=\"button\" id=\"lcAddButton\">+</button> ";
        $ret .= "<select name=likeCode style=\"{width: 175px;}\"
                onchange=\"updateLcModList(this.value);\">";
        $ret .= "<option value=-1>(none)</option>";
    
        $p = $dbc->prepare_statement('SELECT likeCode, likeCodeDesc FROM likeCodes ORDER BY likeCode');
        $r = $dbc->exec_statement($p);
        while($w = $dbc->fetch_row($r)){
            $ret .= sprintf('<option %s value="%d">%d %s</option>',
                ($w['likeCode'] == $myLC ? 'selected': ''),
                $w['likeCode'],$w['likeCode'],$w['likeCodeDesc']
            );
        }
        $ret .= "</select></td>";
        $ret .= "<td><input type=checkbox name=LikeCodeNoUpdate value='noupdate'>Check to not update like code items</td>
            </tr><tr>";
        $ret .= '<td id="LikeCodeItemList">';
        $ret .= $this->LikeCodeItems($myLC, $upc);
        $ret .= '</td>';
        $ret .= '<td id="LikeCodeHistoryLink" valign="top">';
        $ret .= $this->HistoryLink($myLC);  
        $ret .= '</td>';
        $ret .= '</tr></table></fieldset>';

        $ret .= '<div id="addLikeCodeDialog" title="Add Like Code">';
        $ret .= '<span id="addLikeAreaAlert" style="color:red;"></span>';
        $ret .= '<fieldset>';
        $ret .= '<label for="newLikeID">LC #</label>';
        $ret .= '<input type="text" name="newLC" id="newLikeID" style="display:block;" />';
        $ret .= '<label for="newLikeName">LC Name</label>';
        $ret .= '<input type="text" name="lcName" id="newLikeName" style="display:block;" />';
        $ret .= '</fieldset>';
        $ret .= '</div>';

        return $ret;
    }

    function SaveFormData($upc){
        $lc = FormLib::get_form_value('likeCode');  
        $dbc = $this->db();

        $delP = $dbc->prepare_statement('DELETE FROM upcLike WHERE upc=?'); 
        $delR = $dbc->exec_statement($delP,array($upc));
        if ($lc == -1){
            return ($delR === False) ? False : True;
        }

        $insP = 'INSERT INTO upcLike (upc,likeCode) VALUES (?,?)';
        $insR = $dbc->exec_statement($insP,array($upc,$lc));
        
        if (FormLib::get_form_value('LikeCodeNoUpdate') == 'noupdate'){
            return ($insR === False) ? False : True;
        }

        /* get values for current item */
        $valuesP = $dbc->prepare_statement('SELECT normal_price,pricemethod,groupprice,quantity,
            department,scale,tax,foodstamp,discount,qttyEnforced,local
            FROM products WHERE upc=?');
        $valuesR = $dbc->exec_statement($valuesP,array($upc));  
        if ($dbc->num_rows($valuesR) == 0) return False;
        $values = $dbc->fetch_row($valuesR);

        /* apply current values to other other items
           in the like code */
        $upcP = $dbc->prepare_statement('SELECT upc FROM upcLike WHERE likeCode=? AND upc<>?');
        $upcR = $dbc->exec_statement($upcP,array($lc,$upc));
        while($upcW = $dbc->fetch_row($upcR)){
            ProductsModel::update($upcW['upc'],$values, true);
            updateProductAllLanes($upcW['upc']);
        }
        return True;
    }

    public function getFormJavascript($upc)
    {
        global $FANNIE_URL;
        ob_start();
        ?>
        function updateLcModList(val){
            $.ajax({
                url: '<?php echo $FANNIE_URL; ?>item/modules/LikeCodeModule.php',
                data: 'lc='+val,
                dataType: 'json',
                cache: false,
                success: function(data){
                    if (data.items){
                        $('#LikeCodeItemList').html(data.items);
                    }
                    if (data.link){
                        $('#LikeCodeHistoryLink').html(data.link);
                        $('#LikeCodeHistoryLink a.fancyboxLink').fancybox();
                    }
                }
            });
        }
        function addLcDialog()
        {
            var lc_dialog = $('#addLikeCodeDialog').dialog({
                autoOpen: false,
                height: 300,
                width: 300,
                modal: true,
                buttons: {
                    "Create Like Code" : addLcCallback,
                    "Cancel" : function() {
                        lc_dialog.dialog("close");
                    }
                },
                close: function() {
                    $('#addLikeCodeDialog :input').each(function(){
                        $(this).val('');
                    });
                    $('#addLikeAreaAlert').html('');
                }
            });

            $('#addLikeCodeDialog :input').keyup(function (e) {
                if (e.which == 13) {
                    addLcCallback();
                }
            });

            $('#lcAddButton').click(function(e){
                e.preventDefault();
                lc_dialog.dialog("open"); 
            });

            function addLcCallback()
            {
                var data = $('#addLikeCodeDialog :input').serialize();
                $.ajax({
                    url: '<?php echo $FANNIE_URL; ?>item/modules/LikeCodeModule.php',
                    data: data,
                    dataType: 'json',
                    error: function() {
                        $('#addLikeAreaAlert').html('Communication error');
                    },
                    success: function(resp) {
                        if (resp.error) {
                            $('#addLikeAreaAlert').html(resp.error);
                        } else {
                            var newOpt = $('<option></option>');
                            newOpt.val(resp.likeCode);
                            newOpt.html(resp.likeCode + ' ' + resp.likeCodeDesc);
                            $('#LikeCodeFieldSet select').append(newOpt);
                            $('#LikeCodeFieldSet select').val(resp.likeCode);
                            lc_dialog.dialog("close");
                        }
                    }
                });
            }
        }
        <?php

        return ob_get_clean();
    }

    private function HistoryLink($lc){
        global $FANNIE_URL;
        if ($lc == -1) return '';
        $ret = '<a href="'.$FANNIE_URL.'reports/RecentSales/?likecode='.$lc.'" 
                title="Likecode Sales History" class="iframe fancyboxLink">';
        $ret .= 'Likecode Sales History</a>';
        return $ret;
    }

    private function LikeCodeItems($lc, $upc='nomatch'){
        if ($lc == -1) return '';
        $ret = "<b>Like Code Linked Items</b><div id=lctable>";
        $ret .= "<table border=0 bgcolor=\"#FFFFCC\">";
        $dbc = $this->db();
        $p = $dbc->prepare_statement("SELECT p.upc,p.description FROM
            products AS p INNER JOIN upcLike AS u ON
            p.upc=u.upc WHERE u.likeCode=?
            ORDER BY p.upc");
        $res = $dbc->exec_statement($p,array($lc));
        while($row = $dbc->fetch_row($res)){
            $tag = ($upc == $row['upc']) ? 'th' : 'td';
            $ret .= sprintf("<tr><%s><a href=itemMaint.php?upc=%s>%s</a></%s>
                    <%s>%s</%s></tr>",
                    $tag, $row['upc'],$row['upc'], $tag,
                    $tag, $row[1], $tag);
        }
        $ret .= "</table>";
        $ret .= '</div>';
        return $ret;
    }

    function AjaxCallback()
    {
        global $FANNIE_OP_DB;
        $lc = FormLib::get_form_value('lc',-1);
        $newLC = FormLib::get('newLC', false);
        $json = array();

        /** create new like code **/
        if ($newLC !== false) {
            $newName = FormLib::get('lcName');
            $json['error'] = '';
            if ($newName == '') {
                $json['error'] .= '<li>Name is required</li>';
            }
            if ($newLC == '') {
                $json['error'] .= '<li>Number is required</li>';
            } elseif (!is_numeric($newLC)) {
                $json['error'] .= '<li>' . $newLC . ' is not a number</li>';
            }
            if (empty($json['error'])) {
                $dbc = FannieDB::get($FANNIE_OP_DB);
                $chkP = $dbc->prepare('
                    SELECT likeCode
                    FROM likeCodes
                    WHERE likeCode = ?
                ');
                $chkR = $dbc->execute($chkP, array($newLC));
                if ($dbc->num_rows($chkR) > 0) {
                    $json['error'] .= '<li>' . $newLC . ' is already a like code</li>';
                } else {
                    unset($json['error']);
                    $insP = $dbc->prepare('
                        INSERT INTO likeCodes (likeCode, likeCodeDesc)
                        VALUES (?, ?)
                    ');
                    $insR = $dbc->execute($insP, array($newLC, $newName));
                    $json['likeCode'] = $newLC;
                    $json['likeCodeDesc'] = $newName;
                }
            }
        /** lookup items associated w/ like code **/
        } else {
            $json = array(
            'items' => $this->LikeCodeItems($lc),
            'link' => $this->HistoryLink($lc)
            );
        }

        echo json_encode($json);
    }
}

/**
  This form does some fancy tricks via AJAX calls. This block
  ensures the AJAX functionality only runs when the script
  is accessed via the browser and not when it's included in
  another PHP script.
*/
if (basename($_SERVER['SCRIPT_NAME']) == basename(__FILE__)){
    $obj = new LikeCodeModule();
    $obj->AjaxCallback();   
}

?>
