<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

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

class AdTextImportPage extends FannieUploadPage 
{
    protected $title = "Fannie :: Product Ad Text";
    protected $header = "Import Product Ad Text";

    public $description = '[Ad Text Import] uploads long brand names and product descriptions
    for use in signage. The default format is set for Co+op Deals signage spreadsheets.';

    /**
      Default based on Co+op Deals Signage Data spreadsheets
    */

    protected $preview_opts = array(
        'upc' => array(
            'name' => 'upc',
            'display_name' => 'UPC*',
            'default' => 5,
            'required' => true
        ),
        'brand' => array(
            'name' => 'brand',
            'display_name' => 'Brand',
            'default' => 0,
            'required' => false
        ),
        'desc' => array(
            'name' => 'desc',
            'display_name' => 'Description',
            'default' => 1,
            'required' => false
        ),
        'size' => array(
            'name' => 'size',
            'display_name' => 'Unit Size',
            'default' => 3,
            'required' => false
        ),
        'sku' => array(
            'name' => 'sku',
            'display_name' => 'Vendor SKU',
            'default' => 4,
            'required' => false
        ),
    );

    public function process_file($linedata)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $upc_index = $this->get_column_index('upc');
        $desc_index = $this->get_column_index('desc');
        $size_index = $this->get_column_index('size');
        $brand_index = $this->get_column_index('brand');
        $sku_index = $this->get_column_index('sku');

        if ($desc_index === false && $brand_index === false) {
            $this->error_details = 'Neither brand nor description provided; nothing to import!';

            return false;
        }

        $ret = true;

        $checks = (FormLib::get_form_value('checks')=='yes') ? true : false;
        $normalize = (FormLib::get_form_value('norm')=='yes') ? true : false;

        $model = new ProductUserModel($dbc);

        $verifyP = $dbc->prepare('
            SELECT p.upc
            FROM products AS p
            WHERE p.upc = ?
        ');

        $skuP = $dbc->prepare('
            SELECT p.upc
            FROM vendorItems AS v
                INNER JOIN products AS p ON v.upc=p.upc
            WHERE v.sku = ?
        ');

        foreach ($linedata as $line) {
            $upc = $line[$upc_index];
            // upc cleanup
            $upc = str_replace(" ","",$upc);
            $upc = str_replace("-","",$upc);
            if (!is_numeric($upc)) continue; // skip header(s) or blank rows

            if ($checks) {
                $upc = substr($upc,0,strlen($upc)-1);
            }
            $upc = BarcodeLib::padUPC($upc);

            $verifyR = $dbc->execute($verifyP, array($upc));
            if ($dbc->num_rows($verifyR) == 0) {
                if ($sku_index !== false) {
                    $skuR = $dbc->execute($skuP, array($line[$sku_index]));
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
            $changed = false;
            if ($brand_index !== false && !empty($line[$brand_index])) {
                $brand = $line[$brand_index];
                if ($normalize) {
                    $brand = ucwords(strtolower($brand));
                }
                $model->brand($brand);
                $changed = true;
            }
            if ($desc_index !== false && !empty($line[$desc_index])) {
                $desc = $line[$desc_index];
                if ($normalize) {
                    $desc = ucwords(strtolower($desc));
                }
                $model->description($desc);
                $changed = true;
            }
            if ($size_index !== false && !empty($line[$size_index])) {
                $size = $line[$size_index];
                $model->sizing($size);
                $changed = true;
            }

            // possible that columns exist for brand and/or description
            // but are blank for the given row. No need to update.
            if ($changed) {
                $model->save();
            }
        }

        return $ret;
    }

    public function form_content()
    {
        return '<fieldset><legend>Instructions</legend>
        Upload a CSV or XLS file containing product UPCs, descriptions and/or brands,
        and optionally vendor SKU numbers
        <br />A preview helps you to choose and map columns to the database.
        <br />The uploaded file will be deleted after the load.
        </fieldset><br />';
    }

    public function preview_content()
    {
        return '<input type="checkbox" id="checks" name="checks" value="yes" checked />
                <label for="checks">Remove check digits from UPCs</label>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <input type="checkbox" id="norm" name="norm" value="yes" checked />
                <label for="norm">Normalize Capitalization</label>
                ';
    }

    public function results_content()
    {
        return 'Import completed successfully' 
            . '<hr />' 
            . $this->form_content()
            . $this->basicForm();
    }
}

FannieDispatch::conditionalExec();

