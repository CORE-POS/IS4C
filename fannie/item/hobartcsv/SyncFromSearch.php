<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Community Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
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
    public $themed = true;

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

        ob_start();
        echo '<ul>';
        // go through scales one at a time
        // check whether item is present on that
        // scale and do write or change as appropriate
        $dbc->startTransaction();
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
                    echo '<li class="alert-success">Sending <strong>' . $model->plu() . '</strong></li>';
                    // batch out changes @ 10 items / file
                    if (count($all_items) >= 10) {
                        \COREPOS\Fannie\API\item\HobartDgwLib::writeItemsToScales($all_items, array($scale));
                        \COREPOS\Fannie\API\item\EpScaleLib::writeItemsToScales($all_items, array($scale));
                        $all_items = array();
                    }
                } else {
                    echo '<li class="alert-danger">Error on <strong>' . $model->plu() . '</strong></li>';
                }
            } // end loop on items
            echo '</ul>';

            if (count($all_items) > 0) {
                \COREPOS\Fannie\API\item\HobartDgwLib::writeItemsToScales($all_items, array($scale));
                \COREPOS\Fannie\API\item\EpScaleLib::writeItemsToScales($all_items, array($scale));
            }
        } // end loop on scales
        $dbc->commitTransaction();
        $this->sent_status = ob_get_clean();

        return true;
    }

    function post_sendall_view()
    {
        return $this->sent_status;
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

                \COREPOS\Fannie\API\item\HobartDgwLib::writeItemsToScales($item_info, array($scale));
                \COREPOS\Fannie\API\item\EpScaleLib::writeItemsToScales($item_info, array($scale));
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
            'Description' => $model->mergeDescription(),
            'Tare' => $model->tare(),
            'ShelfLife' => $model->shelflife(),
            'Price' => $model->price(),
            'Label' => $model->label(),
            'ExpandedText' => $model->text(),
            'ByCount' => $model->bycount(),
            'OriginText' => $model->originText(),
            'MOSA' => $model->mosaStatement(),
        );
        if ($model->netWeight() != 0) {
            $item_info['NetWeight'] = $model->netWeight();
        }
        if ($model->graphics()) {
            $item_info['Graphics'] = $model->graphics();
        }
        if ($model->weight() == 1) {
            $item_info['Type'] = 'Fixed Weight';
            $item_info['ByCount'] = 1;
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
        $ret .= '<div class="panel panel-default">
            <div class="panel-heading">Scales</div>
            <div class="panel-body">';
        foreach ($scales->find('description') as $scale) {
            $ret .= sprintf('<input type="checkbox" class="scaleID" name="scaleID[]" 
                                id="scaleID%d" value="%d" />
                             <label for="scaleID%d">%s</label><br />',
                             $scale->serviceScaleID(), $scale->serviceScaleID(),
                             $scale->serviceScaleID(), $scale->description());
        }
        $ret .= '</div></div>';
        $ret .= '<p><button type="submit" name="sendall" value="1"
                    class="btn btn-default">Sync All Items</button></p>';
        $ret .= '<div id="alert-area"></div>';
        $ret .= '<table class="table">';
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
                            <td><button type="button" class="btn btn-default"
                                onclick="sendOne(\'%s\'); return false;">Sync Item</button></td>
                            <input type="hidden" name="upcs[]" value="%s" />
                            </tr>',
                            $model->plu(),
                            $model->plu(),
                            $model->mergeDescription(),
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
                data: 'sendupc='+upc+'&'+scaleStr
            }).done(function(result) {
                if (result.error) {
                    showBootstrapAlert('#alert-area', 'danger', 'Error sending item ' + upc);
                } else {
                    showBootstrapAlert('#alert-area', 'success', 'Sent item ' + upc);
                    $('#row'+upc).remove();
                }
            });
        }
        <?php
        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>
            Push item(s) from an advanced search to service scales.
            Currently Hobart Quantums are supported. Choose which scale(s)
            the items should be sent to and then either sync all items with
            buttons at the top or sync individual items with the buttons
            in the list of items.
            </p>';

    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->javascriptContent()));
        $this->u = 'foo';
        ob_start();
        $phpunit->assertEquals(false, $this->post_u_handler());
        $this->u = '4011';
        $phpunit->assertEquals(true, $this->post_u_handler());
        ob_get_clean();
        $phpunit->assertNotEquals(0, strlen($this->post_u_view()));
    }
}

FannieDispatch::conditionalExec();

