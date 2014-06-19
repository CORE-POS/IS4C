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

include('../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class SyncFromSearch extends FannieRESTfulPage
{
    protected $header = 'Send Items to Scales';
    protected $title = 'Send Items to Scales';

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
        echo '<ul>';
        foreach ($upcs as $upc) {
            $model->reset();
            $model->plu(BarcodeLib::padUPC($upc));
            if ($model->load()) {
                $all_items[] = $this->getItemInfo($model);
                echo '<li style="color:green;">Sending <b>' . $model->plu() . '</b></li>';
                // batch out changes @ 50 items / file
                if (count($all_items) > 50) {
                    HobartDgwLib::writeItemsToScales($all_items);
                    $all_items = array();
                }
            } else {
                echo '<li style="color:red;">Error on <b>' . $model->plu() . '</b></li>';
            }
        }
        echo '</ul>';

        if (count($all_items) > 0) {
            HobartDgwLib::writeItemsToScales($all_items);
        }

        return false;
    }

    function post_sendupc_handler()
    {
        global $FANNIE_OP_DB;
        $model = new ScaleItemsModel(FannieDB::get($FANNIE_OP_DB));
        $model->plu(BarcodeLib::padUPC($this->sendupc));

        if ($model->load()) {
            $item_info = $this->getItemInfo($model);

            HobartDgwLib::writeItemsToScales($item_info);

            echo '{error:0}';
        } else {
            echo '{error:1}';
        }

        return false;
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
        $this->add_script($FANNIE_URL.'/src/jquery/jquery.js');
        $this->add_css_file($FANNIE_URL.'/src/style.css');
        $ret = '';

        $ret .= '<form action="SyncFromSearch.php" method="post">';
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
            $.ajax({
                type: 'post',
                data: 'sendupc='+upc,
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

