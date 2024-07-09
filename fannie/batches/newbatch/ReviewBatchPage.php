<?php
/*******************************************************************************

    Copyright 2009,2010 Whole Foods Co-op

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

use COREPOS\Fannie\API\lib\PriceLib;

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}
if (!function_exists('checkLogin')) {
    include_once(__DIR__ . '/../../auth/login.php');
}

class ReviewBatchPage extends FannieRESTfulPage
{
    protected $must_authenticate = true;
    protected $auth_classes = array('batches','batches_audited');
    protected $title = 'Review Batches Tool';
    protected $header = 'Review Batches Tool';
    protected $enable_linea = true;
    protected $debug_routing = false;

    public $description = '[Sales Batches] is the primary tool for creating, editing, and managing
    sale and price change batches.';

    private $audited = 1;
    private $con = null;

    public function preprocess()
    {
        return parent::preprocess();
    }

    public function get_id_view()
    {
        $id = FormLib::get('id');
        $listed = array();

        $listModel = new BatchListModel($this->connection);
        $listModel->batchID($id);

        $prodModel = new ProductsModel($this->connection);
        $prodModelB = new ProductsModel($this->connection);

        $prodReviewModel = new ProdReviewModel($this->connection);

        $brandsP = $this->connection->prepare("SELECT upc FROM products WHERE brand = ?
            AND upc NOT IN (SELECT upc FROM batchList WHERE batchID = ?)");

        $lastSoldP = $this->connection->prepare("SELECT last_sold FROM products WHERE upc = ? ORDER BY last_sold DESC LIMIT 1");

        $td = '';
        foreach ($listModel->find() as $k => $obj) {
            $otherItems = array();
            $listID = $obj->listID();
            $salePrice = $obj->salePrice();
            $upc = $obj->upc();

            $prodModel->reset();
            $prodModel->upc($upc);
            $prodModel->load();
            $brand = $prodModel->brand();
            $description = $prodModel->description();
            $size = $prodModel->size();
            $sizeMatchA = preg_replace("/[^0-9]/", '', $size);
            $sizeMatchA = floor($sizeMatchA);
            $normal_price = $prodModel->normal_price();
            $default_vendor_id = $prodModel->default_vendor_id();
            $cost = $prodModel->cost();

            $lastSoldR = $this->connection->execute($lastSoldP, array($upc));
            $row = $this->connection->fetchRow($lastSoldR);
            $last_sold = $row['last_sold'];
            $last_sold = substr($last_sold, 0, 10);

            $prodReviewModel->reset();
            $prodReviewModel->upc($upc);
            $prodReviewModel->vendorID($default_vendor_id);
            $prodReviewModel->load();
            $reviewed = $prodReviewModel->reviewed();

            $date1 = new DateTime();
            $date1 = $date1->format('Y-m-d');
            $date1 = strtotime($date1);

            $date2 = new DateTime($reviewed);
            $date2 = $date2->format('Y-m-d');
            $date2 = strtotime($date2);

            $date3 = new DateTime($last_sold);
            $date3 = $date3->format('Y-m-d');
            $date3 = strtotime($date3);

            $dateDiff = $date1 - $date2;
            $diff = round($dateDiff / (60 * 60 * 24));
            $diff = round($diff / 30, 1);

            $dateDiff = $date1 - $date3;
            $lastSoldDiff = round($dateDiff / (60 * 60 * 24));
            $lastSoldDiff = round($lastSoldDiff / 30, 1);

            $diffColor = '';
            if ($diff <= 1) $diffColor = 'alert-success';
            if ($diff > 1) $diffColor = 'alert-warning';
            if ($diff > 3) $diffColor = 'alert-danger';

            $lastSoldCol = 'white';
            if ($lastSoldDiff < 1) $lastSoldCol = 'alert-success';
            if ($lastSoldDiff == 0) $lastSoldCol = 'alert-info';
            if ($lastSoldDiff >= 1) $lastSoldCol = 'alert-warning';
            if ($lastSoldDiff > 2) $lastSoldCol = 'alert-danger';
            if ($lastSoldDiff > 3) $lastSoldCol = 'alert-over';

            if ($lastSoldDiff == 0)
                $lastSoldDiff = '&#9734;';
            if ($diff == 0)
                $diff = '&#9734;';

            $listedID = $brand . $size . $salePrice;
            if (!in_array($listedID, $listed)) {
                $td .= sprintf("<tr>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td class=\"%s\">%s</td>
                    <td class=\"%s\">%s</td>
                    <td>%s</td>
                    <td>%s</td></tr> ",
                    $upc,
                    $default_vendor_id,
                    $brand,
                    $description,
                    $size,
                    $cost,
                    $diffColor, $diff,
                    $lastSoldCol, $lastSoldDiff,
                    $normal_price,
                    $salePrice,
                );

                $listed[] = $listedID;

                $brandsA = array($brand, $id);
                $brandsR = $this->connection->execute($brandsP, $brandsA);
                while ($row = $this->connection->fetchRow($brandsR)) {
                    $curUpc = $row['upc'];
                    $otherItems[$curUpc] = 1;
                }

                $prodModelB->reset();
                foreach ($otherItems as $upc => $one) {
                    $prodModelB->upc($upc);
                    $prodModelB->load();
                    $brand = $prodModelB->brand();
                    $description = $prodModelB->description();
                    $normal_price = $prodModelB->normal_price();
                    $curSize = $prodModelB->size();
                    $sizeMatchB = preg_replace("/[^0-9]/", '', $curSize);
                    $sizeMatchB = floor($sizeMatchB);
                    $cur_default_vendor_id = $prodModelB->default_vendor_id();
                    $cost = $prodModelB->cost();
                    $inUse = $prodModelB->inUse();

                    $lastSoldR = $this->connection->execute($lastSoldP, array($upc));
                    $row = $this->connection->fetchRow($lastSoldR);
                    $last_sold = $row['last_sold'];
                    $last_sold = substr($last_sold, 0, 10);
                    
                    $prodReviewModel->reset();
                    $prodReviewModel->upc($upc);
                    $prodReviewModel->vendorID($cur_default_vendor_id);
                    $prodReviewModel->load();
                    $reviewed = $prodReviewModel->reviewed();

                    $date1 = new DateTime();
                    $date1 = $date1->format('Y-m-d');
                    $date1 = strtotime($date1);

                    $date2 = new DateTime($reviewed);
                    $date2 = $date2->format('Y-m-d');
                    $date2 = strtotime($date2);

                    $date3 = new DateTime($last_sold);
                    $date3 = $date3->format('Y-m-d');
                    $date3 = strtotime($date3);

                    $dateDiff = $date1 - $date3;
                    $diff = round($dateDiff / (60 * 60 * 24));
                    $diff = round($diff / 30, 1);

                    $dateDiff = $date1 - $date2;
                    $lastSoldDiff = round($dateDiff / (60 * 60 * 24));
                    $lastSoldDiff = round($lastSoldDiff / 30, 1);

                    $diffColor = '';
                    if ($diff <= 1) $diffColor = 'alert-success';
                    if ($diff > 1) $diffColor = 'alert-warning';
                    if ($diff > 3) $diffColor = 'alert-danger';

                    $lastSoldCol = 'white';
                    if ($lastSoldDiff < 1) $lastSoldCol = 'alert-success';
                    if ($lastSoldDiff == 0) $lastSoldCol = 'alert-info';
                    if ($lastSoldDiff > 1) $lastSoldCol = 'alert-warning';
                    if ($lastSoldDiff > 2) $lastSoldCol = 'alert-danger';
                    if ($lastSoldDiff > 3) $lastSoldCol = 'alert-over';

                    if ($lastSoldDiff == 0)
                        $lastSoldDiff = '&#9734;';
                    if ($diff == 0)
                        $diff = '&#9734;';

                    if ($sizeMatchA == $sizeMatchB) {
                        $td .= sprintf("<tr style=\"background: rgba(155,125,115,0.1); color: grey\" class=\"\">
                            <td>%s</td>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%s</td>
                            <td class=\"%s\">%s</td>
                            <td class=\"%s\">%s</td>
                            <td>%s</td>
                            <td>%s</td></tr>",
                            $upc,
                            $cur_default_vendor_id,
                            "&#8627; ".$brand,
                            $description,
                            $curSize,
                            $cost,
                            $diffColor, $diff,
                            $lastSoldCol, $lastSoldDiff,
                            $normal_price,
                            "<input type=\"text\" name=\"items[]\" style=\"border: 1px solid lightgrey; background-color: rgba(0,0,0,0);\"/>"
                        );
                    }
                    

                }
                $td .= "<tr><td colspan=10 style=\"background: linear-gradient(grey, lightgrey)\"></td></tr>";
            }



        }


        return <<<HTML
<h4>Batch $id</h4>
<div>
    <label>Legend</label>
    <div>Review & Last Sold = months since reviewed/sold.</div>
    New/In-Stock  &#8594;
    <div style="display: inline-block; width: 25px; height: 25px; border: 1px solid grey;" class="alert-info"></div>&nbsp;&#8594;
    <div style="display: inline-block; width: 25px; height: 25px; border: 1px solid grey;" class="alert-success"></div>&nbsp;&#8594;
    <div style="display: inline-block; width: 25px; height: 25px; border: 1px solid grey;" class="alert-warning"></div>&nbsp;&#8594;
    <div style="display: inline-block; width: 25px; height: 25px; border: 1px solid grey;" class="alert-danger"></div>&nbsp;&#8594;
    Old/Out-Of-Stock
</div>
<table class="table table-bordered">
    <thead>
        <tr><th>UPC</th>
        <th>VID</th>
        <th>Brand</th>
        <th>Description</th>
        <th>Size</th>
        <th>Cost</th>
        <th>Reviewed (Months)</th>
        <th>Days Since Sold</th>
        <th>Price</th>
        <th>New/Sale Price</th>
        </tr>
    </thead>
    <tbody>$td</tbody>
</table>
HTML;

    }

    public function css_content()
    {
        return <<<HTML
.alert-over {
    background: linear-gradient(#D8ABAB, #CE8C8C);
    color: darkred;
}
HTML;
    }

    public function helpContent()
    {
        return '';
    }

    public function unitTest($phpunit)
    {
        $this->id = 1;
        $phpunit->assertEquals(true, $this->get_id_paste_handler());
    }
}

FannieDispatch::conditionalExec();

