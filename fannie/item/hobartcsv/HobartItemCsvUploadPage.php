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

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class HobartItemCsvUploadPage extends \COREPOS\Fannie\API\FannieUploadPage 
{
    protected $title = "Fannie :: Product Tools";
    protected $header = "Import Hobart CSV Items";

    public $description = '[Hobart CSV Item Import] load information about service-scale (Hobart) items from Data Gate Weigh export.';
    public $themed = true;

    protected $preview_opts = array(
        'barcode' => array(
            'name' => 'barcode',
            'display_name' => 'Barcode Segment*',
            'default' => 32,
            'required' => true,
        ),
        'desc' => array(
            'name' => 'desc',
            'display_name' => 'Description',
            'default' => 7,
            'required' => false,
        ),
        'price' => array(
            'name' => 'price',
            'display_name' => 'Price*',
            'default' => 27,
            'required' => true,
        ),
        'type' => array(
            'name' => 'type',
            'display_name' => 'Random/Fixed*',
            'default' => 6,
            'required' => true,
        ),
        'graphics' => array(
            'name' => 'graphics',
            'display_name' => 'Graphic#',
            'default' => 8,
            'required' => false,
        ),
        'label' => array(
            'name' => 'label',
            'display_name' => 'Label#*',
            'default' => 9,
            'required' => true,
        ),
        'tare' => array(
            'name' => 'tare',
            'display_name' => 'Tare',
            'default' => 34,
            'required' => false,
        ),
        'shelf' => array(
            'name' => 'shelf',
            'display_name' => 'Shelf Life',
            'default' => 44,
            'required' => false
        ),
        'net' => array(
            'name' => 'net',
            'display_name' => 'NetWt',
            'default' => 11,
            'required' => false,
        ),
    );

    protected $use_splits = false;

    function process_file($linedata, $indexes)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $model = new ScaleItemsModel($dbc);
        $ret = true;
        $this->stats = array('done' => 0, 'error' => array());
        foreach ($linedata as $line) {
            // get info from file and member-type default settings
            // if applicable
            if (!isset($line[$indexes['barcode']])) {
                continue;
            }
            $barcode_segment = $line[$indexes['barcode']];
            if (!is_numeric($barcode_segment)) {
                continue;
            }
            $upc = '002' . str_pad($barcode_segment, 5, '0', STR_PAD_LEFT) . '00000';

            $model->reset();
            $model->plu($upc);
            $model->load();

            $type = $line[$indexes['type']];
            if ($type == 'Random Weight') {
                $model->weight(0);
                $model->bycount(0);
            } elseif ($type == 'Fixed Weight') {
                $model->weight(1);
                $model->bycount(1);
            } else {
                // bad record; no weight given
                continue;
            }

            if ($indexes['desc'] !== false && isset($line[$indexes['desc']]) && !empty($line[$indexes['desc']])) {
                $desc = $line[$indexes['desc']];
                $desc = str_replace("'","",$desc);
                $desc = str_replace("\"","",$desc);
                $model->itemdesc($desc);
            }

            if ($indexes['price'] !== false && isset($line[$indexes['price']])) {
                $model->price($line[$indexes['price']]);
            }

            if ($indexes['tare'] !== false && isset($line[$indexes['tare']])) {
                $model->tare($line[$indexes['tare']]);
            }

            if ($indexes['shelf'] !== false && isset($line[$indexes['shelf']])) {
                $model->shelflife($line[$indexes['shelf']]);
            }

            if ($indexes['net'] !== false && isset($line[$indexes['net']])) {
                $model->netWeight($line[$indexes['net']]);
            }

            if ($indexes['label'] !== false && isset($line[$indexes['label']])) {
                $model->label($line[$indexes['label']]);
            }

            if ($indexes['graphics'] !== false && isset($line[$indexes['graphics']])) {
                $model->graphics($line[$indexes['graphics']]);
            }

            $try = $model->save();

            if ($try === false) {
                $ret = false;
                $this->stats['error'][] = 'There was an error importing UPC ' . $upc;
            } else {
                $this->stats['done']++;
            }
        }

        return $ret;
    }

    function form_content()
    {
        return '<div class="well"><legend>Instructions</legend>
        Upload a CSV file containing items as read from Data GateWeigh
        in Hobart CSV format.
        <br />A preview helps you to choose and map columns to the database.
        <br />The uploaded file will be deleted after the load.
        </div><br />';
    }

    function results_content()
    {
        $ret = '<p>Import Complete</p>';
        $ret .= '<div class="alert alert-success">Updated ' . $this->stats['done'] . ' items</div>';
        if (count($this->stats['error']) > 0) {
            $ret .= '<div class="alert alert-danger"><ul>';
            foreach ($this->stats['error'] as $error) {
                $ret .= '<li>' . $error . '</li>';
            }
            $ret .= '</ul></div>';
        }

        return $ret;
    }
    
    public function unitTest($phpunit)
    {
        $this->stats = array('done'=>0, 'error'=>array('foo'));
        $phpunit->assertNotEquals(0, strlen($this->results_content()));
        $data = array('21234000000','test item',1.99,'Random Weight',0,0,0.05,100,0);
        $indexes = array('barcode'=>0,'desc'=>1,'price'=>2,'type'=>3,'graphics'=>4,'label'=>5,'tare'=>6,'shelf'=>7,'net'=>8);
        $phpunit->assertEquals(true, $this->process_file(array($data), $indexes));
    }
}

FannieDispatch::conditionalExec();

