<?php

/**
 * This is intended for use with some kind of in-aisle
 * device and as such is purposely lacking all menus and
 * so forth
 */

use COREPOS\Fannie\API\item\ItemText;
use COREPOS\Fannie\API\lib\Store;

include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class PriceCheckPage extends FannieRESTfulPage
{
    public function preprocess()
    {
        $ret = parent::preprocess();
        $this->window_dressing = false;

        return $ret;
    }

    protected function get_id_handler()
    {
        $upc1 = BarcodeLib::padUPC($this->id);
        $upc2 = '0' . substr($upc1, 0, 12);

        $query = "
            SELECT p.normal_price,
                p.special_price,
                p.discounttype,
                " . ItemText::longBrandSQL() . ",
                " . ItemText::longDescriptionSQL() . "
            FROM products AS p
                LEFT JOIN productUser AS u ON p.upc=u.upc
            WHERE p.store_id=?
                AND p.upc=?";
        $prep = $this->connection->prepare($query);
        $store = Store::getIdByIp();
        $row = $this->connection->getRow($prep, array($store, $upc1));
        if ($row === false) {
            $row = $this->connection->getRow($prep, array($store, $upc2));
        }

        if ($row === false) {
            echo '<div class="h2 alert alert-danger">Item not found</div>';
            return false;
        }

        $item = ($row['brand'] != '' ? $row['brand'] . ' ' : '') . $row['description'];
        switch ($row['discounttype']) {
            case 1:
                $price = sprintf('Sale Price: $%.2f', $row['special_price']);
                break;
            case 0:
                $price = sprintf('Price: $%.2f', $row['normal_price']);
                break;
        }

        echo "<div class=\"h2\">{$item}</div><div class=\"h2\">{$price}</div>";

        return false;
    }
    
    protected function get_view()
    {
        $this->addJQuery();
        $this->addBootstrap();
        $this->addScript('priceCheck.js');
        $this->addOnloadCommand("\$('#pc-upc').focus();");
        $this->addOnloadCommand("priceCheck.showDefault();");
        if (file_exists(__DIR__ . '/../../src/javascript/composer-components/bootstrap/css/bootstrap.min.css')) {
            $bootstrap = '../../src/javascript/composer-components/bootstrap/css/';
        } elseif (file_exists(__DIR__ . '/../../src/javascript/bootstrap/css/bootstrap.min.css')) {
            $bootstrap = '../../src/javascript/bootstrap/css/';
        }
        return <<<HTML
<!DOCTYPE html> 
<html>
<head>
    <link rel="stylesheet" type="text/css" href="{$bootstrap}bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="{$bootstrap}bootstrap-theme.min.css">
</head>
<body class="container">
<form method="get" id="pc-form" onsubmit="priceCheck.search(); return false;">
    <div class="form-inline">
        <input type="text" class="form-control form" name="id" id="pc-upc" autocomplete="off" />
        <button type="submit" class="btn btn-default btn-success">Search</button>
    </div>
</form>
<div id="pc-results" class="well"></div>
HTML;
    }
}

FannieDispatch::conditionalExec();

