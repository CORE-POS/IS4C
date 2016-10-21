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

        $model = new ScaleItemsModel($dbc);
        $ret = true;
        $this->stats = array('done' => 0, 'errors' => array());
        foreach($linedata as $line) {
            // get info from file and member-type default settings
            // if applicable
            $upc = $line[$indexes['upc']];

            // upc cleanup
            $upc = str_replace(" ","",$upc);
            $upc = str_replace("-","",$upc);
            if (!is_numeric($upc)) continue; // skip header(s) or blank rows

            $upc = BarcodeLib::padUPC($upc);

            $model->reset();
            $model->plu($upc);
            $model->load();

            if ($this->checkIndex($indexes['type'], $line)) {
                if (strtoupper($line[$indexes['type']]) == 'FIXED' || strtoupper($line[$indexes['type']]) == 'EA') {
                    $model->weight(1);
                    $model->bycount(1);
                } else if (strtoupper($line[$indexes['type']]) == 'RANDOM' || strtoupper($line[$indexes['type']]) == 'RAND') {
                    $model->weight(0);
                    $model->bycount(0);
                }
            }
            if ($this->checkIndex($indexes['desc'], $line) && !empty($line[$indexes['desc']])) {
                $desc = str_replace("'","",$line[$indexes['desc']]);
                $desc = str_replace("\"","",$desc);
                $model->itemdesc($desc);
            }
            if ($this->checkIndex($indexes['price'], $line)) {
                $model->price($line[$indexes['price']]);
            }
            if ($this->checkIndex($indexes['tare'], $line)) {
                $model->tare($line[$indexes['tare']]);
            }
            if ($this->checkIndex($indexes['shelf'], $line)) {
                $model->shelflife($line[$indexes['shelf']]);
            }
            if ($this->checkIndex($indexes['net'], $line)) {
                $model->netWeight($line[$indexes['net']]);
            }
            if ($this->checkIndex($indexes['text'], $line) && !empty($line[$indexes['text']])) {
                $model->text($line[$indexes['text']]);
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

