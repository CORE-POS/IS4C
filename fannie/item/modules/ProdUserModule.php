<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op, Duluth, MN

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

if (!class_exists('FannieAPI'))
    include_once(dirname(__FILE__).'/../../classlib2.0/FannieAPI.php');

class ProdUserModule extends ItemModule 
{

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        global $FANNIE_URL;
        $upc = BarcodeLib::padUPC($upc);

        $ret = '<fieldset id="ProdUserFieldset">';
        $ret .=  "<legend onclick=\"\$('#ProdUserFieldsetContent').toggle();\">
                <a href=\"\" onclick=\"return false;\">Longform Info</a>
                </legend>";
        $css = ($expand_mode == 1) ? '' : 'display:none;';
        $ret .= '<div id="ProdUserFieldsetContent" style="' . $css . '">';

        $dbc = $this->db();
        $model = new ProductUserModel($dbc);
        $model->upc($upc);
        $model->load();

        $prod = new ProductsModel($dbc);
        $prod->upc($upc);
        $prod->load();

        $ret .= '<div style="float:left;">';
        $ret .= '<table>';
        $ret .= '<tr>';
        $ret .= '<th>Brand</th>';
        $ret .= '<td><input type="text" size="45" id="lf_brand" name="lf_brand" value="' . $model->brand() . '" /></td>';
        $ret .= '<td><a href="" onclick="createSign(); return false;">Make Sign</a></td>';
        $ret .= '</tr>';
        $ret .= '<tr>';
        $ret .= '<th>Desc.</th>';
        $ret .= '<td><input type="text" size="45" name="lf_desc" value="' . $model->description() . '" /></td>';
        $ret .= '</tr>';

        $otherOriginBlock = '<tr><td>&nbsp;</td><td><select name=otherOrigin[]><option value=0>n/a</option>';

        $ret .= '<tr>';
        $ret .= '<th><a href="' . $FANNIE_URL . 'item/origins/OriginEditor.php">Origin</a></th>';
        $ret .= '<td><select name="origin">';
        $ret .= '<option value="0">n/a</option>';
        $origins = new OriginsModel($dbc);
        $origins->local(0);
        foreach ($origins->find('name') as $o) {
            $ret .= sprintf('<option %s value="%d">%s</option>',
                        $prod->current_origin_id() == $o->originID() ? 'selected' : '',
                        $o->originID(), $o->name());
            $otherOriginBlock .= sprintf('<option value=%d>%s</option>',
                                            $o->originID(), $o->name());
        }
        $ret .= '</select>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;<a href="" 
                onclick="$(\'#originsBeforeMe\').before(\'' . $otherOriginBlock . '\'); return false;">Add more</a>';
        $ret .= '</td></tr>';

        $mapP = 'SELECT originID FROM ProductOriginsMap WHERE upc=? AND originID <> ?';
        $mapR = $dbc->execute($mapP, array($upc, $prod->current_origin_id()));
        while ($mapW = $dbc->fetch_row($mapR)) {
            $ret .= '<tr><td>&nbsp;</td><td><select name="otherOrigin[]"><option value="0">n/a</option>';
            foreach ($origins->find('name') as $o) {
                $ret .= sprintf('<option %s value="%d">%s</option>',
                            $mapW['originID'] == $o->originID() ? 'selected' : '',
                            $o->originID(), $o->name());
            }
            $ret .= '</select></td></tr>';
        }

        $ret .= '<tr id="originsBeforeMe"><th colspan="2">Ad Text</th></tr>';
        $ret .= '<tr><td colspan="3"><textarea name="lf_text"
                    rows="8" cols="45">' 
                    . str_replace('<br />', "\n", $model->long_text()) 
                    . '</textarea></td></tr>';
        $ret .= '</table>';
        $ret .= '</div>';
        if (is_file(dirname(__FILE__) . '/../images/done/' . $model->photo())) {
            $ret .= '<div style="float:left;">';
            $ret .= '<img width="150px" src="' . $FANNIE_URL . 'item/images/done/' . $model->photo() . '" />';
            $ret .= '</div>';
        }

        $ret .= '</div>';
        $ret .= '</fieldset>';

        return $ret;
    }

    function SaveFormData($upc)
    {
        $upc = BarcodeLib::padUPC($upc);
        $brand = FormLib::get('lf_brand');
        $desc = FormLib::get('lf_desc');
        $origin = FormLib::get('origin', 0);
        $text = FormLib::get('lf_text');
        $text = str_replace("\r", '', $text);
        $text = str_replace("\n", '<br />', $text);
        // strip non-ASCII (word copy/paste artifacts)
        $text = preg_replace("/[^\x01-\x7F]/","", $text); 

        $dbc = $this->db();

        $model = new ProductUserModel($dbc);
        $model->upc($upc);
        $model->brand($brand);
        $model->description($desc);
        $model->long_text($text);

        $multiOrigin = FormLib::get('otherOrigin', array());
        $originMap = array();
        if ($origin != 0) {
            $originMap[] = $origin;
        }
        foreach ($multiOrigin as $originID) {
            if ($originID != 0) {
                $originMap[] = $originID;
            }
        }
        
        $mapP = $dbc->prepare('DELETE FROM ProductOriginsMap WHERE upc=?');
        $addP = $dbc->prepare('INSERT INTO ProductOriginsMap
                                (originID, upc, active)
                                VALUES (?, ?, 1)');

        $lcP = $dbc->prepare('SELECT u.upc
                            FROM upcLike AS u
                                INNER JOIN products AS p ON u.upc=p.upc
                            WHERE u.likeCode IN (
                                SELECT l.likeCode
                                FROM upcLike AS l
                                WHERE l.upc = ?
                            )');
        $lcR = $dbc->execute($lcP, array($upc));
        $items = array($upc);
        while ($w = $dbc->fetch_row($lcR)) {
            if ($w['upc'] == $upc) {
                continue;
            }
            $items[] = $w['upc'];
        }

        $prod = new ProductsModel($dbc);
        foreach ($items as $item) {
            $prod->upc($item);
            $prod->current_origin_id($origin);
            $prod->save();

            $dbc->execute($mapP, array($item));
            foreach ($originMap as $originID) {
                $dbc->execute($addP, array($originID, $item));
            }
        }
        
        return $model->save();
    }

    public function getFormJavascript($upc)
    {
        global $FANNIE_URL;
        ob_start();
        ?>
        function createSign()
        {
           var form = $('<form />',
                            { action: '<?php echo $FANNIE_URL; ?>admin/labels/SignFromSearch.php',
                              method: 'post',
                              id: 'newSignForm' }
            );
           form.append($('<input />',
                        { type: 'hidden', name: 'u[]', value: '<?php echo $upc; ?>' }));

           $('body').append(form);
           $('#newSignForm').submit();
        }
        <?php
        return ob_get_clean();

    }

    public function summaryRows($upc)
    {
        global $FANNIE_URL;
        $form = sprintf('<form id="newSignForm" method="post" action="%sadmin/labels/SignFromSearch.php">
                        <input type="hidden" name="u[]" value="%s" />
                        </form>', $FANNIE_URL, $upc);
        $ret = '<td>' . $form . '<a href="" onclick="$(\'#newSignForm\').submit();return false;">Create Sign</a></td>';

        return array($ret);
    }
}

