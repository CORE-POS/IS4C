<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class CpwImport extends FannieRESTfulPage
{
    protected $title = 'CPW COOL Data Import';
    protected $header = 'CPW COOL Data Import';

    protected $COOL_MAP = array(
        'LOCAL' => 'USA',
        'WA' => 'USA',
        'OR' => 'USA',
        'CA' => 'USA',
        'AZ' => 'USA',
        'NM' => 'USA',
        'NV' => 'USA',
        'ID' => 'USA',
        'MT' => 'USA',
        'CO' => 'USA',
        'UT' => 'USA',
        'WY' => 'USA',
        'TX' => 'USA',
        'OK' => 'USA',
        'KS' => 'USA',
        'NE' => 'USA',
        'SD' => 'USA',
        'ND' => 'USA',
        'MN' => 'USA',
        'WI' => 'USA',
        'IA' => 'USA',
        'MO' => 'USA',
        'AR' => 'USA',
        'LA' => 'USA',
        'MS' => 'USA',
        'AL' => 'USA',
        'GA' => 'USA',
        'FL' => 'USA',
        'SC' => 'USA',
        'NC' => 'USA',
        'TN' => 'USA',
        'KY' => 'USA',
        'IN' => 'USA',
        'IL' => 'USA',
        'MI' => 'USA',
        'OH' => 'USA',
        'WV' => 'USA',
        'VA' => 'USA',
        'MD' => 'USA',
        'PA' => 'USA',
        'NY' => 'USA',
        'DE' => 'USA',
        'NJ' => 'USA',
        'MA' => 'USA',
        'NH' => 'USA',
        'VT' => 'USA',
        'RI' => 'USA',
        'ME' => 'USA',
        'AK' => 'USA',
        'HI' => 'USA',
        'MX' => 'MEXICO',
        'ARG' => 'ARGENTINA',
        'NZ' => 'NEW ZEALAND',
        'THAI' => 'THAILAND',
        'PERU' => 'PERU',
        'CH' => 'CHILE',
        'CHILE' => 'CHILE',
        'ECUADOR' => 'ECUADOR',
        'JAMAICA' => 'JAMAICA',
        'CAN' => 'CANADA',
    );

    protected function expandCOOL($str)
    {
        return $this->COOL_MAP[$str] ? $this->COOL_MAP[$str] : $str;
    }

    protected function findCOOL($str)
    {
        if (preg_match('/[A-Z]+\/[A-Z\/]+/', $str, $matches)) {
            $origins = array();
            $all = explode('/', $matches[0]);
            foreach ($all as $a) {
                $exp = $this->expandCOOL($a);
                if (!isset($origins[$exp])) {
                    $origins[$exp] = $exp;
                }
            }
            $vals = array_values($origins);
            sort($vals);

            return implode(' AND ', $vals);
        }

        foreach ($this->COOL_MAP as $abbrev => $full) {
            $len = strlen($abbrev) + 1;
            if (substr($str, -1*$len) == ' ' . $abbrev) {
                return $full;
            }
        }
        foreach ($this->COOL_MAP as $abbrev => $full) {
            if (strpos($str, ' '. $abbrev . ' ')) {
                return $full;
            }
        }

        return '';
    }

    protected function post_handler()
    {
        $this->data = array();
        $this->invoice = array();
        $invoice = FormLib::get('invoice');
        $prev = false;
        foreach (explode("\n", $invoice) as $line) {
            $data = explode("\t", $line);
            $sku = isset($data[5]) ? $data[5] : 0;
            $sku = str_replace('#', '', $sku);
            if (!is_numeric($sku)) {
                continue;
            }
            $item = isset($data[2]) ? $data[2] : '';
            $cool = $this->findCOOL($item);
            $this->data[$sku] = $cool;
            $this->invoice[$sku] = $item;
        }

        return true;
    }

    protected function post_view()
    {
        $vendorID = 293;
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

