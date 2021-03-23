<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('NcgCouponsModel')) {
    include(__DIR__ . '/NcgCouponsModel.php');
}
if (!class_exists('NcgCouponItemsModel')) {
    include(__DIR__ . '/NcgCouponItemsModel.php');
}

class NcgCouponEditor extends FannieRESTfulPage
{
    protected $header = 'NCG Coupons';
    protected $title = 'NCG Coupons';

    protected function post_id_handler()
    {
        if (trim($this->id) != '') {
            $items = new NcgCouponItemsModel($this->connection);
            $items->couponUPC($this->id);
            $items->itemUPC(BarcodeLib::padUPC(FormLib::get('upc')));
            $items->save();
        }

        return 'NcgCouponEditor.php?id=' . $this->id;
    }

    protected function post_handler()
    {
        $upcs = FormLib::get('upc');
        $descs = FormLib::get('desc');
        $starts = FormLib::get('start');
        $ends = FormLib::get('end');
        for ($i=0; $i<count($upcs); $i++) {
            $upc = trim($upcs[$i]);
            if ($upc == '') {
                continue;
            }
            $upc = BarcodeLib::padUPC($upc);
            $model = new NcgCouponsModel($this->connection);
            $model->couponUPC($upc);
            $model->description($descs[$i]);
            $model->startDate($starts[$i]);
            $model->endDate($ends[$i]);
            $model->save();
        }

        return 'NcgCouponEditor.php';
    }

    protected function get_id_view()
    {
        $model = new NcgCouponsModel($this->connection);
        $model->couponUPC($this->id);
        $model->load();
        $ret = '<h3>' . $model->couponUPC() . '</h3>';
        $ret .= '<h3>' . $model->description() . '</h3>';
        $ret .= '<p>' . $model->startDate() . ' ' . $model->endDate() . '</p>';
        $ret .= '<form method="post">';
        $ret .= sprintf('<input type="hidden" name="id" value="%s" />', $this->id);
        $ret .= '<table class="table table-bordered">';
        $prep = $this->connection->prepare("
            SELECT n.itemUPC, p.brand, p.description
            FROM NcgCouponItems AS n
                LEFT JOIN products AS p ON n.itemUPC=p.upc AND p.store_id=1
            WHERE n.couponUPC=?");
        $res = $this->connection->execute($prep, array($this->id));
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= sprintf('<tr><td>%s</td><td>%s %s</td></tr>',
                $row['itemUPC'], $row['brand'], $row['description']);
        }
        $ret .= '<tr><td><input type="text" name="upc" class="form-control" 
                    placeholder="Add UPC" /></td><td>&nbsp;</td></tr>';
        $ret .= '</table>';
        $ret .= '<p><button type="submit" class="btn btn-default">Add Item</button>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="NcgCouponEditor.php" class="btn btn-default">Home</a></p>';
        $ret .= '</form>';

        return $ret;
    }

    protected function get_view()
    {
        $res = $this->connection->query('SELECT couponUPC, description, startDate, endDate FROM NcgCoupons ORDER BY startDate');
        $table = '';
        while ($row = $this->connection->fetchRow($res)) {
            $table .= sprintf('<tr>
                <td><input type="text" name="upc[]" value="%s" class="form-control" /></td>
                <td><input type="text" name="desc[]" value="%s" class="form-control" /></td>
                <td><input type="text" name="start[]" value="%s" class="form-control date-field" /></td>
                <td><input type="text" name="end[]" value="%s" class="form-control date-field" /></td>
                <td><a href="NcgCouponEditor.php?id=%s">Adjust Items</a></td>
                <td><a href="NcgCouponReport.php?id=%s">Usage Report</a></td>
                </tr>',
                $row['couponUPC'], $row['description'], $row['startDate'], $row['endDate'], $row['couponUPC'], $row['couponUPC']
            );
        }

        return <<<HTML
<p>
<form method="post" action="NcgCouponEditor.php">
<table class="table table-bordered table-striped">
<tr><th>UPC</th><th>Description</th><th>Starts</th><th>Ends</th></tr>
{$table}
<tr>
    <td><input type="text" name="upc[]" value="" class="form-control" /></td>
    <td><input type="text" name="desc[]" value="" class="form-control" /></td>
    <td><input type="text" name="start[]" value="" class="form-control date-field" /></td>
    <td><input type="text" name="end[]" value="" class="form-control date-field" /></td>
    <td>&nbsp;</td>
</tr>
</table>
<div class="form-group">
    <button type="submit" class="btn btn-default">Update</button>
</div>
</form>
</p>
HTML;
    }
}

FannieDispatch::conditionalExec();

