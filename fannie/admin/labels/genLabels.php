<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    require_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class genLabels extends FannieRESTfulPage
{
    public $discoverable = false;

    /** wrapper since param "id" already in use **/
    protected function get_id_handler()
    {
        return $this->get_handler();
    }

    protected function get_handler()
    {
        $layout = FormLib::get('layout',$this->config->get('DEFAULT_PDF'));
        $layout = str_replace(" ","_",$layout);
        $offset = FormLib::get('offset', 0);
        $data = array();

        $tagID = FormLib::get('id', false);
        $batchID = FormLib::get('batchID', false);

        $dbc = FannieDB::getReadOnly($this->config->get('OP_DB'));
        if ($tagID !== false) {
            $data = $this->dataFromTags($dbc, $tagID);
        } elseif ($batchID !== false) {
            $data = $this->dataFromBatches($dbc, $batchID);
        }

        if (!defined('FPDF_FONTPATH')) {
          define('FPDF_FONTPATH','font/');
        }
        if (!class_exists('FPDF', false)) {
            require(dirname(__FILE__) . '/../../src/fpdf/fpdf.php');
        }
        if (!class_exists('FpdfWithBarcode', false)) {
            include(dirname(__FILE__) . '/FpdfWithBarcode.php');
        }

        $layout_file = dirname(__FILE__) . '/pdf_layouts/' . $layout . '.php';
        if (count($data) > 0 && file_exists($layout_file) && !function_exists($layout)) {
            include($layout_file);
        }
        if (count($data) > 0 && function_existS($layout)) {
            $layout($data,$offset);
        } else {
            echo 'Invalid data and/or layout';
        }
        
        return false;
    }

    private function dataFromTags($dbc, $tagID)
    {
        if (!is_array($tagID)) {
            $tagID = array($tagID);
        }
        
        list($inStr, $args) = $dbc->safeInClause($tagID);
        $query = "
            SELECT s.*,
                p.scale,
                p.numflag
            FROM shelftags AS s
                " . DTrans::joinProducts('s', 'p', 'INNER') . "
            WHERE s.id IN ($inStr) ";
        switch (strtolower(FormLib::get('sort'))) {
            case 'order entered':
                $query .= ' ORDER BY shelftagID';
                break;
            case 'alphabetically':
                $query .= ' ORDER BY s.description';
                break;
            case 'department':
            default:
                $query .= ' ORDER BY p.department, s.upc';
                break;
        }
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $args);

        $data = array();
        while ($row = $dbc->fetchRow($result)) {
            $count = 1;
            // add multiples of the give tag if requested
            if (isset($row['count']) && $row['count'] > 0) {
                $count = $row['count'];
            }
            for ($i=0; $i<$count; $i++) {
                if ($row['sku'] == $row['upc']) {
                    $row['sku'] = '';
                } elseif (strlen($row['sku']) > 7) {
                    $row['sku'] = ltrim($row['sku'], '0');
                }
                $myrow = array(
                    'normal_price' => $row['normal_price'],
                    'description' => $row['description'],
                    'brand' => $row['brand'],
                    'units' => $row['units'],
                    'size' => $row['size'],
                    'sku' => $row['sku'],
                    'pricePerUnit' => $row['pricePerUnit'],
                    'upc' => $row['upc'],
                    'vendor' => $row['vendor'],
                    'scale' => $row['scale'],
                    'numflag' => $row['numflag']
                );          
                $data[] = $myrow;
            }
        }

        return $data;
    }

    private function dataFromBatches($dbc, $batchID)
    {
        if (!is_array($batchID)) {
            $batchID = array($batchID);
        }
        list($batchIDList, $args) = $dbc->safeInClause($batchID);
        
        $testQ = $dbc->prepare("select b.*,p.scale,p.numflag
            FROM batchBarcodes as b 
                " . DTrans::joinProducts('b', 'p', 'INNER') . "
            WHERE b.batchID in ($batchIDList) and b.description <> ''
            ORDER BY b.batchID");
        $result = $dbc->execute($testQ,$args);
        $data = array();
        while ($row = $dbc->fetchRow($result)) {
            if ($row['sku'] == $row['upc']) {
                $row['sku'] = '';
            } elseif (strlen($row['sku']) > 7) {
                $row['sku'] = ltrim($row['sku'], '0');
            }
            $myrow = array(
            'normal_price' => $row['normal_price'],
            'description' => $row['description'],
            'brand' => $row['brand'],
            'units' => $row['units'],
            'size' => $row['size'],
            'sku' => $row['sku'],
            'pricePerUnit' => COREPOS\Fannie\API\lib\PriceLib::pricePerUnit($row['normal_price'], $row['size']),
            'upc' => $row['upc'],
            'vendor' => $row['vendor'],
            'scale' => $row['scale'],
            'numflag' => $row['numflag']
            );          
            $data[] = $myrow;
        }

        return $data;
    }

    public function unitTest($phpunit)
    {
        ob_start();
        $phpunit->assertEquals(false, $this->get_id_handler());
        ob_end_clean();
        $phpunit->assertInternalType('array', $this->dataFromTags($this->connection, 1));
        $phpunit->assertInternalType('array', $this->dataFromBatches($this->connection, 1));
    }
}

FannieDispatch::conditionalExec();

