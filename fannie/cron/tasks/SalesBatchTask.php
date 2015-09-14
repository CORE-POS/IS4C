<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

class SalesBatchTask extends FannieTask
{

    public $name = 'Sales Batch Task';

    public $description = 'Apply sales batches. Puts items on sale
    if they are in a current batch and also takes items off sale
    if they are not in a current batch.
    Replaces the old nightly.batch.php script.';

    public $default_schedule = array(
        'min' => 10,
        'hour' => 2,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $now = date('Y-m-d 00:00:00');
        $sale_upcs = array();

        // ensure likecode items are mixmatch-able
        if ($dbc->dbms_name() == 'mssql') {
            $dbc->query("UPDATE products
                SET mixmatchcode=convert(varchar,u.likecode+500)
                FROM 
                products AS p
                INNER JOIN upcLike AS u
                ON p.upc=u.upc");
        } else {
            $dbc->query("UPDATE products AS p
                INNER JOIN upcLike AS u ON p.upc=u.upc
                SET p.mixmatchcode=convert(u.likeCode+500,char)");
        }

        $likeP = $dbc->prepare('SELECT u.upc 
                                FROM upcLike AS u
                                    INNER JOIN products AS p ON u.upc=p.upc
                                WHERE likeCode=?');
        $product = new ProductsModel($dbc);
        $b_def = $dbc->tableDefinition('batches');

        // lookup current batches
        $query = 'SELECT l.upc, 
                    l.batchID, 
                    l.pricemethod, 
                    l.salePrice, 
                    l.groupSalePrice,
                    l.quantity,
                    b.startDate, 
                    b.endDate, 
                    b.discounttype
                    ' . (isset($b_def['transLimit']) ? ',b.transLimit' : ',0 AS transLimit') . '
                  FROM batches AS b
                    INNER JOIN batchList AS l ON b.batchID = l.batchID
                  WHERE b.discounttype <> 0
                    AND b.startDate <= ?
                    AND b.endDate >= ?
                  ORDER BY l.upc,
                    l.salePrice DESC';
        $t_def = $dbc->tableDefinition('batchList');
        if (!isset($t_def['groupSalePrice'])) {
            $query = str_replace('l.groupSalePrice', 'NULL AS groupSalePrice', $query);
        }
        /**
          In HQ mode, join on junction table to get UPC+storeID rows
          when applying sale pricing
        */
        if ($this->config->get('STORE_MODE') === 'HQ') {
            $query = str_replace('WHERE', ' LEFT JOIN StoreBatchMap AS s ON b.batchID=s.batchID WHERE ', $query);
            $query = str_replace('SELECT', 'SELECT s.storeID,', $query);
        }
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, array($now, $now));
        while ($row = $dbc->fetch_row($result)) {
            // all items affected by this bathcList record
            // could be more than one in the case of likecodes
            $item_upcs = array();

            // use products column names for readability below
            $special_price = $row['salePrice'];
            $specialpricemethod = $row['pricemethod'];
            if ($row['groupSalePrice'] != null) {
                $specialgroupprice = $row['groupSalePrice'];
            } else {
                $specialgroupprice = abs($row['salePrice']);
            }
            $specialquantity = $row['quantity'];
            $special_limit = $row['transLimit'];
            $start_date = $row['startDate'];
            $end_date = $row['endDate'];
            $discounttype = $row['discounttype'];
            $batchID = $row['batchID'];

            // pricemethod 3 and 4 (AB pricing, typically)
            // has some overly complicated rules
            $mixmatch = false;
            if ($specialpricemethod == 3 || $specialpricemethod==4) {
                if ($special_price >= 0) {
                    $mixmatch = $row['batchID'];
                } else {
                    $mixmatch = -1 * $row['batchID'];
                }
            }

            // unpack likecodes, if needed
            if (substr($row['upc'], 0, 2) == 'LC') {
                $likeCode = substr($row['upc'], 2);
                $likeR = $dbc->execute($likeP, array($likeCode));
                while ($likeW = $dbc->fetch_row($likeR)) {
                    $item_upcs[] = $likeW['upc'];
                    if ($mixmatch !== false) {
                        $mixmatch = $likeCode + 500;
                    }
                }
            } else {
                $item_upcs[] = $row['upc'];
            }

            // check each item to see if it is on
            // sale with the correct parameters
            foreach ($item_upcs as $upc) {
                $product->reset();
                $product->upc($upc);
                $this->cronMsg('Checking item ' . $upc, FannieLogger::INFO);
                /**
                  Transistion mechanism. A batch that is set to apply to
                  zero stores really should apply to zero stores. For now
                  it fails over to using the local store's ID
                */
                if ($this->config->get('STORE_MODE') === 'HQ') {
                    $storeID = $row['storeID'];
                    if ($storeID == null) {
                        $storeID = $this->config->get('STORE_ID');
                    }
                    $product->store_id($storeID);
                }
                if (!$product->load()) {
                    $this->cronMsg("\tError: item does not exist in products", FannieLogger::NOTICE);
                    continue;
                }
                // list of UPCs that should be on sale
                $sale_upcs[] = $upc;

                $changed = false;
                if ($product->special_price() != $special_price) {
                    $changed = true;
                    $product->special_price($special_price);
                }
                if ($product->specialpricemethod() != $specialpricemethod) {
                    $changed = true;
                    $product->specialpricemethod($specialpricemethod);
                }
                if ($product->specialgroupprice() != $specialgroupprice) {
                    $changed = true;
                    $product->specialgroupprice($specialgroupprice);
                }
                if ($product->specialquantity() != $specialquantity) {
                    $changed = true;
                    $product->specialquantity($specialquantity);
                }
                if ($product->special_limit() != $special_limit) {
                    $changed = true;
                    $product->special_limit($special_limit);
                }
                if ($product->start_date() != $start_date) {
                    $changed = true;
                    $product->start_date($start_date);
                }
                if ($product->end_date() != $end_date) {
                    $changed = true;
                    $product->end_date($end_date);
                }
                if ($product->discounttype() != $discounttype) {
                    $changed = true;
                    $product->discounttype($discounttype);
                }
                if ($mixmatch !== false && $product->mixmatchcode() != $mixmatch) {
                    $changed = true;
                    $product->mixmatchcode($mixmatch);
                }
                if ($product->batchID() != $batchID) {
                    $changed = true;
                    $product->batchID($batchID);
                }

                if ($changed) {
                    $product->save();
                    $this->cronMsg("\tUpdated item", FannieLogger::INFO);
                }
            } // end loop on batchList record items
        } // end loop on batchList records

        // No sale items; need a filler value for
        // the query below
        if (count($sale_upcs) == 0) {
            $this->cronMsg('Notice: nothing is currently on sale', FannieLogger::WARNING);
            $sale_upcs[] = 'notValidUPC';
        }
        // now look for anything on sale that should not be
        // and take those items off sale
        $upc_in = '';
        foreach($sale_upcs as $upc) {
            $upc_in .= '?,';
        }
        $upc_in = substr($upc_in, 0, strlen($upc_in)-1);
        $lookupQ = 'SELECT p.upc
                    FROM products AS p
                    WHERE upc NOT IN (' . $upc_in . ')
                        AND (
                            p.discounttype <> 0
                            OR p.special_price <> 0
                            OR p.specialpricemethod <> 0
                            OR p.specialgroupprice <> 0
                            OR p.specialquantity <> 0
                        )';
        $lookupP = $dbc->prepare($lookupQ);
        $lookupR = $dbc->execute($lookupP, $sale_upcs);
        while($lookupW = $dbc->fetch_row($lookupR)) {
            $this->cronMsg('Taking ' . $lookupW['upc'] . ' off sale', FannieLogger::INFO);
            $product->reset();
            if ($this->config->get('STORE_MODE') === 'HQ') {
                $product->store_id($this->config->get('STORE_ID'));
            }
            $product->upc($lookupW['upc']);
            $product->discounttype(0);
            $product->special_price(0);
            $product->specialgroupprice(0);
            $product->specialquantity(0);
            $product->start_date('');
            $product->end_date('');
            $product->batchID(0);
            $product->save();
        }
    }
}

