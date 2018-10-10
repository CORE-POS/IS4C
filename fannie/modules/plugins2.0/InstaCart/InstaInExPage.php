<?php

use COREPOS\Fannie\API\item\ItemText;
use COREPOS\Fannie\API\lib\FannieUI;

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class InstaInExPage extends FannieRESTfulPage
{
    protected $header = 'Instacart Item Adjustments';
    protected $title = 'Instacart Item Adjustments';

    protected function post_id_handler()
    {
        $plugin = $this->config->get('PLUGIN_SETTINGS');
        $table = $plugin['InstaCartDB'] . $this->connection->sep() . 'InstaExcludes';
        if ($plugin['InstaCardMode']) {
            $table = $plugin['InstaCartDB'] . $this->connection->sep() . 'InstaIncludes';
        }
        $upc = BarcodeLib::padUPC($this->id);
        $prep = $this->connection->prepare("SELECT upc FROM {$table} WHERE upc=?");
        if (!$this->connection->getValue($prep, array($upc))) {
            $ins = $this->connection->prepare("INSERT INTO $table (upc) VALUES (?)");
            $this->connection->execute($ins, array($upc));
        }

        return 'InstaInExPage.php';
    }

    protected function delete_id_handler()
    {
        $plugin = $this->config->get('PLUGIN_SETTINGS');
        $table = $plugin['InstaCartDB'] . $this->connection->sep() . 'InstaExcludes';
        if ($plugin['InstaCardMode']) {
            $table = $plugin['InstaCartDB'] . $this->connection->sep() . 'InstaIncludes';
        }
        $upc = BarcodeLib::padUPC($this->id);
        $prep = $this->connection->prepare("DELETE FROM {$table} WHERE upc=?");
        $this->connection->execute($prep, array($upc));

        return 'InstaInExPage.php';
    }

    protected function get_view()
    {
        $this->addOnloadCommand("\$('#form-in').focus();");
        $ret = <<<HTML
<form method="post" action="InstaInExPage.php">
<p>
    <div class="input-group">
        <span class="input-group-addon">UPC</span>
        <input type="text" name="id" class="form-control" id="form-in" />
        <span class="input-group-btn">
            <button type="submit" class="btn btn-default">Add</button>
        </span>
    </div>
</p>
</form>
<hr />
HTML;
        $plugin = $this->config->get('PLUGIN_SETTINGS');
        if ($plugin['InstaCardMode']) {
            $ret .= $this->itemTable($plugin['InstaCartDB'] . $this->connection->sep() . 'InstaIncludes', 'Included Items');
        } else {
            $ret .= $this->itemTable($plugin['InstaCartDB'] . $this->connection->sep() . 'InstaExcludes', 'Excluded Items');
        }

        return $ret;
    }

    private function itemTable($baseTable, $header)
    {
        $ret = '<h3>' . $header . '</h3>';
        $ret .= '<table class="table table-bordered table-striped small">
                <thead><th>UPC</th><th>Brand</th><th>Item</th><th>&nbsp;</th></thead>
                <tbody>';
        $res = $this->connection->query("
            SELECT i.upc,
                " . ItemText::longBrandSQL() . ",
                " . ItemText::longDescriptionSQL() . "
            FROM {$baseTable} AS i
                " . DTrans::joinProducts('i', 'p', 'INNER') . "
                LEFT JOIN productUser AS u ON i.upc=u.upc
            ORDER BY brand, description");
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= sprintf('<tr>
                    <td><a href="../../../item/ItemEditor.php?searchupc=%s">%s</a></td>
                    <td>%s</td><td>%s</td>
                    <td><a href="InstaInExPage.php?_method=delete&id=%s">%s</a></td>
                    </tr>',
                    $row['upc'], $row['upc'],
                    $row['brand'], $row['description'],
                    $row['upc'], FannieUI::deleteIcon()
            );
        }

        $ret .= '</tbody></table>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

