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

class CoolItemUploadPage extends \COREPOS\Fannie\API\FannieUploadPage 
{
    protected $title = "Fannie :: Product Tools";
    protected $header = "Import Scale Item Price & COOL";

    public $description = '[Scale Item Price & COOL] update service scale items\' price and/or country of origin.';

    protected $preview_opts = array(
        'upc' => array(
            'display_name' => 'UPC*',
            'default' => 0,
            'required' => true
        ),
        'price' => array(
            'display_name' => 'Price*',
            'default' => 1,
            'required' => true
        ),
        'cool' => array(
            'display_name' => 'COOL*',
            'default' => 2,
            'required' => true
        ),
    );

    function process_file($linedata, $indexes)
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $itemP = $dbc->prepare('
            SELECT s.itemdesc,
                p.description,
                s.weight,
                s.text,
                s.mosaStatement,
                s.originText
            FROM scaleItems AS s
                LEFT JOIN products AS p ON s.plu=p.upc
            WHERE plu=?');
        $saveP = $dbc->prepare('
            UPDATE scaleItems
            SET price=?,
                originText=?,
                modified=' . $dbc->now() . '
            WHERE plu=?');
        $product = new ProductsModel($dbc);
        $prodPricing = FormLib::get('prodPricing') === '' ? false : true;
        $scale_items = array();
        foreach ($linedata as $line) {
            $upc = trim($line[$indexes['upc']]);
            $upc = BarcodeLib::padUPC($upc);
            $price = str_replace('$', '', $line[$indexes['price']]);
            $price = trim($price);
            $cool = $line[$indexes['cool']];
            if (!is_numeric($upc) || !is_numeric($price)) {
                continue;
            }
            $item = $dbc->getRow($itemP, array($upc));
            if ($item === false) {
                continue;
            }
            $itemdesc = !empty($item['itemdesc']) ? $item['itemdesc'] : $item['description'];
            $dbc->execute($saveP, array($price, $cool, $upc));
            if ($prodPricing) {
                $product->upc($upc);
                foreach ($product->find() as $obj) {
                    $obj->normal_price($price);
                    $obj->save();
                }
            }

            $scale_info = array(
                'RecordType' => 'ChangeOneItem',
                'PLU' => substr($upc, 3, 4),
                'Description' => $itemdesc,
                'Price' => $price,
                'Type' => $item['weight'] == 0 ? 'Random Weight' : 'Fixed Weight',
                'ReportingClass' => 1,
                'ExpandedText' => $text,
                'MOSA' => $item['mosaStatement'],
                'OriginText' => $cool,
            );
            $scale_items[] = $scale_info;
        }

        $scales = $this->getScales(FormLib::get('scales', array()));
        \COREPOS\Fannie\API\item\HobartDgwLib::writeItemsToScales($scale_items, $scales);
        \COREPOS\Fannie\API\item\EpScaleLib::writeItemsToScales($scale_items, $scales);

        return true;
    }

    public function results_content()
    {
        return '<div class="alert alert-success">Import complete</div>';
    }

    public function preview_content()
    {
        $scales = new ServiceScalesModel($this->connection);
        $ret = '<fieldset><legend>Send to Scales</legend>';
        foreach ($scales->find('description') as $obj) {
            $ret .= sprintf('<label>
                <input type="checkbox" name="scales[]" value="%d" /> %s
                </label><br />',
                $obj->serviceScaleID(), $obj->description()
            );
        }
        $ret .= '</fieldset>';
        $ret .= '<label><input type="checkbox" name="prodPricing" />
                    Update POS Product Pricing</label>';

        return $ret;
    }

    private function getScales($ids)
    {
        $model = new ServiceScalesModel($this->connection);
        $scales = array();
        foreach ($ids as $scaleID) {
            $model->reset();
            $model->serviceScaleID($scaleID);
            if (!$model->load()) {
                // scale doesn't exist
                continue;
            }
            $repr = array(
                'host' => $model->host(),
                'dept' => $model->scaleDeptName(),
                'type' => $model->scaleType(),  
                'new' => false,
            );
            $scales[] = $repr;
        }

        return $scales;
    }
    
    public function unitTest($phpunit)
    {
        $data = array('4011', '0.99', 'Testlandia');
        $indexes = array('upc'=>0, 'price'=>1, 'cool'=>2);
        $phpunit->assertEquals(true, $this->process_file(array($data), $indexes));
        $phpunit->assertNotEquals(0, strlen($this->results_content()));
    }
}

FannieDispatch::conditionalExec();

