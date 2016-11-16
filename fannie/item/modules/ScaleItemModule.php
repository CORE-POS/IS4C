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

class ScaleItemModule extends \COREPOS\Fannie\API\item\ItemModule 
{

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $upc = BarcodeLib::padUPC($upc);

        $dbc = $this->db();
        $p = $dbc->prepare('SELECT * FROM scaleItems WHERE plu=?');
        $r = $dbc->execute($p,array($upc));
        $scale = array('itemdesc'=>'','weight'=>0,'bycount'=>0,'tare'=>0,
            'shelflife'=>0,'label'=>133,'graphics'=>0,'text'=>'', 'netWeight'=>0,
            'mosaStatement'=>0, 'originText'=>'');
        $found = false;
        if ($dbc->num_rows($r) > 0) {
            $scale = $dbc->fetch_row($r);
            $found = true;
        }

        if (!$found && $display_mode == 2 && substr($upc, 0, 3) != '002') {
            return '';
        }
        $css = '';
        if ($expand_mode == 1) {
            $css = '';
        } else if ($found && $expand_mode == 2) {
            $css = '';
        } else {
            $css = 'display:none;';
        }

        $ret = '<div id="ScaleItemFieldset" class="panel panel-default">';
        $ret .=  "<div class=\"panel-heading\">
                <a href=\"\" onclick=\"\$('#ScaleFieldsetContent').toggle();return false;\">
                Scale</a>
                </div>";
        $ret .= '<div id="ScaleFieldsetContent" class="panel-body" style="' . $css . '">';
        
        $p = $dbc->prepare('SELECT description FROM products WHERE upc=?');
        $r = $dbc->execute($p,array($upc));
        $reg_description = '';
        if ($dbc->num_rows($r) > 0) {
            $w = $dbc->fetch_row($r);
            $reg_description = $w['description'];
        }

        $ret .= sprintf('<input type="hidden" name="s_plu" value="%s" />',$upc);
        $ret .= "<table style=\"background:#ffffcc;\" class=\"table\">";
        $ret .= sprintf("<tr><th colspan=2>Longer description</th><td colspan=5><input size=35 
                type=text name=s_longdesc maxlength=100 value=\"%s\" 
                class=\"form-control\" /></td></tr>",
                ($reg_description == $scale['itemdesc'] ? '': $scale['itemdesc']));

        $ret .= "<tr><th>Weight</th><th>By Count</th><th>Tare</th><th>Shelf Life</th>";
        $ret .= "<th>Net Wt (oz)</th><th>Label</th><th>Safehandling</th></tr>";         

        $ret .= '<tr><td><select name="s_type" class="form-control" size="2">';
        if ($scale['weight']==0){
            $ret .= "<option value=\"Random Weight\" selected /> Random</option>";
            $ret .= "<option value=\"Fixed Weight\" /> Fixed</option>";
        } else {
            $ret .= "<option value=\"Random Weight\" /> Random</option>";
            $ret .= "<option value=\"Fixed Weight\" selected /> Fixed</option>";
        }
        $ret .= '</select></td>';

        $ret .= sprintf("<td align=center><input type=checkbox value=1 name=s_bycount %s /></td>",
                ($scale['bycount']==1?'checked':''));

        $ret .= sprintf("<td align=center><input type=text class=\"form-control\" name=s_tare value=\"%s\" /></td>",
                $scale['tare']);

        $ret .= sprintf("<td align=center><input type=text class=\"form-control\" name=s_shelflife value=\"%s\" /></td>",
                $scale['shelflife']);

        $ret .= sprintf("<td align=center><input type=text class=\"form-control\" name=s_netwt value=\"%s\" /></td>",
                $scale['netWeight']);

        $ret .= "<td><select name=s_label size=2 class=\"form-control\">";
        $label_attr = \COREPOS\Fannie\API\item\ServiceScaleLib::labelToAttributes($scale['label']);
        if ($label_attr['align'] == 'horizontal') {
            $ret .= "<option value=horizontal selected>Horizontal</option>";
            $ret .= "<option value=vertical>Vertical</option>";
        } else {
            $ret .= "<option value=horizontal>Horizontal</option>";
            $ret .= "<option value=vertical selected>Vertical</option>";
        }
        $ret .= '</select></td>';

        $ret .= sprintf("<td align=center><input type=checkbox value=1 name=s_graphics %s /></td>",
                ($scale['graphics']>0?'checked':''));
        $ret .= '</tr>';    

        $ret .= "<tr><td colspan=7>";
        $ret .= '<div class="col-sm-6">';
        $ret .= "<b>Expanded text:<br />
            <textarea name=s_text id=s_text rows=4 cols=45 class=\"form-control\">";
        $ret .= $scale['text'];
        $ret .= "</textarea>";
        $ret .= '<br /><b>Linked PLU</b><br />';
        $linkedPLU = isset($scale['linkedPLU']) ? $scale['linkedPLU'] : '';
        $ret .= '<input type="text" class="form-control" name="s_linkedPLU" value="' . $linkedPLU . '" />';
        $ret .= '</div>';
        $ret .= '<div class="col-sm-4">';
        $ret .= '<div class="form-group">
            <button type="button" class="btn btn-default btn-sm" onclick="appendScaleTag(\'mosa\'); return false;">MOSA</button>
            <label>
                <input type="checkbox" name="scale_mosa" ' . ($scale['mosaStatement'] ? 'checked' : '') . ' />
                Include MOSA statement
            </label>
            </div>';
        $ret .= '<div class="form-group">
            <button type="button" class="btn btn-default btn-sm" onclick="appendScaleTag(\'cool\'); return false;">COOL</button>
            <input type="text" class="form-control" name="scale_origin" value="' . $scale['originText'] . '" 
                placeholder="Country of origin text" />
            </div>';
        $ret .= '</div>';
        $scales = new ServiceScalesModel($dbc);
        $mapP = $dbc->prepare('SELECT upc
                               FROM ServiceScaleItemMap
                               WHERE serviceScaleID=?
                                AND upc=?');
        $deptP = $dbc->prepare('SELECT p.upc
                                FROM products AS p
                                    INNER JOIN superdepts AS s ON p.department=s.dept_ID
                                WHERE p.upc=?
                                    AND s.superID=?');
        $ret .= '<div class="col-sm-2">';
        foreach ($scales->find('description') as $scale) {
            $checked = false;
            $mapR = $dbc->execute($mapP, array($scale->serviceScaleID(), $upc));
            if ($dbc->num_rows($mapR) > 0) {
                // marked in map table
                $checked = true;
            } else {
                $deptR = $dbc->execute($deptP, array($upc, $scale->superID()));
                if ($dbc->num_rows($deptR) > 0) {
                    // in a POS department corresponding 
                    // to this scale
                    $checked = true;
                }
            }

            $css_class = '';
            $title = '';
            $ret .= sprintf('<div class="form-group %s" title="%s"><div class="checkbox">
                            <label class="control-label">
                            <input type="checkbox" name="scaleID[]" id="scaleID%d" value=%d %s />
                            %s</label>
                            </div></div>',
                            $css_class, $title,
                            $scale->serviceScaleID(), $scale->serviceScaleID(), ($checked ? 'checked' : ''),
                            $scale->description()
            );
        }
        $ret .= '</div>';
        $ret .= "</td></tr>";

        $ret .= '</table></div></div>';
        return $ret;
    }

    function getFormJavascript($upc)
    {
        return <<<STR
function appendScaleTag(tag) {
    var current = $('#s_text').val();
    current += "{" + tag + "}";
    $('#s_text').val(current);
    console.log(current);
}
STR;
    }

    function SaveFormData($upc)
    {
        /* check if data was submitted */
        if (FormLib::get('s_plu') === '') return False;

        $desc = FormLib::get('descript','');
        if (is_array($desc)) {
            $desc = array_pop($desc);
        }
        $longdesc = FormLib::get('s_longdesc','');
        if (trim($longdesc) !== '') $desc = $longdesc;
        $price = FormLib::get('price',0);
        if (is_array($price)) {
            $price = array_pop($price);
        }
        $tare = FormLib::get('s_tare',0);
        $shelf = FormLib::get('s_shelflife',0);
        $bycount = FormLib::get('s_bycount',0);
        $graphics = FormLib::get('s_graphics',0);
        $type = FormLib::get('s_type','Random Weight');
        $weight = ($type == 'Random Weight') ? 0 : 1;
        $text = FormLib::get('s_text','');
        $align = FormLib::get('s_label','horizontal');
        $netWeight = FormLib::get('s_netwt', 0);
        $linkedPLU = FormLib::get('s_linkedPLU', null);

        $label = \COREPOS\Fannie\API\item\ServiceScaleLib::attributesToLabel(
            $align,
            ($type == 'Fixed Weight') ? true : false,
            ($graphics != 0) ? true : false
        );

        $dbc = $this->db();

        // apostrophes might make a mess
        // double quotes definitely will
        // DGW quotes text fields w/o any escaping
        $desc = str_replace("'","",$desc);
        $text = str_replace("'","",$text);
        $desc = str_replace("\"","",$desc);
        $text = str_replace("\"","",$text);
        
        /**
          Safety check:
          A fixed-weight item sticked by the each flagged
          as scalable will interact with the register's
          quantity * upc functionality incorrectly
        */
        if ($weight == 1 && $bycount == 1) {
            $p = new ProductsModel($dbc);
            $p->upc($upc);
            $stores = FormLib::get('store_id');
            foreach ($stores as $s) {
                $p->store_id($s);
                $p->scale(0);
                $p->enableLogging(false);
                $p->save();
            }
        }

        $scaleItem = new ScaleItemsModel($dbc);
        $scaleItem->plu($upc);
        $action = 'ChangeOneItem';
        if (!$scaleItem->load()) {
            // new record
            $action = "WriteOneItem";
        }
        $scaleItem->price($price);
        $scaleItem->itemdesc($desc);
        $scaleItem->weight( ($type == 'Fixed Weight') ? 1 : 0 );
        $scaleItem->bycount($bycount);
        $scaleItem->tare($tare);
        $scaleItem->shelflife($shelf);
        $scaleItem->text($text);
        $scaleItem->label($label);
        $scaleItem->graphics( ($graphics) ? 121 : 0 );
        $scaleItem->netWeight($netWeight);
        $scaleItem->linkedPLU(BarcodeLib::padUPC($linkedPLU));
        $scaleItem->mosaStatement(FormLib::get('scale_mosa',false) ? 1 : 0);
        $scaleItem->originText(FormLib::get('scale_origin'));
        $scaleItem->save();

        // extract scale PLU
        preg_match("/^002(\d\d\d\d)0/",$upc,$matches);
        $s_plu = $matches[1];
        if ($s_plu == '0000') {
            preg_match("/^0020(\d\d\d\d)/",$upc,$matches);
            $s_plu = $matches[1];
        }

        $item_info = array(
            'RecordType' => $action,
            'PLU' => $s_plu,
            'Description' => $desc,
            'Tare' => $tare,
            'ShelfLife' => $shelf,
            'Price' => $price,
            'Label' => $label,
            'ExpandedText' => $text,
            'ByCount' => $bycount,
            'OriginText' => $scaleItem->originText(),
            'MOSA' => $scaleItem->mosaStatement(),
        );
        if ($netWeight != 0) {
            $item_info['NetWeight'] = $netWeight;
        }
        if ($graphics) {
            $item_info['Graphics'] = 121;
        }
        // normalize type + bycount; they need to match
        if ($item_info['ByCount'] && $type == 'Random Weight') {
            $item_info['Type'] = 'By Count';
        } else if ($type == 'Fixed Weight') {
            $item_info['Type'] = 'Fixed Weight';
            $item_info['ByCount'] = 1;
        } else {
            $item_info['Type'] = 'Random Weight';
            $item_info['ByCount'] = 0;
        }

        $scales = array();
        $scaleIDs = FormLib::get('scaleID', array());
        $model = new ServiceScalesModel($dbc);
        /**
          Send item to requested scales
        */
        if (count($scaleIDs) > 0) {
            $chkMap = $dbc->prepare('SELECT upc
                                     FROM ServiceScaleItemMap
                                     WHERE serviceScaleID=?
                                        AND upc=?');
            $addMap = $dbc->prepare('INSERT INTO ServiceScaleItemMap
                                        (serviceScaleID, upc)
                                     VALUES
                                        (?, ?)');
            foreach ($scaleIDs as $scaleID) {
                $model->reset();
                $model->serviceScaleID($scaleID);
                if (!$model->load()) {
                    // scale doesn't exist
                    continue;
                }
                $repr = array(
                    'host' => $model->host(),
                    'dept' => $model->scaleDeptName(),
                    'type' => $model->scaleType(),  
                    'new' => false,
                );
                $exists = $dbc->execute($chkMap, array($scaleID, $upc));
                if ($dbc->num_rows($exists) == 0) {
                    $repr['new'] = true;
                    $dbc->execute($addMap, array($scaleID, $upc));
                }

                $scales[] = $repr;
            }

            \COREPOS\Fannie\API\item\HobartDgwLib::writeItemsToScales($item_info, $scales);
            \COREPOS\Fannie\API\item\EpScaleLib::writeItemsToScales($item_info, $scales);
        }

        /**
          Delete item from scales if that
          option was unchecked
        */
        $mapP = $dbc->prepare('
            SELECT serviceScaleID
            FROM ServiceScaleItemMap
            WHERE upc=?');
        $mapR = $dbc->execute($mapP, array($upc));
        $delP = $dbc->prepare('
            DELETE
            FROM ServiceScaleItemMap
            WHERE serviceScaleID=?
                AND upc=?');
        if ($mapR && $dbc->numRows($mapR)) {
            $scales = array();
            while ($mapW = $dbc->fetchRow($mapR)) {
                if (in_array($mapW['serviceScaleID'], $scaleIDs)) {
                    // item sent to that scale
                    continue;
                }
                $model->reset();
                $model->serviceScaleID($mapW['serviceScaleID']);
                if (!$model->load()) {
                    // scale doesn't exist
                    continue;
                }
                $repr = array(
                    'host' => $model->host(),
                    'dept' => $model->scaleDeptName(),
                    'type' => $model->scaleType(),  
                    'new' => false,
                );
                $scales[] = $repr;

                $dbc->execute($delP, array($mapW['serviceScaleID'], $upc));
            }
            if (count($scales) > 0) {
                \COREPOS\Fannie\API\item\HobartDgwLib::deleteItemsFromScales($item_info['PLU'], $scales); 
            }
        }
    }
}

