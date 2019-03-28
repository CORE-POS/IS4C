<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('RpFixedMapsModel')) {
    include(__DIR__ . '/RpFixedMapsModel.php');
}

class RpFixedMaps extends FannieRESTfulPage
{
    protected $header = 'RP Fixed Mappings';
    protected $title = 'RP Fixed Mappings';

    protected function get_id_handler()
    {
        $prep = $this->connection->prepare('
            SELECT sku, brand, description
            FROM vendorItems
            WHERE (brand LIKE ? OR description LIKE ?)
                AND vendorID=?');
        $search = '%' . FormLib::get('search') . '%';
        $res = $this->connection->execute($prep, array($search, $search, $this->id));
        $ret = array();
        while ($row = $this->connection->fetchRow($res)) {
            $ret[] = array(
                'label' => $row['brand'] . ' ' . $row['description'],
                'value' => $row['sku'],
            );
        }
        echo json_encode($ret);

        return false;
    }

    protected function post_id_handler()
    {
        $vendorID = FormLib::get('vendor');
        $sku = FormLib::get('sku');
        $model = new RpFixedMapsModel($this->connection);
        $model->likeCode($this->id);
        $exists = $model->find();
        if ($exists) {
            $exists[0]->vendorID($vendorID);
            $exists[0]->sku($sku);
            $exists[0]->save();
        } else {
            $model->likeCode($this->id);
            $model->vendorID($vendorID);
            $model->sku($sku);
            $model->save();
        }

        return true;
    }

    protected function post_id_view()
    {
        return $this->get_view();
    }

    private function currentTable()
    {
        $res = $this->connection->query("
            SELECT r.likeCode,
                l.likeCodeDesc,
                n.vendorName,
                r.sku,
                v.brand,
                v.description
            FROM RpFixedMaps AS r
                LEFT JOIN likeCodes AS l ON r.likeCode=l.likeCode
                LEFT JOIN vendorItems AS v ON r.vendorID=v.vendorID AND r.sku=v.sku
                LEFT JOIN vendors AS n ON r.vendorID=n.vendorID
        ");
        $table = '<table class="table table-bordered table-small">
            <tr><th>Like Code</th><th>Vendor</th><th>SKU</th><th>Item</th></tr>';
        while ($row = $this->connection->fetchRow($res)) {
            $table .= sprintf('<tr><td>%d %s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                $row['likeCode'], $row['likeCodeDesc'],
                $row['vendorName'], $row['sku'],
                $row['brand'] . ' ' . $row['description']
            );
        }

        $table .= '</table>';

        return $table;
    }

    protected function get_view()
    {
        $vendors = new VendorsModel($this->connection);
        $vendors = $vendors->toOptions();
        $likeCodes = new LikeCodesModel($this->connection);
        $likeCodes = $likeCodes->toOptions();
        $table = $this->currentTable();

        $this->addScript('../../../src/javascript/chosen/chosen.jquery.min.js');
        $this->addCssFile('../../../src/javascript/chosen/bootstrap-chosen.css');
        $this->addOnloadCommand("\$('select.form-control').chosen();");
        $this->addOnloadCommand("bindAuto();");

        return <<<HTML
<div class="form-inline">
<form method="post">
    <label>Vendor</label>
    <select name="vendor" id="vendor" class="form-control input-sm">{$vendors}</select>
    <label>SKU</label>
    <input type="text" id="sku" class="form-control input-sm" name="sku" />
    <label>Likecode</label>
    <select name="id" class="form-control input-sm">{$likeCodes}</select>
    <button type="submit" class="btn btn-default">Add/Update</button>
</form>
</div>
<p>
    {$table}
</p>
HTML;
    }

    protected function javascriptContent()
    {
        return <<<JAVASCRIPT
function bindAuto() {
    $('input#sku').autocomplete({
        source: function (req, callback) {
            $.ajax({
                type: 'get',
                data: 'id=' + $('#vendor').val() + '&search=' + encodeURIComponent(req.term),
                dataType: 'json'
            }).fail(function() {
                callback([]);
            }).done(function (resp) {
                callback(resp);
            });
        },
        minLength: 3
    });
}
JAVASCRIPT;
    }
}

FannieDispatch::conditionalExec();

