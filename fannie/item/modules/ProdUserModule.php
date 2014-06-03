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
        $ret .= '</tr>';
        $ret .= '<tr>';
        $ret .= '<th>Desc.</th>';
        $ret .= '<td><input type="text" size="45" name="lf_desc" value="' . $model->description() . '" /></td>';
        $ret .= '</tr>';
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
        }
        $ret .= '</select></td></tr>';
        $ret .= '<tr><th colspan="2">Ad Text</th></tr>';
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
        }
        
        return $model->save();
	}
}

