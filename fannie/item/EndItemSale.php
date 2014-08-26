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

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class EndItemSale extends FannieRESTfulPage {

    protected $header = 'Take item off sale';
    protected $title = 'Take item off sale';

    public $description = '[Take Item Off Sale] immediately stops sale pricing an item.';

    function post_id_handler(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upc = BarcodeLib::padUPC($this->id);

        $prodP = $dbc->prepare_statement('UPDATE products SET
                discounttype=0, special_price=0
                WHERE upc=?');
        $prodR = $dbc->exec_statement($prodP, array($upc));

        $batchID = FormLib::get_form_value('batchID');
        $batchUPC = FormLib::get_form_value('batchUPC');
        if ($batchID !== '' && $batchUPC !== ''){
            if (substr($batchUPC,0,2) != 'LC')
                $batchUPC = BarcodeLib::padUPC($batchUPC);
            $batchP = $dbc->prepare_statement('DELETE FROM batchList
                    WHERE upc=? AND batchID=?');
            $batchR = $dbc->exec_statement($batchP, array($batchUPC, $batchID));
        }

        require('laneUpdates.php');
        updateProductAllLanes($upc);

        header('Location: ItemEditorPage.php?searchupc='.$upc);
        return False;
    }

    function get_id_view(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upc = BarcodeLib::padUPC($this->id);

        $itemP = $dbc->prepare_statement('SELECT p.description,p.special_price,
                        CASE WHEN u.likeCode IS NULL THEN -1 ELSE u.likeCode END as lc
                        FROM products AS p LEFT JOIN upcLike AS u
                        ON p.upc=u.upc WHERE p.upc=?');
        $itemR = $dbc->exec_statement($itemP, array($upc));
        if ($dbc->num_rows($itemR)==0)
            return 'Item not found';
        $itemW = $dbc->fetch_row($itemR);
        $ret = '<form method="post" action="EndItemSale.php">
            <input type="hidden" name="id" value="'.$upc.'" />';
        $ret .= sprintf('%s is currently on sale for $%.2f', $itemW['description'], $itemW['special_price']);

        $batchP = $dbc->prepare_statement("SELECT b.batchName, b.batchID, l.upc FROM batches AS b 
            LEFT JOIN batchList as l
            on b.batchID=l.batchID WHERE '".date('Y-m-d')."' BETWEEN b.startDate
            AND b.endDate AND (l.upc=? OR l.upc=?)");
        $batchR = $dbc->exec_statement($batchP,array($upc,'LC'.$itemW['lc']));
        if ($dbc->num_rows($batchR) == 0){
            $ret .= '<br />The item does not appear to be in an active batch';
        }
        else {
            $batchW = $dbc->fetch_row($batchR);
            $ret .= '<br />The item will be removed from the batch <i>'.$batchW['batchName'].'</i>';
            $ret .= sprintf('<input type="hidden" name="batchID" value="%d" />
                    <input type="hidden" name="batchUPC" value="%s" />',
                    $batchW['batchID'],$batchW['upc']);
        }
        $ret .= '<br /><input type="submit" value="Take item off sale" id="button" />';
        return $ret;
    }

}

FannieDispatch::conditionalExec();

?>
