<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op, Duluth, MN

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

class CoopDealsSignsPage extends \COREPOS\Fannie\API\FannieUploadPage 
{
    protected $title = "Fannie :: Co+op Deals Signs";
    protected $header = "Co+op Deals Signs";

    public $description = '[Co+op Deals Signs] creates a PDF of signs with the specified
    layouts using data from a Co+op Deals signage spreadsheet.';
    public $themed = true;

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
            'required' => true,
        ),
        'desc' => array(
            'name' => 'desc',
            'display_name' => 'Description',
            'default' => 1,
            'required' => true,
        ),
        'price' => array(
            'name' => 'price',
            'display_name' => 'Sale Price',
            'default' => 2,
            'required' => true,
        ),
        'size' => array(
            'name' => 'size',
            'display_name' => 'Unit Size',
            'default' => 3,
            'required' => true,
        ),
        'sku' => array(
            'name' => 'sku',
            'display_name' => 'Vendor SKU',
            'default' => 4,
            'required' => false,
        ),
    );

    public function preprocess()
    {
        if (FormLib::get('sign-layout') !== '') {
            $class = FormLib::get('sign-layout');
            $items = array();
            $prices = array();
            $upcs = FormLib::get('u', array());
            $formatted_prices = FormLib::get('p', array());
            for ($i=0; $i<count($upcs); $i++) {
                $items[] = $upcs[$i];
                $prices[$upcs[$i]] = $formatted_prices[$i];
            }
            $source_id = FormLib::get('sale-prices') == 'current' ? 2 : 3;
            var_dump(class_exists($class));
            $obj = new $class($items, '', $source_id);
            foreach ($prices as $upc => $price) {
                $obj->addOverride($upc, 'normal_price', $price);
            }
            $obj->drawPDF();

            return false;
        } else {
            return parent::preprocess();
        }
    }

    public function process_file($linedata)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $upc_index = $this->get_column_index('upc');
        $desc_index = $this->get_column_index('desc');
        $size_index = $this->get_column_index('size');
        $brand_index = $this->get_column_index('brand');
        $price_index = $this->get_column_index('price');
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
                INNER JOIN products AS p ON v.upc=p.upc AND v.vendorID=p.default_vendor_id
            WHERE v.sku = ?
        ');

        $this->sign_items = array();
        $this->formatted_prices = array();

        foreach ($linedata as $line) {
            $upc = $line[$upc_index];
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
            $model->load();
            $changed = false;
            if ($model->brand() != '' && $brand_index !== false && !empty($line[$brand_index])) {
                $brand = $line[$brand_index];
                if ($normalize) {
                    $brand = ucwords(strtolower($brand));
                }
                $model->brand($brand);
                $changed = true;
            }
            if ($model->description() != '' && $desc_index !== false && !empty($line[$desc_index])) {
                $desc = $line[$desc_index];
                if ($normalize) {
                    $desc = ucwords(strtolower($desc));
                }
                $model->description($desc);
                $changed = true;
            }
            if ($model->sizing() != '' && $size_index !== false && !empty($line[$size_index])) {
                $size = $line[$size_index];
                $model->sizing($size);
                $changed = true;
            }

            // possible that columns exist for brand and/or description
            // but are blank for the given row. No need to update.
            if ($changed) {
                $model->save();
                $this->stats['here']++;
            }

            $this->sign_items[] = $upc;
            $this->formatted_prices[$upc] = $line[$price_index];
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
                ';
    }

    public function results_content()
    {
        $ret = '<div class="alert alert-success">
            Import completed successfully<br />'
            . $this->stats['total'] . ' items examined<br />'
            . $this->stats['here'] . ' items updated<br />'
            . '</div>'
            . '<hr />' ;
        $ret .= '<form method="post">
            <div class="form-group">
                <label>Layout</label>
                <select name="sign-layout" class="form-control">
                    <option value="Signage12UpL">12 Up</option>
                    <option value="Signage16UpP">16 Up</option>
                </select>
            </div>
            <div class="form-group">
                <label>Prices</label>
                <select name="sale-prices" class="form-control">
                    <option>Upcoming</option>
                    <option>Current</option>
                </select>
            </div>
            <div class="form-group">
                <button class="btn btn-default" type="submit">Get Signs</button>
            </div>';
        foreach ($this->sign_items as $u) { 
            $ret .= '<input type="hidden" name="u[]" value="' . $u . '" />';
            $ret .= '<input type="hidden" name="p[]" value="' 
                . (isset($this->formatted_prices[$u]) ? $this->formatted_prices[$u] : '') . '" />';
        }
        $ret .= '</form>';

        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            Create signs from NCG provided Co+op Deals sign data. Importing
            one worksheet (tab) of the spreadsheet as a sign source accomplishes
            two things: first, it allows for an identical mix of 12 and 16 up
            signs since each size has its own worksheet. Second, items priced
            as multiples (e.g., 5/$5) use the same multiple as the flyer.
            </p>
            <p>The default column layout matches NCG Co+op Deal sign data
            spreadsheets.</p>'
            . parent::helpContent();
    }
}

FannieDispatch::conditionalExec();

