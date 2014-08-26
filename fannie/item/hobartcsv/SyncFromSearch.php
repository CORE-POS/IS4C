<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Community Co-op

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

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class SyncFromSearch extends FannieRESTfulPage
{
    protected $header = 'Send Items to Scales';
    protected $title = 'Send Items to Scales';

    public $description = '[Scale Sync] sends a set of advanced search items to
    specified scales (Hobart). Must be accessed via Advanced Search.';

    protected $window_dressing = false;

    private $upcs = array();
    private $save_results = array();

    function preprocess()
    {
       $this->__routes[] = 'post<u>'; 
       $this->__routes[] = 'post<sendall>';
       $this->__routes[] = 'post<sendupc>';
       return parent::preprocess();
    }

    function post_sendall_handler()
    {
        global $FANNIE_OP_DB;
        $model = new ScaleItemsModel(FannieDB::get($FANNIE_OP_DB));
        $upcs = FormLib::get('upcs', array());
        $all_items = array();
        $scales = $this->scalesFromIDs(FormLib::get('scaleID', array()));

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $chkMap = $dbc->prepare('
            SELECT upc
            FROM ServiceScaleItemMap
            WHERE serviceScaleID=?
                AND upc=?
        ');
        $addMap = $dbc->prepare('
            INSERT INTO ServiceScaleItemMap
                (serviceScaleID, upc)
            VALUES
                (?, ?)
        ');

        echo '<ul>';
        // go through scales one at a time
        // check whether item is present on that
        // scale and do write or change as appropriate
        foreach ($scales as $scale) {

            foreach ($upcs as $upc) {
                $model->reset();
                $model->plu(BarcodeLib::padUPC($upc));

                if ($model->load()) {
                    $item_info = $this->getItemInfo($model);
                    $chk = $dbc->execute($chkMap, array($scale['id'], $upc));
                    if ($dbc->num_rows($chk) == 0) {
                        $dbc->execute($addMap, array($scale['id'], $upc));
                        $item_info['RecordType'] = 'WriteOneItem';
                    }
                    $all_items[] = $item_info;
                    echo '<li style="color:green;">Sending <b>' . $model->plu() . '</b></li>';
                    // batch out changes @ 10 items / file
                    if (count($all_items) >= 10) {
                        HobartDgwLib::writeItemsToScales($all_items, array($scale));
                        $all_items = array();
                    }
                } else {
                    echo '<li style="color:red;">Error on <b>' . $model->plu() . '</b></li>';
                }
            } // end loop on items
            echo '</ul>';

            if (count($all_items) > 0) {
                HobartDgwLib::writeItemsToScales($all_items, array($scale));
            }
        } // end loop on scales

        return false;
    }

    function post_sendupc_handler()
    {
        global $FANNIE_OP_DB;
        $model = new ScaleItemsModel(FannieDB::get($FANNIE_OP_DB));
        $model->plu(BarcodeLib::padUPC($this->sendupc));

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $chkMap = $dbc->prepare('
            SELECT upc
            FROM ServiceScaleItemMap
            WHERE serviceScaleID=?
                AND upc=?
        ');
        $addMap = $dbc->prepare('
            INSERT INTO ServiceScaleItemMap
                (serviceScaleID, upc)
            VALUES
                (?, ?)
        ');

        $scales = $this->scalesFromIDs(FormLib::get('scaleID', array()));

        if ($model->load() && is_array($scales)) {
            $item_info = $this->getItemInfo($model);

            // go through scales one at a time
            // check whether item is present on that
            // scale and do write or change as appropriate
            foreach ($scales as $scale) {

                $chk = $dbc->execute($chkMap, array($scale['id'], $this->sendupc));
                if ($dbc->num_rows($chk) == 0) {
                    $dbc->execute($addMap, array($scale['id'], $this->sendupc));
                    $item_info['RecordType'] = 'WriteOneItem';
                } else {
                    $item_info['RecordType'] = 'ChangeOneItem';
                }

                HobartDgwLib::writeItemsToScales($item_info, array($scale));
            }

            echo '{error:0}';
        } else {
            echo '{error:1}';
        }

        return false;
    }

    private function scalesFromIDs($ids)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $model = new ServiceScalesModel($dbc);
        $scales = array();
        foreach ($ids as $id) {
            $model->reset();
            $model->serviceScaleID($id);
            if (!$model->load()) {
                continue;
            }
            $scales[] = array(
                'host' => $model->host(),
                'type' => $model->scaleType(),
                'dept' => $model->scaleDeptName(),
                'id' => $model->serviceScaleID(),
            );
        }

        return $scales;
    }

    private function getItemInfo($model)
    {
        // extract scale PLU
        preg_match("/002(\d\d\d\d)0/", $model->plu(), $matches);
        $s_plu = $matches[1];

        $item_info = array(
            'RecordType' => 'ChangeOneItem',
            'PLU' => $s_plu,
            'Description' => $model->itemdesc(),
            'Tare' => $model->tare(),
            'ShelfLife' => $model->shelflife(),
            'Price' => $model->price(),
            'Label' => $model->label(),
            'ExpandedText' => $model->text(),
            'ByCount' => $model->bycount(),
        );
        if ($model->netWeight() != 0) {
            $item_info['NetWeight'] = $model->netWeight();
        }
        if ($model->graphics()) {
            $item_info['Graphics'] = $model->graphics();
        }
        if ($model->weight() == 1) {
            $item_info['Type'] = 'Fixed Weight';
        } else {
            $item_info['Type'] = 'Random Weight';
        }

        return $item_info;
    }

    function post_u_handler()
    {
        if (!is_array($this->u)) {
            $this->u = array($this->u);
        }
        foreach($this->u as $postdata) {
            if (is_numeric($postdata)) {
                $this->upcs[] = BarcodeLib::padUPC($postdata);
            }
        }

        if (empty($this->upcs)) {
            echo 'Error: no valid data';
            return false;
        } else {
            return true;
        }
    }

    function post_u_view()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $this->add_script($FANNIE_URL.'/src/javascript/jquery.js');
        $this->add_css_file($FANNIE_URL.'/src/style.css');
        $ret = '';

        $ret .= '<form action="SyncFromSearch.php" method="post">';
        $scales = new ServiceScalesModel(FannieDB::get($FANNIE_OP_DB));
        $ret .= '<fieldset><legend>Scales</legend>';
        foreach ($scales->find('description') as $scale) {
            $ret .= sprintf('<input type="checkbox" class="scaleID" name="scaleID[]" 
                                id="scaleID%d" value="%d" />
                             <label for="scaleID%d">%s</label><br />',
                             $scale->serviceScaleID(), $scale->serviceScaleID(),
                             $scale->serviceScaleID(), $scale->description());
        }
        $ret .= '</fieldset>';
        $ret .= '<input type="submit" name="sendall" value="Sync All Items" />';
        $ret .= '<table cellpadding="4" cellspacing="0" border="1">';
        $ret .= '<tr>
                <th>UPC</th>
                <th>Description</th>
                <th>Price</th>
                <th>Last Changed</th>
                <th>Send Now</th>
                </tr>';
        $model = new ScaleItemsModel(FannieDB::get($FANNIE_OP_DB));
        foreach ($this->upcs as $upc) {
            $model->plu($upc);
            if (!$model->load()) {
                continue;
            }
            $ret .= sprintf('<tr id="row%s">
                            <td>%s</td>
                            <td>%s</td>
                            <td>%.2f</td>
                            <td>%s</td>
                            <td><button onclick="sendOne(\'%s\'); return false;">Sync Item</button></td>
                            <input type="hidden" name="upcs[]" value="%s" />
                            </tr>',
                            $model->plu(),
                            $model->plu(),
                            $model->itemdesc(),
                            $model->price(),
                            $model->modified(),
                            $model->plu(),
                            $model->plu()
            );
        }
        $ret .= '</table>';
        $ret .= '</form>';

        return $ret;
    }

    public function javascriptContent()
    {
        ob_start();
        ?>
        function sendOne(upc)
        {
            var scaleStr = $('.scaleID').serialize();
            if (scaleStr == '') {
                alert('Must select a scale');

                return false;
            }
            $.ajax({
                type: 'post',
                data: 'sendupc='+upc+'&'+scaleStr,
                success: function(result) {
                    $('#row'+upc).remove();
                }
            });
        }
        <?php
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

