<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class CoolImport extends COREPOS\Fannie\API\FannieUploadPage
{
    protected $title = 'COOL Data Import';
    protected $header = 'COOL Data Import';

    protected $preview_opts = array(
        'sku' => array(
            'name' => 'sku',
            'display_name' => 'SKU',
            'default' => 0,
            'required' => false,
        ),
        'cool' => array(
            'name' => 'cool',
            'display_name' => 'COOL',
            'default' => 1,
            'required' => false,
        ),
        'item' => array(
            'name' => 'item',
            'display_name' => 'Item',
            'default' => 2,
            'required' => false,
        ),
    );

    public function process_file($linedata, $indexes)
    {
        $vendorID = FormLib::get('vendor');
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
        foreach ($linedata as $line) {
            $sku = trim($line[$indexes['sku']]);
            $cool = strtoupper(trim($line[$indexes['cool']]));
            $item = trim($line[$indexes['item']]);
            $cool = str_replace('UNITED STATES', 'USA', $cool);
            $lc = $this->connection->getValue($likeP, array($vendorID, $sku));
            $ret .= sprintf('<tr><td>%s</td><td>%s</td>
                        <td>%s<input type="hidden" name="cool[]" value="%s" /></td>
                        <td><select name="lc[]" class="form-control input-sm chosen">
                        <option value=""></option>',
                $sku, $item, $cool, $cool);
            foreach ($opts as $val => $label) {
                $ret .= sprintf('<option %s value="%d">%d %s</option>',
                    $lc == $val ? 'selected' : '', $val, $val, $label);
            }
            $ret .= '</select></td></tr>';
        }
        $ret .= '</table>
            <p><button class="btn btn-default" type="submit">Save</button></p>';
        $this->result = $ret;

        return true;
    }

    public function results_content()
    {
        $this->addScript('../../../src/javascript/chosen/chosen.jquery.min.js');
        $this->addCssFile('../../../src/javascript/chosen/bootstrap-chosen.css');
        $this->addOnloadCommand("\$('select.chosen').chosen({search_contains: true});");
        return $this->result;
    }

    public function preview_content()
    {
        return sprintf('<input type="hidden" name="vendor" value="%d" />',
            FormLib::get('vendor'));
    }

    public function form_content()
    {
        return <<<HTML
<p>
Custom importers:<br />
<a href="CpwImport.php">CPW</a><br />
<a href="RdwImport.php">RDW</a><br />
</p>
HTML;
    }

    protected function basicForm()
    {
        $form = parent::basicForm();
        $model = new VendorsModel($this->connection);
        $this->addScript('../../../src/javascript/chosen/chosen.jquery.min.js');
        $this->addCssFile('../../../src/javascript/chosen/bootstrap-chosen.css');
        $this->addOnloadCommand("\$('select.chosen').chosen();");
        $opts = $model->toOptions();
        $addOn = <<<HTML
<p>
<div class="form-group">
    <label>Vendor</label>
    <select class="form-control chosen" name="vendor">
        {$opts}
    </select>
</div>
HTML;
        return str_replace('<p>', $addOn, $form);
    }
}

FannieDispatch::conditionalExec();

