<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RdwImport extends FannieRESTfulPage
{
    protected $title = 'RDW COOL Data Import';
    protected $header = 'RDW COOL Data Import';

    protected function post_handler()
    {
        $this->data = array();
        $this->invoice = array();
        $invoice = FormLib::get('invoice');
        $prev = false;
        foreach (explode("\n", $invoice) as $line) {
            $hasSku = preg_match('/.* (\d\d\d\d\d) .*/', $line, $matches);
            if ($hasSku) {
                $sku = $matches[1];
                $cool = substr($line, 0, strpos($line, ' '));
                if ($cool == 'COSTA') {
                    $cool = 'COSTA RICA';
                }
                $this->data[$sku] = $cool;
                $this->invoice[$sku] = $line;
                $prev = $sku;
            } elseif ($prev) {
                $this->data[$prev] .= ' AND ' . $line;
                $this->data[$prev] = str_replace('N/A AND ', '', $this->data[$prev]);
            }
        }

        return true;
    }

    protected function post_view()
    {
        $vendorID = 136;
        $likeP = $this->connection->prepare("SELECT likeCode
            FROM VendorLikeCodeMap
            WHERE vendorID=? AND sku=?");
        $model = new LikeCodesModel($this->connection);
        $opts = array();
        foreach ($model->find() as $obj) {
            $opts[$obj->likeCode()] = $obj->likeCodeDesc()
                . ' '
                . ($obj->organic() ? '(O)' : '(C)');
        }
        $ret = '<form method="post" action="CoolImportSave.php">
            <table class="table table-bordered">';
        foreach ($this->data as $sku => $cool) {
            $item = $this->invoice[$sku];
            $lc = $this->connection->getValue($likeP, array($vendorID, $sku));
            if ($cool == 'NEW') {
                $cool = 'NEW ZEALAND';
            } elseif (is_numeric($cool)) {
                $lc = -1; // skip update if there's no valid origin
            }
            $ret .= sprintf('<tr><td>%s</td><td>%s</td>
                        <td><input type="text" name="cool[]" class="form-control input-sm" value="%s" /></td>
                        <td><select name="lc[]" class="form-control input-sm chosen">
                        <option value="">Skip item</option>',
                $sku, $item, $cool, $cool);
            foreach ($opts as $val => $label) {
                $ret .= sprintf('<option %s value="%d">%d %s</option>',
                    $lc == $val ? 'selected' : '', $val, $val, $label);
            }
            $ret .= '</select></td></tr>';
        }
        $ret .= '</table>
            <p><button class="btn btn-default" type="submit">Save</button></p>';

        $this->addScript('../../../src/javascript/chosen/chosen.jquery.min.js');
        $this->addCssFile('../../../src/javascript/chosen/bootstrap-chosen.css');
        $this->addOnloadCommand("\$('select.chosen').chosen({search_contains: true});");

        return $ret;
    }

    protected function get_view()
    {
        return <<<HTML
<form method="post">
<div class="form-group">
    <label>Copy/Paste Invoice Data</label>
    <textarea name="invoice" class="form-control" rows="20"></textarea>
</div>
<div class="form-group">
    <button type="submit" class="btn btn-default">Import</button>
</div>
</form>
HTML;
    }

}

FannieDispatch::conditionalExec();

