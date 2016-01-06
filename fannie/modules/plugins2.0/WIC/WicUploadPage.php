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
include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class WicUploadPage extends \COREPOS\Fannie\API\FannieUploadPage 
{
    protected $title = "Fannie :: WIC Items";
    protected $header = "Import WIC Items";

    public $description = '[WIC Items Import] uploads a list of WIC-eligible items.';
    public $themed = true;

    /**
      Default based on Co+op Deals Signage Data spreadsheets
    */

    protected $preview_opts = array(
        'upc' => array(
            'name' => 'upc',
            'display_name' => 'UPC*',
            'default' => 3,
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
            'default' => 2,
            'required' => false
        ),
    );

    private $stats = array('total'=>0);

    public function process_file($linedata, $indexes)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['WicDB']);

        $upc_index = $this->get_column_index('upc');
        $desc_index = $this->get_column_index('desc');
        $size_index = $this->get_column_index('size');
        $brand_index = $this->get_column_index('brand');

        if ($desc_index === false && $brand_index === false) {
            $this->error_details = 'Neither brand nor description provided; nothing to import!';

            return false;
        }

        $ret = true;

        $checks = (FormLib::get_form_value('checks')=='yes') ? true : false;
        $insP = $dbc->prepare('
            INSERT INTO WicItems
                (upc, brand, description, size)
            VALUES
                (?, ?, ?, ?)');
        $dbc->query('TRUNCATE TABLE WicItems');

        foreach ($linedata as $line) {
            $upc = $line[$upc_index];
            // upc cleanup
            $upc = str_replace(" ","",$upc);
            $upc = str_replace("-","",$upc);
            if (!is_numeric($upc)) continue; // skip header(s) or blank rows

            $this->stats['total']++;

            // MN published spreadhsheet is impossibly dumb
            if (strlen($upc) == 12) {
                $upc = substr($upc,0,strlen($upc)-1);
            }
            $upc = BarcodeLib::padUPC($upc);

            $brand = $brand_index !== false && isset($line[$brand_index]) ? $line[$brand_index] : '';
            $desc = $desc_index !== false && isset($line[$desc_index]) ? $line[$desc_index] : '';
            $size = $size_index !== false && isset($line[$size_index]) ? $line[$size_index] : '';

            $dbc->execute($insP, array($upc, $brand, $desc, $size));
        }

        return $ret;
    }

    public function form_content()
    {
        return '<div class="well"><legend>Instructions</legend>
        Upload a CSV or XLS file containing product UPCs, descriptions and/or brands,
        and sizes
        <br />A preview helps you to choose and map columns to the database.
        <br />The uploaded file will be deleted after the load.
        </div>';
    }

    public function preview_content()
    {
        return '<input type="checkbox" id="checks" name="checks" value="yes" checked />
                <label for="checks">Remove check digits from UPCs</label>
                ';
    }

    public function results_content()
    {
        return '<div class="alert alert-success">
            Import completed successfully<br />'
            . $this->stats['total'] . ' items imported'
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
}

FannieDispatch::conditionalExec();

