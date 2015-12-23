<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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
    $prep = $dbc->prepare("UPDATE prodExtra SET variable_pricing=1 WHERE upc=?");
    $dbc->execute($prep,array($upc));
    $prod = new ProductsModel($dbc);
    $prod->upc($upc);
    foreach ($prod->find('store_id') as $p) {
        // make sure another rule isn't overwritten with a generic one
        if ($p->price_rule_id() == 0) {
            $p->price_rule_id(1);
        }
        $p->save();
    }
    break;
case 'delVarPricing':
    $prep = $dbc->prepare("UPDATE prodExtra SET variable_pricing=0 WHERE upc=?");
    $dbc->execute($prep,array($upc));
    $ruleProd = new ProductsModel($dbc);
    $prod = new ProductsModel($dbc);
    $prod->upc($upc);
    foreach ($prod->find('store_id') as $p) {
        $ruleID = 0;
        // remove the rule but save its ID
        if ($p->price_rule_id() != 0) {
            $ruleID = $p->price_rule_id();
            $p->price_rule_id(0);
        }
        $p->save();
        // make sure no other item is using the same
        // rule before deleting it
        if ($ruleID > 1) {
            $ruleProd->reset();
            $ruleProd->price_rule_id($ruleID);
            if (count($ruleProd->find()) == 0) {
                // no products are using this rule
                $rule = new PriceRulesModel($dbc);
                $rule->priceRuleID($ruleID);
                $rule->delete();
            }
        }
    }
    break;
case 'newPrice':
    $vid = FormLib::get_form_value('vendorID');
    $bid = FormLib::get_form_value('batchID');
    $sid = FormLib::get_form_value('queueID',0);
    if ($sid == 99) $sid = 0;
    $price = FormLib::get_form_value('price',0);
    $viP = $dbc->prepare('
        UPDATE vendorItems
        SET srp=?,
            modified=' . $dbc->now() . '
        WHERE upc=?
            AND vendorID=?');
    $dbc->execute($viP, array($price,$upc,$vid));
    $batchP = $dbc->prepare('
        UPDATE batchList
        SET salePrice=?
        WHERE batchID=?
            AND upc=?');
    $dbc->execute($batchP, array($price, $bid, $upc));
    if ($dbc->tableExists('vendorSRPs')) {
        $sP = $dbc->prepare("UPDATE vendorSRPs SET srp=? WHERE upc=? AND vendorID=?");
        $dbc->execute($sP,array($price,$upc,$vid));
    }
    echo "New Price Applied";
    break;
case 'batchAdd':
    $vid = FormLib::get_form_value('vendorID');
    $bid = FormLib::get_form_value('batchID');
    $sid = FormLib::get_form_value('queueID',0);
    if ($sid == 99) $sid = 0;
    $price = FormLib::get_form_value('price',0);

    $model = new BatchListModel($dbc);
    $model->batchID($bid);
    $model->upc($upc);
    $model->salePrice($price);
    $model->pricemethod(0);
    $model->quantity(0);
    $model->save();

    $product = new ProductsModel($dbc);
    $product->upc($upc);
    $info = $product->getTagData($price);
    
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
    $tag->vendor($info['vendor']);
    $tag->pricePerUnit($info['pricePerUnit']);
    $tag->save();

    break;
case 'batchDel':
    $vid = FormLib::get_form_value('vendorID');
    $bid = FormLib::get_form_value('batchID');
    $sid = FormLib::get_form_value('queueID',0);
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

