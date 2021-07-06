<?php

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class AltCouponPage extends FannieRESTfulPage
{
    protected $header = 'Coupon Alternative Barcodes';
    protected $title = 'Coupon Alternative Barcodes';

    public $description = '[Coupon Alternative Barcodes] streamlines handling coupons with multiple barcodes such that
    it doesn\'t matter which one the cashier scans';

    protected function post_handler()
    {
        $upc = BarcodeLib::padUPC(FormLib::get('upc'));
        $alt = BarcodeLib::padUPC(FormLib::get('altUPC'));
        $exp = FormLib::get('expires');

        if ($upc != '0000000000000' && $alt != '0000000000000') {
            $model = new CouponAltsModel($this->connection);
            $model->upc($upc);
            $model->altUPC($alt);
            $model->expires($exp);
            $model->save();
        }

        return 'AltCouponPage.php';
    }

    protected function get_view()
    {
        $prep = $this->connection->prepare("SELECT * FROM CouponAlts ORDER BY expires DESC, upc");
        $alts = $this->connection->getAllRows($prep);
        $table = '';
        foreach ($alts as $alt) {
            $table .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
                $alt['upc'], $alt['altUPC'], $alt['expires']);
        }

        return <<<HTML
<p>
<form method="post" action="AltCouponPage.php">
    <div class="form-group">
        <div class="input-group">
            <span class="input-group-addon">Coupon UPC</span>
            <input type="text" name="upc" class="form-control" />
        </div>
    </div>
    <div class="form-group">
        <div class="input-group">
            <span class="input-group-addon">Alternate UPC</span>
            <input type="text" name="altUPC" class="form-control" />
        </div>
    </div>
    <div class="form-group">
        <div class="input-group">
            <span class="input-group-addon">Expiration Date</span>
            <input type="text" name="expires" class="form-control date-field" />
        </div>
    </div>
    <p>
        <button type="submit" class="btn btn-default">Add Entry</button>
    </p>
</form>
</p>
<table class="table table-bordered table-striped">
    <tr><th>Coupon UPC</th><th>Alternate UPC</th><th>Expires</th></tr>
    {$table}
</table>
HTML;
    }
}

FannieDispatch::conditionalExec();

