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

class FannieLaneStatusService extends \COREPOS\Fannie\API\webservices\FannieWebService
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
        $config = \FannieConfig::factory();
        $one_host = false;
        if (property_exists($args, 'host')) {
            $one_host = $args->host;
        }
        $check_upc = false;
        if (property_exists($args, 'upc')) {
            $check_upc = \BarcodeLib::padUPC($args->upc);
        }

        $lanes = array_filter($config->get('LANES'), 
            function($i) use ($one_host) { return ($one_host === false || $one_host == $i['host']); });

        $ret = array_map(function($f) use ($check_upc) {
            $lane = array();
            $sql = new \SQLManager($f['host'],$f['type'],$f['op'],$f['user'],$f['pw']);
            if ($sql->isConnected($f['op'])) {
                $lane['online'] = true;
                if ($check_upc) {
                    $prep = $sql->prepare('SELECT * FROM products WHERE upc=?'); 
                    $res = $sql->execute($prep, array($check_upc));
                    $lane['itemFound'] = $sql->numRows($res);
                    $lane['itemUPC'] = $check_upc;
                    if ($lane['itemFound'] > 0) {
                        $row = $sql->fetchRow($res);
                        $lane['itemDescription'] = $row['description'];
                        $lane['itemPrice'] = $row['normal_price'];
                        if ($row['discounttype'] != 0) {
                            $lane['itemOnSale'] = true;
                            $lane['itemSalePrice'] = $row['special_price'];
                        }
                    }
                }
            } else {
                $lane['online'] = false;
            }

            return $lane;
        }, $lanes);


        return $ret;
   }
}

