<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class AdTextImportPage extends \COREPOS\Fannie\API\FannieUploadPage 
{
    protected $title = "Fannie :: Product Ad Text";
    protected $header = "Import Product Ad Text";

    public $description = '[Ad Text Import] uploads long brand names and product descriptions
    for use in signage. The default format is set for Co+op Deals signage spreadsheets.';
    public $themed = true;

    /**
      Default based on Co+op Deals Signage Data spreadsheets
    */

    protected $preview_opts = array(
        'upc' => array(
            'display_name' => 'UPC*',
            'default' => 5,
            'required' => true
        ),
        'brand' => array(
            'display_name' => 'Brand',
            'default' => 0,
        ),
        'desc' => array(
            'display_name' => 'Description',
            'default' => 1,
        ),
        'size' => array(
            'display_name' => 'Unit Size',
            'default' => 3,
        ),
        'sku' => array(
            'display_name' => 'Vendor SKU',
            'default' => 4,
        ),
    );

    private $stats = array('total'=>0, 'here'=>0);

    public function process_file($linedata, $indexes)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        if ($indexes['desc'] === false && $indexes['brand'] === false) {
            $this->error_details = 'Neither brand nor description provided; nothing to import!';

            return false;
        }

        $ret = true;

        $checks = (FormLib::get('checks')=='yes') ? true : false;
        $normalize = (FormLib::get('norm')=='yes') ? true : false;
        $overwrite = (FormLib::get('overwrite')=='yes') ? true : false;

        $model = new ProductUserModel($dbc);

        $verifyP = $dbc->prepare('
            SELECT p.upc
            FROM products AS p
            WHERE p.upc = ?
        ');

        $skuP = $dbc->prepare('
            SELECT p.upc
            FROM vendorItems AS v
                INNER JOIN products AS p ON v.upc=p.upc AND p.default_vendor_id=v.vendorID
            WHERE v.sku = ?
        ');

        foreach ($linedata as $line) {
            $upc = $line[$indexes['upc']];
            // upc cleanup
            $upc = str_replace(" ","",$upc);
            $upc = str_replace("-","",$upc);
            if (!is_numeric($upc)) continue; // skip header(s) or blank rows

            $this->stats['total']++;

            if ($checks) {
                $upc = substr($upc,0,strlen($upc)-1);
            }
            $upc = BarcodeLib::padUPC($upc);

            $verifyR = $dbc->execute($verifyP, array($upc));
            if ($dbc->num_rows($verifyR) == 0) {
                if ($indexes['sku'] !== false) {
                    $skuR = $dbc->execute($skuP, array($line[$indexes['sku']]));
                    if ($dbc->num_rows($skuR) == 0) {
                        // no UPC match, no vendor sku match
                        continue;
                    } else {
                        $skuW = $dbc->fetch_row($skuR);
                        $upc = $skuW['upc'];
                    }
                } else {
                    // item does not exist, no vendor sku provided
                    continue;
                }
            }

            $model->reset();
            $model->upc($upc);
            $model->load();
            $changed = false;
            if (($model->brand() == '' || $overwrite) && $indexes['brand'] !== false && !empty($line[$indexes['brand']])) {
                $brand = $line[$indexes['brand']];
                if ($normalize) {
                    $brand = ucwords(strtolower($brand));
                }
                $model->brand($brand);
                $changed = true;
            }
            if (($model->description() == '' || $overwrite) && $indexes['desc'] !== false && !empty($line[$indexes['desc']])) {
                $desc = $line[$indexes['desc']];
                if ($normalize) {
                    $desc = ucwords(strtolower($desc));
                }
                $model->description($desc);
                $changed = true;
            }
            if (($model->sizing() == '' || $overwrite) && $indexes['size'] !== false && !empty($line[$indexes['size']])) {
                $size = $line[$indexes['size']];
                $model->sizing($size);
                $changed = true;
            }

            // possible that columns exist for brand and/or description
            // but are blank for the given row. No need to update.
            if ($changed) {
                $model->save();
                $this->stats['here']++;
            }
        }

        return $ret;
    }

    public function form_content()
    {
        return '<div class="well"><legend>Instructions</legend>
        Upload a CSV or XLS file containing product UPCs, descriptions and/or brands,
        and optionally vendor SKU numbers
        <br />A preview helps you to choose and map columns to the database.
        <br />The uploaded file will be deleted after the load.
        </div>';
    }

    public function preview_content()
    {
        return '<input type="checkbox" id="checks" name="checks" value="yes" checked />
                <label for="checks">Remove check digits from UPCs</label>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <input type="checkbox" id="norm" name="norm" value="yes" checked />
                <label for="norm">Normalize Capitalization</label>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <input type="checkbox" id="overwrite" name="overwrite" value="yes" />
                <label for="norm">Overwrite Existing Values</label>
                ';
    }

    public function results_content()
    {
        return '<div class="alert alert-success">
            Import completed successfully<br />'
            . $this->stats['total'] . ' items examined<br />'
            . $this->stats['here'] . ' items updated<br />'
            . '</div>'
            . '<hr />' 
            . $this->form_content()
            . $this->basicForm();
    }

    public function helpContent()
    {
        return '<p>Import alternate brands and descriptions for products.
            These are typically more verbose versions. Often the full 
            name of an item will not fit well on a receipt but it\'s still
            useful to have the full name in the system for things like 
            sale signs.</p>
            <p>The default column layout matches NCGA Co+op Deal sign data
            spreadsheets.</p>'
            . parent::helpContent();
    }

    public function unitTest($phpunit)
    {
        $this->stats = array('total'=>0, 'here'=>0);
        $phpunit->assertNotEquals(0, strlen($this->results_content()));
        $data = array('4011', 'Nature', 'Bananas', 'per LB', '4011');
        $indexes = array('upc'=>0, 'brand'=>1, 'desc'=>2, 'size'=>3, 'sku'=>4);
        $phpunit->assertEquals(true, $this->process_file(array($data), $indexes));
    }
}

FannieDispatch::conditionalExec();

