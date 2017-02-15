<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

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

if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__).'/../../classlib2.0/FannieAPI.php');
}

class LikeCodeModule extends \COREPOS\Fannie\API\item\ItemModule 
{

    private function getLikeCode($dbc, $upc)
    {
        $prep = $dbc->prepare('SELECT likeCode FROM upcLike WHERE upc=?');
        $res = $dbc->execute($prep,array($upc));
        $myLC = -1;     
        if ($dbc->numRows($res) > 0) {
            $row = $dbc->fetchRow($res);
            $myLC = $row['likeCode'];
        }

        return $myLC;
    }

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $dbc = $this->db();
        $myLC = $this->getLikeCode($dbc, $upc);
        $ret = '<div id="LikeCodeFieldSet" class="panel panel-default">';
        $ret .=  "<div class=\"panel-heading\">
                <a href=\"\" onclick=\"\$('#LikeCodeFieldsetContent').toggle();return false;\">
                Likecode
                </a></div>";
        $style = '';
        if ($expand_mode == 1) {
            $style = '';
        } else if ($expand_mode == 2 && $myLC != -1) {
            $style = '';
        } else {
            $style = ' collapse';
        }
        $ret .= '<div id="LikeCodeFieldsetContent" class="panel-body' . $style . '">';

        $ret .= "<div class=\"form-group form-inline\">
                <b>Like code</b> <button type=\"button\" id=\"lcAddButton\"
                class=\"btn btn-default\">+</button> ";
        $ret .= "<select name=likeCode id=\"likeCodeSelect\" 
                onchange=\"updateLcModList(this.value);\" class=\"chosenSelect form-control\">";
        $ret .= "<option value=-1>(none)</option>";
    
        $lcm = new LikeCodesModel($dbc);
        $ret .= $lcm->toOptions($myLC);
        $ret .= "</select>";
        $ret .= " <label><input type=checkbox name=LikeCodeNoUpdate value='noupdate'>Check to not update like code items</label>";
        $ret .= ' <span id="LikeCodeHistoryLink">' . $this->HistoryLink($myLC) . '</span>';
        $ret .= '</div>';

        $ret .= '<div id="LikeCodeItemList">';
        $ret .= $this->LikeCodeItems($myLC, $upc);
        $ret .= '</div>';

        $ret .= '<div id="addLikeCodeDialog" title="Add Like Code" class="collapse">';
        $ret .= '<fieldset>';
        $ret .= '<label for="newLikeID">LC #</label>';
        $ret .= '<input type="text" name="newLC" id="newLikeID" class="form-control" />';
        $ret .= '<label for="newLikeName">LC Name</label>';
        $ret .= '<input type="text" name="lcName" id="newLikeName" class="form-control" />';
        $ret .= '</fieldset>';
        $ret .= '</div>';

        $ret .= '</div>';
        $ret .= '</div>';

        return $ret;
    }

    public function SaveFormData($upc)
    {
        try {
            $likecode = $this->form->likeCode;
        } catch (Exception $ex) {
            return false;
        }
        $dbc = $this->db();

        $delP = $dbc->prepare('DELETE FROM upcLike WHERE upc=?'); 
        $delR = $dbc->execute($delP,array($upc));
        if ($likecode == -1){
            return ($delR === False) ? False : True;
        }

        $insP = 'INSERT INTO upcLike (upc,likeCode) VALUES (?,?)';
        $insR = $dbc->execute($insP,array($upc,$likecode));
        
        if (FormLib::get('LikeCodeNoUpdate') == 'noupdate'){
            return ($insR === False) ? False : True;
        }

        /* get values for current item */
        $valuesP = $dbc->prepare('SELECT normal_price,pricemethod,groupprice,quantity,
            department,scale,tax,foodstamp,discount,qttyEnforced,local,wicable
            FROM products WHERE upc=?');
        $values = $dbc->getRow($valuesP,array($upc));  
        if ($values === false) {
            return false;
        }

        $stores = new StoresModel($dbc);
        $stores = array_map(
            function($obj){ return $obj->storeID(); },
            array_filter($stores->find(), function($obj){ return $obj->hasOwnItems(); }));

        $model = new ProductsModel($dbc);
        $model->upc($upc);
        $model->mixmatchcode($likecode+500);
        $this->saveModelToStores($model, $stores);

        /* apply current values to other other items
           in the like code */
        $upcP = $dbc->prepare('SELECT upc FROM upcLike WHERE likeCode=? AND upc<>?');
        $upcR = $dbc->execute($upcP,array($likecode,$upc));
        $upcs = array();
        while ($upcW = $dbc->fetchRow($upcR)) {
            $this->updateItem($dbc, $upcW['upc'], $likecode, $values);
            $upcs[] = $upcW['upc'];
        }
        if (count($upcs) <= 10) {
            COREPOS\Fannie\API\data\ItemSync::sync($upcs);
        }

        return true;
    }

    private $upP = null;
    private function updateStatement($dbc)
    {
        if ($this->upP === null) {
            $this->upP = $dbc->prepare('
                UPDATE products
                SET normal_price=?,
                    pricemethod=?,
                    groupprice=?,
                    quantity=?,
                    department=?,
                    scale=?,
                    tax=?,
                    foodstamp=?,
                    discount=?,
                    qttyEnforced=?,
                    local=?,
                    wicable=?,
                    mixmatchcode=?
                WHERE upc=?');
        }
        return $this->upP;
    }

    private function updateItem($dbc, $upc, $likecode, $values)
    {
        $upP = $this->updateStatement($dbc);
        $args = array(
            $values['normal_price'],
            $values['pricemethod'],
            $values['groupprice'],
            $values['quantity'],
            $values['department'],
            $values['scale'],
            $values['tax'],
            $values['foodstamp'],
            $values['discount'],
            $values['qttyEnforced'],
            $values['local'],
            $values['wicable'],
            $likecode+500,
            $upc
        );
        return $dbc->execute($upP, $args);
    }

    private function saveModelToStores($model, $stores)
    {
        $isHQ = FannieConfig::config('STORE_MODE') == 'HQ' ? true : false;
        if ($isHQ) {
            foreach ($stores as $store_id) {
                $model->store_id($store_id);
                $model->save();
            }
        } else {
            $model->save();
        }
    }

    public function getFormJavascript($upc)
    {
        $FANNIE_URL = FannieConfig::config('URL');
        ob_start();
        ?>
        function updateLcModList(val){
            if (val == -1) {
                $('#LikeCodeItemList').hide();
                $('#LikeCodeHistoryLink').hide();
                return true;
            }
            $.ajax({
                url: '<?php echo $FANNIE_URL; ?>item/modules/LikeCodeModule.php',
                data: 'lc='+val,
                dataType: 'json',
                cache: false
            }).done(function(data){
                if (data.items){
                    $('#LikeCodeItemList').html(data.items);
                }
                if (data.link){
                    $('#LikeCodeHistoryLink').html(data.link);
                    $('#LikeCodeHistoryLink a.fancyboxLink').fancybox();
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
                    dataType: 'json'
                }).fail(function() {
                    $('#addLikeAreaAlert').html('Communication error');
                }).done(function(resp) {
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
                });
            }
        }
        <?php

        return ob_get_clean();
    }

    private function HistoryLink($likecode)
    {
        $FANNIE_URL = FannieConfig::config('URL');
        if ($likecode == -1) return '';
        $ret = '<a href="'.$FANNIE_URL.'reports/RecentSales/?likecode='.$likecode.'" 
                title="Likecode Sales History" class="iframe fancyboxLink">';
        $ret .= 'Likecode Sales History</a>';

        return $ret;
    }

    private function LikeCodeItems($likecode, $upc='nomatch')
    {
        if ($likecode == -1) return '';
        $ret = "<b>Like Code Linked Items</b><div id=lctable>";
        $ret .= "<table class=\"alert alert-warning table\">";
        $dbc = $this->db();
        $p = $dbc->prepare("
            SELECT p.upc,
                p.description 
            FROM products AS p 
                INNER JOIN upcLike AS u ON p.upc=u.upc 
            WHERE u.likeCode=?
            ORDER BY p.upc");
        $res = $dbc->execute($p,array($likecode));
        $prev = false;
        while($row = $dbc->fetch_row($res)){
            if ($prev === $row['upc']) {
                continue;
            }
            $tag = ($upc == $row['upc']) ? 'th' : 'td';
            $ret .= sprintf("<tr><%s><a href=itemMaint.php?upc=%s>%s</a></%s>
                    <%s>%s</%s></tr>",
                    $tag, $row['upc'],$row['upc'], $tag,
                    $tag, $row[1], $tag);
            $prev = $row['upc'];
        }
        $ret .= "</table>";
        $ret .= '</div>';
        return $ret;
    }

    function AjaxCallback()
    {
        $likecode = FormLib::get('lc',-1);
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
                $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
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
            'items' => $this->LikeCodeItems($likecode),
            'link' => $this->HistoryLink($likecode)
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

