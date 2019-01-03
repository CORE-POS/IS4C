<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class DIPage extends FannieRESTfulPage
{

    protected $header = 'Deli Inventory';
    protected $title = 'Deli Inventory';

    public function preprocess()
    {
        $this->addRoute('get<catUp>', 'get<catDown>');

        return parent::preprocess();
    }

    protected function get_catUp_handler()
    {
        $upP = $this->connection->prepare("UPDATE DeliCategories SET seq=seq-1 WHERE deliCategoryID=?");
        $this->connection->execute($upP, array($this->catUp));
        $nameP = $this->connection->prepare('SELECT name FROM DeliCategories WHERE deliCategoryID=?');
        $name = $this->connection->getValue($nameP, array($this->catUp));
        $tag = str_replace(' ', '-', strtolower($name));

        return 'DIPage.php';
    }

    protected function get_catDown_handler()
    {
        $upP = $this->connection->prepare("UPDATE DeliCategories SET seq=seq+1 WHERE deliCategoryID=?");
        $this->connection->execute($upP, array($this->catDown));
        $nameP = $this->connection->prepare('SELECT name FROM DeliCategories WHERE deliCategoryID=?');
        $name = $this->connection->getValue($nameP, array($this->catDown));
        $tag = str_replace(' ', '-', strtolower($name));

        return 'DIPage.php';
    }

    protected function post_id_handler()
    {
        if (FormLib::get('name', false) !== false) {
            $upP = $this->connection->prepare("UPDATE deliInventoryCat SET item=? WHERE id=?");
            $this->connection->execute($upP, array(FormLib::get('name'), $this->id));
        } elseif (FormLib::get('size', false) !== false) {
            $upP = $this->connection->prepare("UPDATE deliInventoryCat SET size=? WHERE id=?");
            $this->connection->execute($upP, array(FormLib::get('size'), $this->id));
        } elseif (FormLib::get('caseSize', false) !== false) {
            $upP = $this->connection->prepare("UPDATE deliInventoryCat SET units=? WHERE id=?");
            $this->connection->execute($upP, array(FormLib::get('caseSize'), $this->id));
        } elseif (FormLib::get('cases', false) !== false) {
            $upP = $this->connection->prepare("UPDATE deliInventoryCat SET cases=? WHERE id=?");
            $this->connection->execute($upP, array(FormLib::get('cases'), $this->id));
        } elseif (FormLib::get('fractions', false) !== false) {
            $upP = $this->connection->prepare("UPDATE deliInventoryCat SET fraction=? WHERE id=?");
            $this->connection->execute($upP, array(FormLib::get('fraction'), $this->id));
        } elseif (FormLib::get('cost', false) !== false) {
            $upP = $this->connection->prepare("UPDATE deliInventoryCat SET price=? WHERE id=?");
            $this->connection->execute($upP, array(FormLib::get('price'), $this->id));
        } elseif (FormLib::get('upc', false) !== false) {
            $upP = $this->connection->prepare("UPDATE deliInventoryCat SET upc=? WHERE id=?");
            $this->connection->execute($upP, array(FormLib::get('upc'), $this->id));
        } elseif (FormLib::get('sku', false) !== false) {
            $upP = $this->connection->prepare("UPDATE deliInventoryCat SET orderno=? WHERE id=?");
            $this->connection->execute($upP, array(FormLib::get('sku'), $this->id));
        } elseif (FormLib::get('vendor', false) !== false) {
            $upP = $this->connection->prepare("UPDATE deliInventoryCat SET vendorID=? WHERE id=?");
            $this->connection->execute($upP, array(FormLib::get('vendor'), $this->id));
        }

        return false;
    }

    protected function get_view()
    {
        $storeID = 1;
        $catP = $this->connection->prepare("SELECT deliCategoryID, name FROM DeliCategories WHERE storeID=? ORDER BY seq, name");
        $itemP = $this->connection->prepare("SELECT i.*, v.vendorName
            FROM deliInventoryCat AS i
                LEFT JOIN vendors AS v ON i.vendorID=v.vendorID
            WHERE categoryID=? ORDER BY item");
        $catR = $this->connection->execute($catP, array($storeID));
        $ret = '';
        while ($catW = $this->connection->fetchRow($catR)) {
            $tag = str_replace(' ', '-', strtolower($catW['name']));
            $ret .= sprintf('<a href="#%s">%s</a><br />', $tag, $catW['name']);
        }

        $catR = $this->connection->execute($catP, array($storeID));
        while ($catW = $this->connection->fetchRow($catR)) {
            $tag = str_replace(' ', '-', strtolower($catW['name']));
            $ret .= sprintf('<a name="%s"></a>
                <h3>%s
                <a href="DIPage.php?catUp=%d"><span class="glyphicon glyphicon-arrow-up"></span></a>
                <a href="DIPage.php?catDown=%d"><span class="glyphicon glyphicon-arrow-down"></span></a>
                </h3>', $tag, $catW['name'], $catW['deliCategoryID'], $catW['deliCategoryID']);
            $ret .= '<table class="table table-bordered table-striped small">';
            $ret .= '<tr><th>Item</th><th>Size</th><th>Units/Case</th><th>Cases</th><th>#/Each</th><th>Price/Case</th>
                     <th>Total</th><th>UPC</th><th>SKU</th><th>Source</th></tr>';
            $itemR = $this->connection->execute($itemP, array($catW['deliCategoryID']));
            $sum = 0;
            while ($itemW = $this->connection->fetchRow($itemR)) {
                $total = ($itemW['cases'] * $itemW['price']) + (($itemW['fraction'] / $itemW['units']) * $itemW['price']);
                if ($total == INF) {
                    $total = 0;
                }
                $ret .= sprintf('<tr data-item-id="%d">
                    <td class="name editable">%s</td>
                    <td class="size editable">%s</td>
                    <td class="caseSize editable">%d</td>
                    <td class="cases editable">%.2f</td>
                    <td class="fractions editable">%.2f</td>
                    <td class="cost editable">$%.2f</td>
                    <td class="total">$%.2f</td>
                    <td class="upc editable">%s</td>
                    <td class="sku editable">%s</td>
                    <td class="vendor">%s</td>
                    </tr>',
                    $itemW['id'],
                    $itemW['item'],
                    $itemW['size'],
                    $itemW['units'],
                    $itemW['cases'],
                    $itemW['fraction'],
                    $itemW['price'],
                    $total,
                    $itemW['upc'],
                    $itemW['orderno'],
                    $itemW['vendorName']
                );
                $sum += $total;
            }
            $ret .= sprintf('<tr><th colspan="6">Grand Total</th><th>$%.2f</th><th colspan="3"></tr>', $sum);
            $ret .= '</table>';
        }

        $vendR = $this->connection->query("SELECT vendorID, vendorName FROM vendors WHERE inactive=0 ORDER BY vendorName");
        $vendors = array();
        while ($row = $this->connection->fetchRow($vendR)) {
            $vendors[] = array(
                'id' => $row['vendorID'],
                'name'=> $row['vendorName'],
            );
        }
        $vendors = json_encode($vendors);

        $this->addScript('../../../src/javascript/chosen/chosen.jquery.min.js');
        $this->addCssFile('../../../src/javascript/chosen/bootstrap-chosen.css');
        $this->addScript('di.js');
        $this->addOnloadCommand('di.initRows();');
        $this->addOnloadCommand("di.setVendors({$vendors});");

        return $ret;
    }
}

FannieDispatch::conditionalExec();

