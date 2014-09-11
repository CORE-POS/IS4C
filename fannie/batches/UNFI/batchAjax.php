<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}

$dbc = FannieDB::get($FANNIE_OP_DB);

$upc = FormLib::get_form_value('upc');
$action = FormLib::get_form_value('action','unknown');
switch($action){
case 'addVarPricing':
    $prep = $dbc->prepare_statement("UPDATE prodExtra SET variable_pricing=1 WHERE upc=?");
    $dbc->exec_statement($prep,array($upc));
    break;
case 'delVarPricing':
    $prep = $dbc->prepare_statement("UPDATE prodExtra SET variable_pricing=0 WHERE upc=?");
    $dbc->exec_statement($prep,array($upc));
    break;
case 'newPrice':
    $vid = FormLib::get_form_value('vendorID');
    $bid = FormLib::get_form_value('batchID');
    $sid = FormLib::get_form_value('superID',0);
    if ($sid == 99) $sid = 0;
    $price = FormLib::get_form_value('price',0);
    $sP = $dbc->prepare_statement("UPDATE vendorSRPs SET srp=? WHERE upc=? AND vendorID=?");
    $dbc->exec_statement($sP,array($price,$upc,$vid));
    $model = new BatchListModel($dbc);
    $model->batchID($bid);
    $model->upc($upc);
    $model->salePrice($price);
    $model->save();
    $tag = new ShelftagsModel($tag);
    $tag->id($sid);
    $tag->upc($upc);
    $tag->normal_price($price);
    $tag->save();
    echo "New Price Applied";
    break;
case 'batchAdd':
    $vid = FormLib::get_form_value('vendorID');
    $bid = FormLib::get_form_value('batchID');
    $sid = FormLib::get_form_value('superID',0);
    if ($sid == 99) $sid = 0;
    $price = FormLib::get_form_value('price',0);

    $model = new BatchListModel($dbc);
    $model->batchID($bid);
    $model->upc($upc);
    $model->salePrice($price);
    $model->pricemethod(0);
    $model->quantity(0);
    $model->save();

    /* get shelftag info */
    $infoQ = $dbc->prepare_statement("SELECT p.description,v.brand,v.sku,v.size,v.units,b.vendorName
        FROM products AS p LEFT JOIN vendorItems AS v ON p.upc=v.upc AND
        v.vendorID=? LEFT JOIN vendors AS b ON v.vendorID=b.vendorID
        WHERE p.upc=?");
    $info = $dbc->fetch_row($dbc->exec_statement($infoQ,array($vid,$upc)));
    $ppo = PriceLib::pricePerUnit($price,$info['size']);
    
    /* create a shelftag */
    $tag = new ShelftagsModel($dbc);
    $tag->id($sid);
    $tag->upc($upc);
    $tag->description($info['description']);
    $tag->normal_price($price);
    $tag->brand($info['brand']);
    $tag->sku($info['sku']);
    $tag->size($info['size']);
    $tag->units($info['units']);
    $tag->vendor($info['vendorName']);
    $tag->pricePerUnit($ppo);
    $tag->save();

    break;
case 'batchDel':
    $vid = FormLib::get_form_value('vendorID');
    $bid = FormLib::get_form_value('batchID');
    $sid = FormLib::get_form_value('superID',0);
    if ($sid == 99) $sid = 0;

    $model = new BatchListModel($dbc);
    $model->batchID($bid);
    $model->upc($upc);
    $model->delete();

    $tag = new ShelftagsModel($dbc);
    $tag->id($sid);
    $tag->upc($upc);
    $tag->delete();

    break;
}

?>
