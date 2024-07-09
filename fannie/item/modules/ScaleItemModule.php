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
            'mosaStatement'=>0, 'originText'=>'', 'price'=>0, 'reheat'=>0);
        $found = false;
        if ($dbc->num_rows($r) > 0) {
            $scale = $dbc->fetch_row($r);
            $found = true;
        }

        $ingP = $dbc->prepare("SELECT s.storeID, s.description, i.ingredients
            FROM Stores AS s
                LEFT JOIN ScaleIngredients AS i ON s.storeID=i.storeID AND i.upc=?
            WHERE s.hasOwnItems = 1");
        $ingR = $dbc->execute($ingP, array($upc));
        $storeIngredients = array();
        $stores = array();
        while ($ingW = $dbc->fetchRow($ingR)) {
            $storeIngredients[$ingW['storeID']] = empty($ingW['ingredients']) ? $scale['text'] : $ingW['ingredients'];
            $stores[$ingW['storeID']] = $ingW['description'];
        }
        $selfStore = COREPOS\Fannie\API\lib\Store::getIdByIp();
        if (!$selfStore) {
            $ids = array_keys($stores);
            $selfStore = $ids[0];
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
        $ret .= sprintf('<input type="hidden" name="s_price" value="%s" />',$scale['price']);
        $ret .= "<table style=\"background:#ffffcc;\" class=\"table\">";
        $ret .= sprintf("<tr><th colspan=2>Longer description</th><td colspan=5><input size=35 
                type=text name=s_longdesc maxlength=100 value=\"%s\" 
                class=\"form-control\" /></td></tr>",
                ($reg_description == $scale['itemdesc'] ? '': $scale['itemdesc']));

        $ret .= "<tr><th>Weight</th><th>By Count</th><th>Tare</th><th>Shelf Life</th>";
        $ret .= "<th>Net Wt (oz)</th><th>Label</th></tr>";         

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

        $ret .= "<td><select name=s_label size=4 class=\"form-control\">";
        $labels = array(
            103 => 'Random Wt',
            23 => 'Fixed Wt',
            53 => 'Safehandling',
            105 => 'Long',
            
        );
        $label_attr = \COREPOS\Fannie\API\item\ServiceScaleLib::labelToAttributes($scale['label']);
        foreach ($labels as $labelID => $labelName) {
            $ret .= sprintf('<option %s value="%d">%s (%d)</option>',
                ($labelID == $scale['label'] ? 'selected' : ''),
                $labelID, $labelName, $labelID);
        }
        $ret .= '</select></td>';

        $ret .= '</tr>';    

        $ret .= "<tr><td colspan=7>";
        $ret .= '<div class="col-sm-6">';
        $measure = str_replace("\r", '', $storeIngredients[$selfStore]);
        $measure = str_replace("\n", '', $measure);
        $siSynced = count(array_unique($storeIngredients)) == 1 ? 'checked' : '';
        $ret .= '<b>Ingredients (<span id="expLength">' . strlen($measure) . '</span>):
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <label><input type="checkbox" id="si_sync" ' . $siSynced . ' /> Sync</label>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="../reports/ScaleIngredientHistory/ScaleIngredientHistoryReport.php?upc=' . $upc . '">Edit History</a>
            <br />
            <ul class="nav nav-tabs" role="tablist">';
        foreach ($stores as $id => $store) {
            $ret .= '<li role="presentation" ' . ($selfStore == $id ? 'class="active"' : '') . '>
                <a href="#si-store-' . $id . '" aria-controls="home" role="tab" data-toggle="tab" class="nav-tab"
                onclick="setTimeout(() => scaleItem.countField(\'s_text\', \'expLength\'), 25);">' . $store . '</a></li>';
        }
        $ret .= '</ul>';
        $ret .= '<div class="tab-content">';
        foreach ($storeIngredients as $id => $text) {
            $ret .= '<div role="tabpanel" class="tab-pane ' . ($selfStore == $id ? 'active' : '') . '" id="si-store-' . $id . '">';
            $ret .= '<input type="hidden" name="s_text_id[]" value="' . $id . '" />';
            $ret .= '<input type="hidden" name="s_text_hash[]" value="' . md5($storeIngredients[$id]) . '" />';
            $ret .= "<textarea name=s_text[] rows=15 cols=45 class=\"form-control s_text\" 
                onkeyup=\"scaleItem.countField('s_text', 'expLength');\"
                onpaste=\"setTimeout(() => scaleItem.countField('s_text', 'expLength'), 25);\">";
            $ret .= $storeIngredients[$id];
            $ret .= "</textarea>";
            $ret .= '</div>';
        }
        $ret .= '</div>';
        $ret .= '<br /><b>Linked PLU</b><br />';
        $linkedPLU = isset($scale['linkedPLU']) ? $scale['linkedPLU'] : '';
        $ret .= '<input type="text" class="form-control" name="s_linkedPLU" value="' . $linkedPLU . '" />';
        $ret .= '</div>';
        $ret .= '<div class="col-sm-4">';
        $ret .= '<div class="form-group">
            <button type="button" class="btn btn-default btn-sm" onclick="scaleItem.appendScaleTag(\'mosa\'); return false;">MOSA</button>
            <label>
                <input type="checkbox" name="scale_mosa" ' . ($scale['mosaStatement'] ? 'checked' : '') . ' />
                Include MOSA statement
            </label>
            </div>';
        $ret .= '<div class="form-group">
            <button type="button" class="btn btn-default btn-sm" onclick="scaleItem.appendScaleTag(\'cool\'); return false;">COOL</button>
            <input type="text" class="form-control" name="scale_origin" value="' . $scale['originText'] . '" 
                placeholder="Country of origin text" />
            </div>';
        $ret .= '<div class="form-group">
            <label><input type="checkbox" name="s_reheat" value="1" ' . ($scale['reheat'] ? 'checked' : '') . ' />
            Include reheat line</label>
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
        $isMapped = $dbc->prepare("SELECT upc FROM ServiceScaleItemMap WHERE upc=?");
        $isMapped = $dbc->getValue($isMapped, array($upc));
        $ret .= '<div class="col-sm-2">';
        foreach ($scales->find('description') as $scale) {
            $checked = false;
            $mapR = $dbc->execute($mapP, array($scale->serviceScaleID(), $upc));
            if ($dbc->num_rows($mapR) > 0) {
                // marked in map table
                $checked = true;
            } elseif (!$isMapped) {
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
                            <input type="checkbox" name="scaleID[]" id="scaleID%d" value=%d class="scale-sync-checkbox" %s/>
                            <span class="label-text">%s</span></label>
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

    public function getFormJavascript($upc)
    {
        return file_get_contents(__DIR__ . '/scaleItem.js');
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
        $storePrices = array();
        if (is_array($price)) {
            $stores = FormLib::get('store_id', array());
            for ($i=0; $i<count($stores); $i++) {
                $storeID = $stores[$i];
                $storePrices[$storeID] = $price[$i];
            }
            $price = array_pop($price);
        }
        if ($price == 0) {
            $price = FormLib::get('s_price', 0);
        }
        $tare = FormLib::get('s_tare',0);
        $shelf = FormLib::get('s_shelflife',0);
        $bycount = FormLib::get('s_bycount',0);
        $type = FormLib::get('s_type','Random Weight');
        $weight = ($type == 'Random Weight') ? 0 : 1;
        $text = FormLib::get('s_text',array());
        $textID = FormLib::get('s_text_id', array());
        $label = FormLib::get('s_label', 103);
        $netWeight = FormLib::get('s_netwt', 0);
        $linkedPLU = FormLib::get('s_linkedPLU', null);
        $inUse = FormLib::get('prod-in-use', array());

        $dbc = $this->db();

        // apostrophes might make a mess
        // double quotes definitely will
        // DGW quotes text fields w/o any escaping
        $desc = str_replace("'","",$desc);
        $desc = str_replace("\"","",$desc);
        for ($i=0; $i<count($text); $i++) {
            $text[$i] = str_replace("'","",$text[$i]);
            $text[$i] = str_replace("\"","",$text[$i]);
        }
        
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
        $scaleItem->text(isset($text[0]) ? $text[0] : '');
        $scaleItem->label($label);
        $scaleItem->graphics( ($label == 53) ? 121 : 0 );
        $scaleItem->netWeight($netWeight);
        $scaleItem->linkedPLU(BarcodeLib::padUPC($linkedPLU));
        $scaleItem->mosaStatement(FormLib::get('scale_mosa',false) ? 1 : 0);
        $scaleItem->originText(FormLib::get('scale_origin'));
        $scaleItem->reheat(FormLib::get('s_reheat',false) ? 1 : 0);
        $scaleItem->save();

        // extract scale PLU
        $s_plu = COREPOS\Fannie\API\item\ServiceScaleLib::upcToPLU($upc);

        $item_info = array(
            'RecordType' => $action,
            'PLU' => $s_plu,
            'Description' => $desc,
            'Tare' => $tare,
            'ShelfLife' => $shelf,
            'Price' => $price,
            'Label' => $label,
            'ExpandedText' => isset($text[0]) ? $text[0] : '',
            'ByCount' => $bycount,
            'OriginText' => $scaleItem->originText(),
            'MOSA' => $scaleItem->mosaStatement(),
            'Reheat' => $scaleItem->reheat(),
            'inUse' => count($inUse) == 0 ? 0 : 1,
        );
        $item_info['NetWeight'] = $netWeight;
        if ($label == 53) {
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
        foreach ($storePrices as $sID => $p) {
            $item_info['Price' . $sID] = $p;
        }

        $hashes = FormLib::get('s_text_hash');
        for ($i=0; $i<count($textID); $i++) {
            $ing = new ScaleIngredientsModel($dbc);
            $ing->upc($upc);
            $ing->storeID($textID[$i]);
            $ing->ingredients($text[$i]);
            $ing->save();
            $item_info['ExpandedText' . $textID[$i]] = $text[$i];
            if (md5($text[$i]) != $hashes[$i]) {
                $history = new ScaleIngredientHistoryModel($dbc);
                $history->upc($upc);
                $history->storeID($textID[$i]);
                $history->ingredients($text[$i]);
                $history->tdate(date('Y-m-d H:i:s'));
                $history->userID(FannieAuth::getUID());
                $history->save();
            }
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
                    'storeID' => $model->storeID(),
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
                // intentionally disabled. seems to trigger too often
                //\COREPOS\Fannie\API\item\HobartDgwLib::deleteItemsFromScales($item_info['PLU'], $scales); 
            }
        }
    }
}

