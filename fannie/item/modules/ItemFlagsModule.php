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

class ItemFlagsModule extends \COREPOS\Fannie\API\item\ItemModule 
{

    public function width()
    {
        return self::META_WIDTH_FULL;
    }

    private function getFlags($upc)
    {
        $dbc = $this->db();
        $query = "
            SELECT f.description,
                f.bit_number,
                (1<<(f.bit_number-1)) & p.numflag AS flagIsSet
            FROM products AS p, 
                prodFlags AS f
            WHERE p.upc=?
                " . (FannieConfig::config('STORE_MODE') == 'HQ' ? ' AND p.store_id=? ' : '') . "
                AND f.active=1";
        $args = array($upc);
        if (FannieConfig::config('STORE_MODE') == 'HQ') {
            $args[] = FannieConfig::config('STORE_ID');
        }
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep,$args);
        
        if ($dbc->numRows($res) == 0){
            // item does not exist
            $prep = $dbc->prepare('
                SELECT f.description,
                    f.bit_number,
                    0 AS flagIsSet
                FROM prodFlags AS f
                WHERE f.active=1');
            $res = $dbc->execute($prep);
        }

        return $res;
    }

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $upc = BarcodeLib::padUPC($upc);

        $ret = '';
        $ret = '<div id="ItemFlagsFieldset" class="panel panel-default">';
        $ret .=  "<div class=\"panel-heading\">
                <a href=\"\" onclick=\"\$('#ItemFlagsContents').toggle();return false;\">
                Flags
                </a></div>";
        $css = ($expand_mode == 1) ? '' : ' collapse';
        $ret .= '<div id="ItemFlagsContents" class="panel-body' . $css . '">';
        // class="col-lg-1" works pretty well with META_WIDTH_HALF
        $ret .= '<div id="ItemFlagsTable" class="col-sm-5">';

        $dbc = $this->db();
        $res = $this->getFlags($upc);

        $tableStyle = " style='border-spacing:5px; border-collapse: separate;'";
        $ret .= "<table{$tableStyle}>";
        $i=0;
        while($row = $dbc->fetchRow($res)){
            if ($i==0) $ret .= '<tr>';
            if ($i != 0 && $i % 2 == 0) $ret .= '</tr><tr>';
            $ret .= sprintf('<td><input type="checkbox" id="item-flag-%d" name="flags[]" value="%d" %s /></td>
                <td><label for="item-flag-%d">%s</label></td>',$i, $row['bit_number'],
                ($row['flagIsSet']==0 ? '' : 'checked'),
                $i,
                $row['description']
            );
            $i++;
        }
        $ret .= '</tr></table>';

        $ret .= '</div>' . '<!-- /#ItemFlagsTable -->';
        $ret .= '</div>' . '<!-- /#ItemFlagsContents -->';
        $ret .= '</div>' . '<!-- /#ItemFlagsFieldset -->';

        return $ret;
    }

    public function saveFormData($upc)
    {
        try {
            $flags = $this->form->flags;
        } catch (Exception $ex) {
            $flags = array();
        }
        if (!is_array($flags)) {
            return false;
        }

        $dbc = $this->connection;

        /**
          Collect known flags and initialize
          JSON object with all flags false
        */
        $res = $this->getFlags($upc);
        $json = array();
        $flagMap = array();
        while ($row = $dbc->fetchRow($res)) {
            $json[$row['description']] = false;
            $flagMap[$row['bit_number']] = $row['description'];
        }

        $numflag = 0;   
        foreach ($flags as $f) {
            if ($f != (int)$f) {
                continue;
            }
            $numflag = $numflag | (1 << ($f-1));

            // set flag in JSON representation
            $attr = $flagMap[$f];
            $json[$attr] = true;
        }

        $model = new ProductsModel($dbc);
        $model->upc($upc);
        $model->numflag($numflag);
        $model->enableLogging(false);
        if (FannieConfig::config('STORE_MODE') === 'HQ') {
            $stores = FormLib::get('store_id');
            foreach ($stores as $s) {
                $model->store_id($s);
                $saved = $model->save();
            }
        } else {
            $saved = $model->save();
        }

        /**
          Only add attributes entry if it changed
        */
        $curQ = 'SELECT attributes FROM ProductAttributes WHERE upc=? ORDER BY modified DESC';
        $curQ = $dbc->addSelectLimit($curQ, 1);
        $curP = $dbc->prepare($curQ);
        $current = $dbc->getValue($curP, array($upc));
        $curJSON = json_decode($current, true);
        if ($current === false || $curJSON != $json) {
            $model = new ProductAttributesModel($dbc);
            $model->upc($upc);
            $model->modified(date('Y-m-d H:i:s'));
            $model->attributes(json_encode($json));
            $model->save();
        }

        return $saved ? true : false;
    }
}

