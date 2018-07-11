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
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class EWicImportPage extends \COREPOS\Fannie\API\FannieUploadPage 
{
    protected $title = "Fannie :: EWIC Import";
    protected $header = "Import EWIC Data";

    public $description = '[EWIC Data Import] loads state APL (approved product list) data';

    /**
      Default based on Co+op Deals Signage Data spreadsheets
    */

    protected $preview_opts = array(
        'upc' => array(
            'display_name' => 'UPC*',
            'default' => 0,
            'required' => true
        ),
        'cat' => array(
            'display_name' => 'Category ID',
            'default' => 2,
        ),
        'catName' => array(
            'display_name' => 'Category Name',
            'default' => 3,
        ),
        'sub' => array(
            'display_name' => 'Sub Category ID',
            'default' => 4,
        ),
        'subName' => array(
            'display_name' => 'Sub Category Namek',
            'default' => 5,
        ),
    );

    private $stats = array('total'=>0, 'cats'=>0, 'subs'=>0);

    public function process_file($linedata, $indexes)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $catP = $dbc->prepare('SELECT name FROM EWicCategories WHERE eWicCategoryID=?');
        $addCat = $dbc->prepare('INSERT INTO EWicCategories (eWicCategoryID, name) VALUES (?, ?)');
        $subP = $dbc->prepare('SELECT name FROM EWicSubCategories WHERE eWicCategoryID=? AND eWicCategoryID=?');
        $addSub = $dbc->prepare('INSERT INTO EWicSubCategories (eWicSubCategoryID, eWicCategoryID, name) VALUES (?, ?, ?)');

        $addItem = $dbc->prepare('INSERT INTO EWicItems (upc, upcCheck, eWicCategoryID, eWicSubCategoryID) VALUES (?, ?, ?, ?)');

        $dbc->query('TRUNCATE TABLE EWicItems');
        $dbc->startTransaction();
        foreach ($linedata as $line) {
            $upc = $line[$indexes['upc']];
            // upc cleanup
            $upc = str_replace(" ","",$upc);
            $upc = str_replace("-","",$upc);
            if (!is_numeric($upc)) continue; // skip header(s) or blank rows

            $ourUPC = BarcodeLib::padUPC(substr($upc, 0, strlen($upc)-1));

            $this->stats['total']++;

            $cat = $line[$indexes['cat']];
            $exists = $dbc->getValue($catP, array($cat));
            if (!$exists) {
                $name = $line[$indexes['catName']];
                $dbc->execute($addCat, array($cat, $name));
                $this->stats['cats']++;
                $dbc->commitTransaction();
                $dbc->startTransaction();
            }
            $sub = $line[$indexes['sub']];
            $exists = $dbc->getValue($subP, array($sub, $cat));
            if (!$exists) {
                $name = $line[$indexes['subName']];
                $dbc->execute($addSub, array($sub, $cat, $name));
                $this->stats['subs']++;
                $dbc->commitTransaction();
                $dbc->startTransaction();
            }

            $dbc->execute($addItem, array($ourUPC, $upc, $cat, $sub));
        }
        $dbc->commitTransaction();

        return $ret;
    }

    public function form_content()
    {
        return '<div class="well"><legend>Instructions</legend>
        Upload a CSV or XLS file containing product UPCs and categories.
        <br />A preview helps you to choose and map columns to the database.
        <br />The uploaded file will be deleted after the load.
        </div>';
    }

    public function results_content()
    {
        return '<div class="alert alert-success">
            Import completed successfully<br />'
            . $this->stats['total'] . ' items imported<br />'
            . $this->stats['cats'] . ' categories imported<br />'
            . $this->stats['subs'] . ' sub categories imported<br />'
            . '</div>'
            . '<hr />' 
            . $this->form_content()
            . $this->basicForm();
    }
}

FannieDispatch::conditionalExec();

