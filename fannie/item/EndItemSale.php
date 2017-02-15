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

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class EndItemSale extends FannieRESTfulPage {

    protected $header = 'Take item off sale';
    protected $title = 'Take item off sale';

    public $description = '[Take Item Off Sale] immediately stops sale pricing an item.';
    public $themed = true;

    function post_id_handler(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upc = BarcodeLib::padUPC($this->id);

        $model = new ProductsModel($dbc);
        $model->upc($upc);
        $model->store_id(1);
        $model->discounttype(0);
        $model->special_price(0);
        $model->modified(date('Y-m-d H:i:s'));
        if ($this->config->get('STORE_MODE') == 'HQ') {
            $stores = new StoresModel($dbc);
            $stores->hasOwnItems(1);
            foreach ($stores->find() as $obj) {
                $model->store_id($obj->storeID());
                $model->save();
            }
        } else {
            $model->save();
        }

        $batchID = FormLib::get_form_value('batchID');
        $batchUPC = FormLib::get_form_value('batchUPC');
        if ($batchID !== '' && $batchUPC !== ''){
            if (substr($batchUPC,0,2) != 'LC')
                $batchUPC = BarcodeLib::padUPC($batchUPC);
            $batchP = $dbc->prepare('DELETE FROM batchList
                    WHERE upc=? AND batchID=?');
            $batchR = $dbc->execute($batchP, array($batchUPC, $batchID));
        }

        COREPOS\Fannie\API\data\ItemSync::sync($upc);

        header('Location: ItemEditorPage.php?searchupc='.$upc);
        return False;
    }

    function get_id_view(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upc = BarcodeLib::padUPC($this->id);

        $itemP = $dbc->prepare('SELECT p.description,p.special_price,
                        CASE WHEN u.likeCode IS NULL THEN -1 ELSE u.likeCode END as lc
                        FROM products AS p LEFT JOIN upcLike AS u
                        ON p.upc=u.upc WHERE p.upc=?');
        $itemR = $dbc->execute($itemP, array($upc));
        if ($dbc->num_rows($itemR)==0)
            return '<div class="alert alert-danger">Item not found</div>';
        $itemW = $dbc->fetch_row($itemR);
        $ret = '<form method="post" action="EndItemSale.php">
            <input type="hidden" name="id" value="'.$upc.'" />';
        $ret .= sprintf('<p>%s is currently on sale for $%.2f', $itemW['description'], $itemW['special_price']);

        $batchP = $dbc->prepare("SELECT b.batchName, b.batchID, l.upc FROM batches AS b 
            LEFT JOIN batchList as l
            on b.batchID=l.batchID WHERE '".date('Y-m-d')."' BETWEEN b.startDate
            AND b.endDate AND (l.upc=? OR l.upc=?)");
        $batchR = $dbc->execute($batchP,array($upc,'LC'.$itemW['lc']));
        if ($dbc->num_rows($batchR) == 0) {
            $ret .= '<div class="alert alert-warning">The item does not appear to be in an active batch</div>';
        } else {
            $batchW = $dbc->fetch_row($batchR);
            $ret .= '<br />The item will be removed from the batch <strong>'.$batchW['batchName'].'</strong>';
            $ret .= sprintf('<input type="hidden" name="batchID" value="%d" />
                    <input type="hidden" name="batchUPC" value="%s" />',
                    $batchW['batchID'],$batchW['upc']);
        }
        $ret .= '<br /><button type="submit" class="btn btn-default" id="button">Take item off sale</button>';
        $ret .= '</p>';

        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            Immediately take an item off sale. Changes will
            be pushed out to the lanes. If CORE can determine
            which sale batch the item is in, it will also be
            removed from that batch.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $this->id = '4011';
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
    }

}

FannieDispatch::conditionalExec();

