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
use \FannieDB;
use \FannieConfig;

class FannieItemInfo extends FannieWebService
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
        if (!property_exists($args, 'type')) {
            // missing required arguments
            $ret['error'] = array(
                'code' => -32602,
                'message' => 'Invalid parameters needs type',
            );
            return $ret;
        }

        // validate additional arguments
        switch (strtolower($args->type)) {
            case 'vendor':
                if (!property_exists($args, 'vendor_id')) {
                    // vendor ID required
                    $ret['error'] = array(
                        'code' => -32602,
                        'message' => 'Invalid parameters needs vendor_id',
                    );
                    return $ret;
                } elseif (!property_exists($args, 'sku') && !property_exists($args, 'upc')) {
                    // either sku or upc is required
                    $ret['error'] = array(
                        'code' => -32602,
                        'message' => 'Invalid parameters needs sku or upc',
                    );
                    return $ret;
                }
                break;

            default:
                // unknown type argument
                $ret['error'] = array(
                    'code' => -32602,
                    'message' => 'Invalid parameters',
                );
                return $ret;
        }

        // lookup results
        $dbc = \FannieDB::getReadOnly(\FannieConfig::factory()->get('OP_DB'));
        switch (strtolower($args->type)) {
            case 'vendor':
                $vendor = new \VendorItemsModel($dbc);
                $vendor->vendorID($args->vendor_id);
                if (property_exists($args, 'sku')) {
                    $vendor->sku($args->sku);
                } elseif (property_exists($args, 'upc')) {
                    $vendor->upc($args->upc);
                }
                foreach ($vendor->find() as $v) {
                    $ret['sku'] = $v->sku();
                    $ret['upc'] = $v->upc();
                    $ret['size'] = $v->size();
                    $ret['units'] = $v->units();
                    $ret['brand'] = $v->brand();
                    $ret['description'] = $v->description();
                    $ret['cost'] = $v->cost();
                    break;
                }

                return $ret;
        }
    }

}

