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

use COREPOS\Fannie\API\item\ItemModule;
use COREPOS\Fannie\API\item\ItemRow;

class ItemFlagsModule extends ItemModule implements ItemRow
{

    public function width()
    {
        return self::META_WIDTH_FULL;
    }

    private function getFlags($upc, $storeID=false)
    {
        $dbc = $this->db();
        $query = "
            SELECT f.description,
                f.bit_number,
                (1<<(f.bit_number-1)) & p.numflag AS flagIsSet
            FROM products AS p, 
                prodFlags AS f
            WHERE p.upc=?
                " . ($storeID ? ' AND p.store_id=? ' : '') . "
                AND f.active=1";
        $args = array($upc);
        if ($storeID) {
            $args[] = $storeID;
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
            // embed flag info to avoid re-querying it on save
            $ret .= sprintf('<input type="hidden" name="pf_attrs[]" value="%s" />
                            <input type="hidden" name="pf_bits[]" value="%d" />',
                            $row['description'], $row['bit_number']);
            $i++;
        }
        $ret .= '</tr></table>';

        $ret .= '</div>' . '<!-- /#ItemFlagsTable -->';
        $ret .= '</div>' . '<!-- /#ItemFlagsContents -->';
        $ret .= '</div>' . '<!-- /#ItemFlagsFieldset -->';

        return $ret;
    }

    public function formRow($upc, $activeTab, $storeID)
    {
        if (FannieConfig::config('STORE_MODE') == 'HQ') {
            return $this->rowOfFlags($upc, $storeID);
        }

        return $activeTab ? $this->rowOfFlags($upc) : '';
    }

    private function rowOfFlags($upc, $storeID=false)
    {
        $upc = BarcodeLib::padUPC($upc);
        $dbc = $this->db();
        $res = $this->getFlags($upc, $storeID);

        $ret = '<tr class="small"><th class="text-right">Flags</th><td colspan="9">';
        while ($row = $dbc->fetchRow($res)) {
            $ret .= sprintf('<label><input type="checkbox" name="flags%s[]" value="%d" %s />
                    %s</label>&nbsp;&nbsp;&nbsp;',
                    ($storeID ? $storeID : ''),
                    $row['bit_number'], ($row['flagIsSet'] ? 'checked' : ''), $row['description']);
            // embed flag info to avoid re-querying it on save
            $ret .= sprintf('<input type="hidden" name="pf_attrs%s[]" value="%s" />
                            <input type="hidden" name="pf_bits%s[]" value="%d" />',
                            ($storeID ? $storeID : ''), $row['description'],
                            ($storeID ? $storeID : ''), $row['bit_number']);
        }
        $ret .= '</td></tr>';
        if ($storeID) {
            $ret .= '<input type="hidden" name="flagStores[]" value="' . $storeID . '" />';
        }

        return $ret;
    }

    public function saveFormData($upc)
    {
        $multi = FormLib::get('flagStores');
        if (is_array($multi)) {
            $ret = true;
            foreach ($multi as $store) {
                $ret = $this->realSave($upc, $store);
            }

            return $ret;
        }

        return $this->realSave($upc, '');
    }

    private function actionMap($dbc)
    {
        $res = $dbc->query("SELECT bit_number, action FROM prodFlags WHERE action IS NOT NULL AND action <> ''");
        $ret = array();
        while ($row = $dbc->fetchRow($res)) {
            $ret[$row['bit_number']] = $row['action'];
        }

        return $ret;
    }

    private function realSave($upc, $suffix)
    {
        try {
            $fName = 'flags' . $suffix;
            $flags = $this->form->{$fName};
            $aName = 'pf_attrs' . $suffix;
            $attrs = $this->form->{$aName};
            $bName = 'pf_bits' . $suffix;
            $bits = $this->form->{$bName};
        } catch (Exception $ex) {
            $flags = array();
            $attrs = array();
            $bits = array();
        }
        if (!is_array($flags)) {
            return false;
        }

        $dbc = $this->connection;

        /**
          Collect known flags and initialize
          JSON object with all flags false
        */
        $json = array();
        $bitStatus = array();
        $flagMap = array();
        for ($i=0; $i<count($attrs); $i++) {
            $json[$attrs[$i]] = false;
            $flagMap[$bits[$i]] = $attrs[$i];
            $bitStatus[$bits[$i]] = false;
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
            $bitStatus[$f] = true;
        }

        $model = new ProductsModel($dbc);
        $model->upc($upc);
        $model->numflag($numflag);
        if ($suffix) {
            $model->store_id($suffix);
        }
        $model->enableLogging(false);
        $saved = $model->save();

        /**
          Only add attributes entry if it changed
        */
        if ($suffix === '' || $suffix == FannieConfig::config('STORE_ID')) {
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
        }

        $this->queueActions($this->actionMap($dbc), $bitStatus, $upc, $suffix);

        return $saved ? true : false;
    }

    private function queueActions($actions, $flags, $upc, $store)
    {
        $queue = new  COREPOS\Fannie\API\jobs\QueueManager();
        $logger = new FannieLogger();

        foreach ($actions as $flagID => $action) {
            $status = (isset($flags[$flagID]) && $flags[$flagID]) ? 1 : 0;
            $job = array(
                'class' => $action,
                'data' => array(
                    'upc' => $upc,
                    'store' => $store,
                    'flag' => $status,
                ),
            );
            $now = microtime(true);
            $queue->add($job);
            $logger->debug('Queueing time: ' . (microtime(true) - $now));
        }
    }
}

