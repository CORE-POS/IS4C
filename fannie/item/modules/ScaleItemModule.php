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

include_once(dirname(__FILE__).'/../../classlib2.0/item/ItemModule.php');
include_once(dirname(__FILE__).'/../../classlib2.0/lib/FormLib.php');

class ScaleItemModule extends ItemModule {

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $upc = BarcodeLib::padUPC($upc);

        $dbc = $this->db();
        $p = $dbc->prepare_statement('SELECT * FROM scaleItems WHERE plu=?');
        $r = $dbc->exec_statement($p,array($upc));
        $scale = array('itemdesc'=>'','weight'=>0,'bycount'=>0,'tare'=>0,
            'shelflife'=>0,'label'=>133,'graphics'=>0,'text'=>'', 'netWeight'=>0);
        $found = false;
        if ($dbc->num_rows($r) > 0) {
            $scale = $dbc->fetch_row($r);
            $found = true;
        }

        if (!$found && $display_mode == 2) {
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

        $ret = '<fieldset id="ScaleItemFieldset">';
        $ret .=  "<legend onclick=\"\$('#ScaleFieldsetContent').toggle();\">
                <a href=\"\" onclick=\"return false;\">Scale</a>
                </legend>";
        $css = ($expand_mode == 1) ? '' : 'display:none;';
        $ret .= '<div id="ScaleFieldsetContent" style="' . $css . '">';
        
        $p = $dbc->prepare_statement('SELECT description FROM products WHERE upc=?');
        $r = $dbc->exec_statement($p,array($upc));
        $reg_description = '';
        if ($dbc->num_rows($r) > 0) {
            $w = $dbc->fetch_row($r);
            $reg_description = $w['description'];
        }

        $ret .= sprintf('<input type="hidden" name="s_plu" value="%s" />',$upc);
        $ret .= "<table style=\"background:#ffffcc;\" cellpadding=5 cellspacing=0 border=1>";
        $ret .= sprintf("<tr><th colspan=2>Longer description</th><td colspan=5><input size=35 
                type=text name=s_longdesc maxlength=100 value=\"%s\" /></td></tr>",
                ($reg_description == $scale['itemdesc'] ? '': $scale['itemdesc']));

        $ret .= "<tr><th>Weight</th><th>By Count</th><th>Tare</th><th>Shelf Life</th>";
        $ret .= "<th>Net Wt (oz)</th><th>Label</th><th>Safehandling</th></tr>";         

        $ret .= '<tr><td>';
        if ($scale['weight']==0){
            $ret .= "<input type=radio name=s_type value=\"Random Weight\" checked /> Random<br />";
            $ret .= "<input type=radio name=s_type value=\"Fixed Weight\" /> Fixed<br />";
        }
        else {
            $ret .= "<input type=radio name=s_type value=\"Random Weight\" /> Random<br />";
            $ret .= "<input type=radio name=s_type value=\"Fixed Weight\" checked /> Fixed<br />";
        }
        $ret .= '</td>';

        $ret .= sprintf("<td align=center><input type=checkbox value=1 name=s_bycount %s /></td>",
                ($scale['bycount']==1?'checked':''));

        $ret .= sprintf("<td align=center><input type=text size=5 name=s_tare value=\"%s\" /></td>",
                $scale['tare']);

        $ret .= sprintf("<td align=center><input type=text size=5 name=s_shelflife value=\"%s\" /></td>",
                $scale['shelflife']);

        $ret .= sprintf("<td align=center><input type=text size=5 name=s_netwt value=\"%s\" /></td>",
                $scale['netWeight']);

        $ret .= "<td><select name=s_label size=2>";
        if ($scale['label']==133 || $scale['label']==63){
            $ret .= "<option value=horizontal selected>Horizontal</option>";
            $ret .= "<option value=vertical>Vertical</option>";
        }
        else {
            $ret .= "<option value=horizontal>Horizontal</option>";
            $ret .= "<option value=vertical selected>Vertical</option>";
        }
        $ret .= '</select></td>';

        $ret .= sprintf("<td align=center><input type=checkbox value=1 name=s_graphics %s /></td>",
                ($scale['graphics']==1?'checked':''));
        $ret .= '</tr>';    

        $ret .= "<tr><td colspan=7>";
        $ret .= '<div style="float: left;">';
        $ret .= "<b>Expanded text:<br /><textarea name=s_text rows=4 cols=45>";
        $ret .= $scale['text'];
        $ret .= "</textarea>";
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
        $ret .= '<div style="float: left;">';
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

            $ret .= sprintf('<input type="checkbox" name="scaleID[]" id="scaleID%d" value=%d %s />
                            <label for="scaleID%d">%s</label><br />',
                            $scale->serviceScaleID(), $scale->serviceScaleID(), ($checked ? 'checked' : ''),
                            $scale->serviceScaleID(), $scale->description());
        }
        $ret .= '</div>';
        $ret .= '<div style="clear:left;"></div>';
        $ret .= "</td></tr>";

        $ret .= '</table></div></fieldset>';
        return $ret;
    }

    function SaveFormData($upc)
    {
        /* check if data was submitted */
        if (FormLib::get('s_plu') === '') return False;

        $desc = FormLib::get('description','');
        $longdesc = FormLib::get('s_longdesc','');
        if ($longdesc !== '') $desc = $longdesc;
        $price = FormLib::get('price',0);
        $tare = FormLib::get('s_tare',0);
        $shelf = FormLib::get('s_shelflife',0);
        $bycount = FormLib::get('s_bycount',0);
        $graphics = FormLib::get('s_graphics',0);
        $type = FormLib::get('s_type','Random Weight');
        $weight = ($type == 'Random Weight') ? 0 : 1;
        $text = FormLib::get('s_text','');
        $label = FormLib::get('s_label','horizontal');
        $netWeight = FormLib::get('s_netwt', 0);

        if ($label == "horizontal" && $type == "Random Weight")
            $label = 133;
        elseif ($label == "horizontal" && $type == "Fixed Weight")
            $label = 63;
        elseif ($label == "vertical" && $type == "Random Weight")
            $label = 103;
        elseif ($label == "vertical" && $type == "Fixed Weight")
            $label = 23;

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
            if($p->load()) {
                $p->Scale(0);
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
        $scaleItem->save();

        // extract scale PLU
        preg_match("/002(\d\d\d\d)0/",$upc,$matches);
        $s_plu = $matches[1];

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

        HobartDgwLib::writeItemsToScales($item_info, $scales);
    }
}

?>
