<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('FoodDudesMapModel')) {
    include(__DIR__ . '/FoodDudesMapModel.php');
}

class FdMappingPage extends FannieRESTfulPage
{
    protected $header = 'Food Dudes Item Mapping';
    protected $title = 'Food Dudes Item Mapping';

    protected function post_id_handler()
    {
        $model = new FoodDudesMapModel($this->connection);
        $upc = FormLib::get('upc');
        $sku = FormLib::get('sku');
        $trans = FormLib::get('trans');
        $this->connection->startTransaction();
        for ($i=0; $i<count($this->id); $i++) {
            $model->reset();
            if (!isset($upc[$i]) || !isset($sku[$i]) || !isset($trans[$i])) {
                continue;
            }
            if (empty($upc[$i]) || empty($sku[$i]) || empty($trans[$i])) {
                continue;
            }
            if ($this->id[$i] > 0) {
                $model->foodDudesMapID($this->id[$i]);
            }
            $model->foodDudesSKU($sku[$i]);
            $model->realUPC($upc[$i]);
            $model->transUPC($trans[$i]);
            $model->save();
        }
        $this->connection->commitTransaction();

        return 'FdMappingPage.php';
    }

    protected function get_view()
    {
        $query = "SELECT f.foodDudesSKU,
                f.realUPC,
                f.transUPC,
                a.description as realDesc,
                b.description AS transDesc,
                f.foodDudesMapID
            FROM FoodDudesMap AS f
                LEFT JOIN products AS a on f.realUPC=a.upc AND a.store_id=1
                LEFT JOIN products AS b on f.transUPC=b.upc AND b.store_id=1";
        $res = $this->connection->query($query);
        $table = '';
        while ($row = $this->connection->fetchRow($res)) {
            $table .= sprintf('<tr><td><input type="hidden" name="id[]" value="%d" />
                                <input type="text" name="sku[]" class="form-control" value="%s" /></td>
                                <td><input type="text" name="upc[]" class="form-control" value="%s" /></td>
                                <td>%s</td>
                                <td><input type="text" name="trans[]" class="form-control" value="%s" /></td>
                                <td>%s</td></tr>',
                                $row['foodDudesMapID'],
                                $row['foodDudesSKU'],
                                $row['realUPC'],
                                $row['realDesc'],
                                $row['transUPC'],
                                $row['transDesc']
            );
        }

        return <<<HTML
<form method="post">
    <table class="table table-bordered table-striped">
        <tr>
            <th>Food Dudes SKU</th>
            <th>Normal UPC</th>
            <th>Normal Desc.</th>
            <th>FD UPC</th>
            <th>FD Desc.</th>
        </tr>
        {$table}
        <tr>
            <td><input type="hidden" name="id[]" value="-1" />
            <input type="text" name="sku[]" class="form-control" value="" /></td>
            <td><input type="text" name="upc[]" class="form-control" value="" /></td>
            <td>NEW</td>
            <td><input type="text" name="trans[]" class="form-control" value="" /></td>
            <td>NEW</td>
        </tr>
    </table>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

