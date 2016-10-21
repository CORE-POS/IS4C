<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

namespace COREPOS\Fannie\API\webservices;

class FannieItemLaneSync extends \COREPOS\Fannie\API\webservices\FannieWebService
{
    
    public $type = 'json'; // json/plain by default

    /**
      Do whatever the service is supposed to do.
      Should override this.
      @param $args array of data
      @return an array of data
    */
    public function run($args=array())
    {
        $ret = array();
        if (!property_exists($args, 'upc')) {
            // missing required arguments
            $ret['error'] = array(
                'code' => -32602,
                'message' => 'Invalid parameters needs upc',
            );
            return $ret;
        }

        if (!is_array($args->upc)) {
            $args->upc = array($args->upc);
        }

        $dbc = \FannieDB::get(\FannieConfig::config('OP_DB'));
        $storeID = \FannieConfig::config('STORE_ID');
        /**
          In "fast" mode, look up the items and run UPDATE queries
          on each lane. This reduces overhead substantially but will
          overlook brand-new items since there's no check whether the
          item exists on the lane.

          If "fast" is not specified, each UPC record is copied to the
          lane exactly using models. This mode is preferrable unless
          performance becomes an issue.
        */
        if (property_exists($args, 'fast')) {
            $upc_data = $this->getItemData($dbc, $args, $storeID);

            $updateQ = '
                UPDATE products AS p SET
                    p.normal_price = ?,
                    p.pricemethod = ?,
                    p.quantity = ?,
                    p.groupprice = ?,
                    p.special_price = ?,
                    p.specialpricemethod = ?,
                    p.specialquantity = ?,
                    p.specialgroupprice = ?,
                    p.discounttype = ?,
                    p.mixmatchcode = ?,
                    p.department = ?,
                    p.tax = ?,
                    p.foodstamp = ?,
                    p.discount=?,
                    p.scale=?,
                    p.qttyEnforced=?,
                    p.idEnforced=?,
                    p.inUse=?,
                    p.wicable = ?
                WHERE p.upc = ?';
            $FANNIE_LANES = \FannieConfig::config('LANES');
            for ($i = 0; $i < count($FANNIE_LANES); $i++) {
                $lane_sql = new \SQLManager($FANNIE_LANES[$i]['host'],$FANNIE_LANES[$i]['type'],
                    $FANNIE_LANES[$i]['op'],$FANNIE_LANES[$i]['user'],
                    $FANNIE_LANES[$i]['pw']);
                
                if (!isset($lane_sql->connections[$FANNIE_LANES[$i]['op']]) || $lane_sql->connections[$FANNIE_LANES[$i]['op']] === false) {
                    // connect failed
                    continue;
                }
                $this->updateLane($lane_sql, $upc_data, $updateQ);
            }
        } else {
            $product = new \ProductsModel($dbc);
            $ret['synced'] = array();
            foreach ($args->upc as $upc) {
                $upc = \BarcodeLib::padUPC($upc);
                $product->upc($upc);
                $product->store_id($storeID);
                if ($product->load()) {
                    $product->pushToLanes();
                    $ret['synced'][] = $upc;
                }
            }
        }

        return $ret;
    }

    private function getItemData($dbc, $args, $storeID)
    {
        $upc_data = array();
        $query = '
            SELECT normal_price,
                pricemethod,
                quantity,
                groupprice,
                special_price,
                specialpricemethod,
                specialquantity,
                specialgroupprice,
                discounttype,
                mixmatchcode,
                department,
                tax,
                foodstamp,
                discount,
                scale,
                qttyEnforced,
                idEnforced,
                inUse,
                wicable,
                upc
            FROM products
            WHERE store_id=?
                AND upc IN ('; 
        $params = array($storeID);
        foreach ($args->upc as $upc) {
            $query .= '?,';
            $params[] = \BarcodeLib::padUPC($upc);
        }
        $query = substr($query, 0, strlen($query)-1) . ')';
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $params);
        while ($w = $dbc->fetchRow($result)) {
            $upc_data[$w['upc']] = $w;
        }

        return $upc_data;
    }

    private function updateLane($lane_sql, $upc_data, $updateQ)
    {
        $updateP = $lane_sql->prepare($updateQ);
        foreach ($upc_data as $upc => $data) {
            $lane_args = array(
                $data['normal_price'],
                $data['pricemethod'],
                $data['quantity'],
                $data['groupprice'],
                $data['special_price'],
                $data['specialpricemethod'],
                $data['specialquantity'],
                $data['specialgroupprice'],
                $data['discounttype'],
                $data['mixmatchcode'],
                $data['department'],
                $data['tax'],
                $data['foodstamp'],
                $data['discount'],
                $data['scale'],
                $data['qttyEnforced'],
                $data['idEnforced'],
                $data['inUse'],
                $data['wicable'],
                $upc,
            );
            $lane_sql->execute($updateP, $lane_args);
        }
    }
}

