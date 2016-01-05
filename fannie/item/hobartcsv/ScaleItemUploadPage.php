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

class ScaleItemUploadPage extends \COREPOS\Fannie\API\FannieUploadPage 
{
    protected $title = "Fannie :: Product Tools";
    protected $header = "Import Scale Items";

    public $description = '[Scale Item Import] load information about service-scale (Hobart) items.';
    public $themed = true;

    protected $preview_opts = array(
        'upc' => array(
            'name' => 'upc',
            'display_name' => 'UPC*',
            'default' => 0,
            'required' => true
        ),
        'desc' => array(
            'name' => 'desc',
            'display_name' => 'Description',
            'default' => 1,
            'required' => false
        ),
        'price' => array(
            'name' => 'price',
            'display_name' => 'Price',
            'default' => 2,
            'required' => false
        ),
        'type' => array(
            'name' => 'type',
            'display_name' => 'Random/Fixed',
            'default' => 3,
            'required' => false
        ),
        'tare' => array(
            'name' => 'tare',
            'display_name' => 'Tare',
            'default' => 3,
            'required' => false
        ),
        'shelf' => array(
            'name' => 'shelf',
            'display_name' => 'Shelf Life',
            'default' => 3,
            'required' => false
        ),
        'net' => array(
            'name' => 'net',
            'display_name' => 'NetWt',
            'default' => 3,
            'required' => false
        ),
        'text' => array(
            'name' => 'text',
            'display_name' => 'Text',
            'default' => 3,
            'required' => false
        ),
    );

    protected $use_splits = true;

    function process_file($linedata, $indexes)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $upc_index = $this->get_column_index('upc');
        $desc_index = $this->get_column_index('desc');
        $price_index = $this->get_column_index('price');
        $type_index = $this->get_column_index('type');
        $tare_index = $this->get_column_index('tare');
        $shelf_index = $this->get_column_index('shelf');
        $net_index = $this->get_column_index('net');
        $text_index = $this->get_column_index('text');

        $model = new ScaleItemsModel($dbc);
        $ret = true;
        $this->stats = array('done' => 0, 'errors' => array());
        foreach($linedata as $line) {
            // get info from file and member-type default settings
            // if applicable
            $upc = $line[$upc_index];

            // upc cleanup
            $upc = str_replace(" ","",$upc);
            $upc = str_replace("-","",$upc);
            if (!is_numeric($upc)) continue; // skip header(s) or blank rows

            $upc = BarcodeLib::padUPC($upc);

            $model->reset();
            $model->plu($upc);
            $model->load();

            if ($this->checkIndex($type_index, $line)) {
                if (strtoupper($line[$type_index]) == 'FIXED' || strtoupper($line[$type_index]) == 'EA') {
                    $model->weight(1);
                    $model->bycount(1);
                } else if (strtoupper($line[$type_index]) == 'RANDOM' || strtoupper($line[$type_index]) == 'RAND') {
                    $model->weight(0);
                    $model->bycount(0);
                }
            }
            if ($this->checkIndex($desc_index, $line) && !empty($line[$desc_index])) {
                $desc = str_replace("'","",$line[$desc_index]);
                $desc = str_replace("\"","",$desc);
                $model->itemdesc($desc);
            }
            if ($this->checkIndex($price_index, $line)) {
                $model->price($line[$price_index]);
            }
            if ($this->checkIndex($tare_index, $line)) {
                $model->tare($line[$tare_index]);
            }
            if ($this->checkIndex($shelf_index, $line)) {
                $model->shelflife($line[$shelf_index]);
            }
            if ($this->checkIndex($net_index, $line)) {
                $model->netWeight($line[$net_index]);
            }
            if ($this->checkIndex($text_index, $line) && !empty($line[$text_index])) {
                $model->text($line[$text_index]);
            }

            $try = $model->save();

            if ($try === false) {
                $ret = false;
                $this->stats['errors'][] = 'There was an error importing UPC ' . $upc;
            } else {
                $this->stats['done']++;
            }
        }

        return $ret;
    }

    function form_content()
    {
        return '<div class="well"><legend>Instructions</legend>
        Upload a CSV or XLS file containing product UPCs plus descriptions, prices,
        tare weights, net weights, shelf lives, and/or ingredients/text.
        <br />A preview helps you to choose and map columns to the database.
        <br />The uploaded file will be deleted after the load.
        </div><br />';
    }

    function results_content()
    {
        return $this->simpleStats($this->stats, 'done');
    }
}

FannieDispatch::conditionalExec();

