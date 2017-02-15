<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

class DenfeldInitialUpload extends \COREPOS\Fannie\API\FannieUploadPage 
{
    public $title = "Fannie - Load Denfeld Initial Product List";
    public $header = "Upload Denfeld Initial Product List";
    public $themed = true;

    public $description = '[Denfeld Catalog Import] is the default tool for initializing the 
    product list for new Denfeld location.';

    protected $must_authenticate = true;

    protected $preview_opts = array(
        'upc' => array(
            'name' => 'upc',
            'display_name' => 'UPC *',
            'default' => 0,
            'required' => true
        ),
    );    
    
    protected $use_js = true;

    public function process_file($linedata, $indexes)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        
        $pmodel = new ProductsModel($dbc);
        $prep = $dbc->prepare('INSERT INTO denfeldList
                    (upc)
                VALUES
                    (?)
            ');
            
        foreach($linedata as $data) {
            if (!is_array($data)) continue;
            if (!isset($data[$indexes['upc']])) continue;
            
            $upc = $data[$indexes['upc']];
            if (strlen($upc) > 13) {
                $upc = substr($upc, -13);
            } else {
                $upc = str_pad($upc, 13, '0', STR_PAD_LEFT);
            }
            $res = $dbc->execute($prep, $upc);
            $row = $dbc->fetchRow($res);
            if (mysql_errno() > 0) {
                echo mysql_errno() . ": " . mysql_error(). "<br>";
            }
        }

        $this->updateInUse($upc);
        $this->copyItems($upc);
        
        return true;
    }

    private function updateInUse($upc)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        
        $prep = $dbc->prepare('
            UPDATE products AS p
            SET p.inUse=1
            WHERE p.store_id=2
                AND p.upc IN (SELECT upc FROM denfeldList)
        ;');
        $res = $dbc->execute($prep);
        $row = $dbc->fetchRow($res);
        if (mysql_errno() > 0) {
                echo mysql_errno() . ": " . mysql_error(). "<br>";
        }
        
        $prep = $dbc->prepare('
            UPDATE products AS p
            SET p.inUse=0
            WHERE p.store_id=2
                AND p.upc NOT IN (SELECT upc FROM denfeldList)
                AND p.department NOT BETWEEN 60 AND 78
                AND p.department != 150
                AND p.department NOT BETWEEN 200 AND 239
                AND p.department NOT BETWEEN 500 AND 998
        ;');
        $res = $dbc->execute($prep);
        $row = $dbc->fetchRow($res);
        if (mysql_errno() > 0) {
                echo mysql_errno() . ": " . mysql_error(). "<br>";
        }
    }
    
    private function copyItems($upc)
    {
        $dbc = FannieDB::get($FANNIE_OP_DB);
        
        $prep = $dbc->prepare('
            INSERT INTO products
            ( 
                upc,
                description,
                brand,
                formatted_name,
                normal_price,
                pricemethod,
                groupprice,
                quantity,
                special_price,
                specialpricemethod,
                specialgroupprice,
                specialquantity,
                special_limit,
                start_date,
                end_date,
                department,
                size,
                tax,
                foodstamp,
                scale,
                scaleprice,
                mixmatchcode,
                modified,
                batchID,
                tareweight,
                discount,
                discounttype,
                line_item_discountable,
                unitofmeasure,
                wicable,
                qttyEnforced,
                idEnforced,
                cost,
                inUse,
                numflag,
                subdept,
                deposit,
                local,
                default_vendor_id,
                current_origin_id,
                auto_par,
                price_rule_id,
                store_id
        ) 
        SELECT
                upc,
                description,
                brand,
                formatted_name,
                normal_price,
                pricemethod,
                groupprice,
                quantity,
                special_price,
                specialpricemethod,
                specialgroupprice,
                specialquantity,
                special_limit,
                start_date,
                end_date,
                department,
                size,
                tax,
                foodstamp,
                scale,
                scaleprice,
                mixmatchcode,
                modified,
                batchID,
                tareweight,
                discount,
                discounttype,
                line_item_discountable,
                unitofmeasure,
                wicable,
                qttyEnforced,
                idEnforced,
                cost,
                inUse,
                numflag,
                subdept,
                deposit,
                local,
                default_vendor_id,
                current_origin_id,
                auto_par,
                price_rule_id,
                2
            FROM products
            WHERE store_id=1
                AND upc IN (
                    SELECT upc
                    FROM denfeldList
                )
                AND upc NOT IN (
                    SELECT upc 
                    FROM products 
                    WHERE store_id=2
                )
        ;');
        $res = $dbc->execute($prep);
        $row = $dbc->fetchRow($res);
        if (mysql_errno() > 0) {
                echo mysql_errno() . ": " . mysql_error(). "<br>";
        }
    }

    function preview_content()
    {
        return '<input type="checkbox" name="rm_cds" value="1" checked /> Remove check digits<br />
                <input type="checkbox" name="up_costs" value="1" checked /> Update product costs';
    }

    function results_content()
    {
        $ret = "<p>Denfeld item list update complete</p>";
        
        return $ret;
    }

    public function preprocess()
    {
        if (php_sapi_name() !== 'cli') {
            /* this page requires a session to pass some extra
               state information through multiple requests */
            @session_start();
        }

        return parent::preprocess();
    }

    

    public function helpContent()
    {
        return '
        <p>Initialize product "In Use" status for opening second store location. 
        </p>';
    }
}

FannieDispatch::conditionalExec(false);

